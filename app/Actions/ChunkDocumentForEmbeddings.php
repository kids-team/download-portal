<?php

namespace App\Actions;

use App\DocumentContentExtractor;
use App\Models\Document;

class ChunkDocumentForEmbeddings
{
    public function __construct(private DocumentContentExtractor $documentContentExtractor) {}

    /**
     * The service verifies the actual tokenizer limit. This conservative word
     * boundary keeps normal passages well below the E5 512-token limit, while
     * a pathological token is never truncated silently.
     *
     * @return array<int, string>
     */
    public function handle(Document $document): array
    {
        $text = trim($this->documentContentExtractor->extract($document->content));

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
}
