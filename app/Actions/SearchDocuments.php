<?php

namespace App\Actions;

use App\Models\DocumentSearchIndex;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

class SearchDocuments
{
    /** @param array<string, mixed> $filters */
    public function handle(array $filters): LengthAwarePaginator
    {
        $terms = $this->terms((string) ($filters['q'] ?? ''));
        $indices = DocumentSearchIndex::query()->with('document')->whereHas('document', function ($query) use ($filters): void {
            $query->publiclyAvailable()->when($filters['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($q) => $q->where('slug', $slug)))->when($filters['audience'] ?? null, fn ($q, $slug) => $q->whereHas('audience', fn ($q) => $q->where('slug', $slug)))->when($filters['tag'] ?? null, fn ($q, $slug) => $q->whereHas('tags', fn ($q) => $q->where('slug', $slug)));
        })->when($filters['bible_ref'] ?? null, fn ($q, $ref) => $q->where('bible_reference_text', 'like', '%'.$this->normalize($ref).'%'))->get();
        $classical = $indices->filter(fn ($index) => collect($terms)->every(fn ($term) => str_contains($index->searchable_text, $term)))->map(fn ($index) => ['index' => $index, 'score' => $this->score($index, $terms)])->sort(fn ($left, $right) => $right['score'] <=> $left['score'] ?: $left['index']->document_id <=> $right['index']->document_id)->values();
        $ranked = $this->fuse($classical, $indices, (string) ($filters['q'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 24);
        $page = (int) ($filters['page'] ?? 1);

        return new LengthAwarePaginator($ranked->forPage($page, $perPage)->values(), $ranked->count(), $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
    }

    /** @return array<int,string> */
    private function terms(string $query): array
    {
        return array_values(array_unique(array_filter(explode(' ', $this->normalize($query)))));
    }

    private function score(DocumentSearchIndex $index, array $terms): int
    {
        return collect($terms)->sum(fn ($term) => (str_contains($index->title_text, $term) ? 8 : 0) + (str_contains((string) $index->summary_text, $term) ? 5 : 0) + (str_contains((string) $index->metadata_text, $term) ? 4 : 0) + (str_contains((string) $index->bible_reference_text, $term) ? 6 : 0) + (str_contains((string) $index->content_text, $term) ? 2 : 0));
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($value)));
    }

    /**
     * RRF uses rank positions only; a semantic outage or an empty RAM index
     * deliberately returns the deterministic classic result unchanged.
     *
     * @param  Collection<int, array{index: DocumentSearchIndex, score: int}>  $classical
     * @param  Collection<int, DocumentSearchIndex>  $visibleIndices
     * @return Collection<int, array{index: DocumentSearchIndex, score: float|int}>
     */
    private function fuse(Collection $classical, Collection $visibleIndices, string $query): Collection
    {
        if (trim($query) === '') {
            return $classical;
        }

        $semantic = $this->semanticRanks($query);
        if ($semantic === []) {
            return $classical;
        }

        $byDocumentId = $visibleIndices->keyBy('document_id');
        $scores = [];
        foreach ($classical->values() as $rank => $result) {
            $scores[$result['index']->document_id] = 1 / (60 + $rank + 1);
        }
        foreach ($semantic as $documentId => $rank) {
            if ($byDocumentId->has($documentId)) {
                $scores[$documentId] = ($scores[$documentId] ?? 0) + 1 / (60 + $rank);
            }
        }

        return collect($scores)->map(fn (float $score, int|string $documentId): array => ['index' => $byDocumentId->get($documentId), 'score' => $score])
            ->filter(fn (array $result): bool => $result['index'] !== null)
            ->sort(fn (array $left, array $right): int => $right['score'] <=> $left['score'] ?: $left['index']->document_id <=> $right['index']->document_id)
            ->values();
    }

    /** @return array<int, int> Document id => one-based rank */
    private function semanticRanks(string $query): array
    {
        try {
            $results = Http::baseUrl((string) config('services.semantic_search.url'))
                ->withHeader('X-Internal-Token', (string) config('services.semantic_search.token'))
                ->connectTimeout(1)->timeout(2)
                ->post('/internal/search', ['query' => $query, 'limit' => 500])
                ->throw()->json('results', []);
        } catch (Throwable) {
            return [];
        }

        $ranks = [];
        foreach ($results as $rank => $result) {
            $documentId = (int) ($result['document_id'] ?? 0);
            if ($documentId > 0 && ! isset($ranks[$documentId])) {
                $ranks[$documentId] = $rank + 1;
            }
        }

        return $ranks;
    }
}
