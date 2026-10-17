<?php

namespace App\Actions;

use App\Models\Document;
use App\Models\DocumentSearchIndex;

class UpdateDocumentSearchIndex
{
    public function handle(Document $document): DocumentSearchIndex
    {
        $document->loadMissing('category', 'audience', 'tags', 'bibleReferences');
        $content = $this->extract($document->content);
        $metadata = collect([$document->label, $document->category?->name, $document->audience?->name])
            ->merge($document->tags->pluck('name'))->filter()->implode(' ');
        $bible = $document->bibleReferences->map(fn ($reference): string => $reference->translation.' '.$reference->apiPath())->implode(' ');
        $values = ['title_text' => $this->normalize($document->title), 'summary_text' => $this->normalize((string) $document->summary), 'content_text' => $this->normalize($content), 'metadata_text' => $this->normalize($metadata), 'bible_reference_text' => $this->normalize($bible)];
        $values['searchable_text'] = $this->normalize(implode(' ', $values));

        return DocumentSearchIndex::query()->updateOrCreate(['document_id' => $document->id], $values);
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
        } $type = strtolower((string) ($node['type'] ?? ''));
        if ($type === 'text') {
            if (is_string($node['text'] ?? null)) {
                $texts[] = $node['text'];
            }

            return;
        } if (str_contains($type, 'mention')) {
            foreach (['text', 'label', 'name', 'value'] as $field) {
                if (is_string($node[$field] ?? null)) {
                    $texts[] = $node[$field];
                    break;
                }
            }
        } foreach ($node['children'] ?? [] as $child) {
            $this->visit($child, $texts);
        }
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($value)));
    }
}
