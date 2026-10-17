<?php

namespace App\Actions;

use App\Models\Document;

class ChunkDocumentForEmbeddings
{
    /**
     * The service verifies the actual tokenizer limit. This conservative word
     * boundary keeps normal passages well below the E5 512-token limit, while
     * a pathological token is never truncated silently.
     *
     * @return array<int, string>
     */
    public function handle(Document $document): array
    {
        $text = trim($this->extract($document->content));

        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];

        foreach (array_chunk($words, 180) as $words) {
            $chunks[] = implode(' ', $words);
        }

        return $chunks;
    }

    private function extract(?array $node): string
    {
        $texts = [];
        $this->visit($node['root'] ?? $node, $texts);

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

        if (str_contains($type, 'mention')) {
            foreach (['text', 'label', 'name', 'value'] as $field) {
                if (is_string($node[$field] ?? null)) {
                    $texts[] = $node[$field];
                    break;
                }
            }
        }

        foreach ($node['children'] ?? [] as $child) {
            $this->visit($child, $texts);
        }
    }
}
