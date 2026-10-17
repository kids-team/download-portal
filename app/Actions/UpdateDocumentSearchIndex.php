<?php

namespace App\Actions;

use App\Models\Document;
use App\Models\DocumentSearchIndex;

class UpdateDocumentSearchIndex
{
    public function handle(Document $document): DocumentSearchIndex
    {
        $document->loadMissing('category', 'audience', 'tags', 'bibleReferences');

        $values = $this->indexValues($document);

        return DocumentSearchIndex::query()->updateOrCreate(['document_id' => $document->id], $values);
    }

    /**
     * @return array{
     *     title_text: string,
     *     summary_text: string,
     *     content_text: string,
     *     metadata_text: string,
     *     bible_reference_text: string,
     *     searchable_text: string
     * }
     */
    private function indexValues(Document $document): array
    {
        $values = [
            'title_text' => $this->normalize($document->title),
            'summary_text' => $this->normalize((string) $document->summary),
            'content_text' => $this->normalize($this->extract($document->content)),
            'metadata_text' => $this->normalize($this->metadata($document)),
            'bible_reference_text' => $this->normalize($this->bibleReferences($document)),
        ];

        $values['searchable_text'] = $this->normalize(implode(' ', $values));

        return $values;
    }

    private function metadata(Document $document): string
    {
        return collect([
            $document->label,
            $document->category?->name,
            $document->audience?->name,
        ])
            ->merge($document->tags->pluck('name'))
            ->filter()
            ->implode(' ');
    }

    private function bibleReferences(Document $document): string
    {
        return $document->bibleReferences
            ->map(fn ($reference): string => $reference->translation.' '.$reference->apiPath())
            ->implode(' ');
    }

    private function extract(?array $node): string
    {
        $texts = [];

        if (is_array($node['root'] ?? null)) {
            $node = $node['root'];
        }

        $this->visit($node, $texts);

        return implode(' ', $texts);
    }

    /** @param array<int, string> $texts */
    private function visit(mixed $node, array &$texts): void
    {
        if (! is_array($node)) {
            return;
        }

        $type = strtolower((string) ($node['type'] ?? ''));
        if ($type === 'text' && is_string($node['text'] ?? null)) {
            $texts[] = $node['text'];

            return;
        }

        if ($type === 'text') {
            return;
        }

        if (str_contains($type, 'mention')) {
            $this->addMentionText($node, $texts);
        }

        foreach ($node['children'] ?? [] as $child) {
            $this->visit($child, $texts);
        }
    }

    /** @param array<int|string, mixed> $node @param array<int, string> $texts */
    private function addMentionText(array $node, array &$texts): void
    {
        foreach (['text', 'label', 'name', 'value'] as $field) {
            $value = $node[$field] ?? null;
            if (! is_string($value)) {
                continue;
            }

            $texts[] = $value;

            return;
        }
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($value)));
    }
}
