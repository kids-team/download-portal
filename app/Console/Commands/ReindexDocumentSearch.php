<?php

namespace App\Console\Commands;

use App\Actions\UpdateDocumentSearchIndex;
use App\Models\Document;
use App\Models\DocumentSearchIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('search:reindex')]
#[Description('Rebuild the derived document search index')]
class ReindexDocumentSearch extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UpdateDocumentSearchIndex $updateDocumentSearchIndex): int
    {
        DocumentSearchIndex::query()->delete();
        Document::query()->lazyById()->each(fn (Document $document) => $updateDocumentSearchIndex->handle($document));
        $this->info('Document search index rebuilt.');

        return self::SUCCESS;
    }
}
