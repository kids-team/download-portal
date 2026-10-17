<?php

namespace App\Console\Commands;

use App\Actions\SyncSemanticIndex;
use App\Actions\UpdateDocumentEmbeddings;
use App\Models\Document;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('semantic:reindex {--reload : Only restore the Python RAM index from stored embeddings}')]
#[Description('Queue embedding regeneration or restore the semantic RAM index')]
class ReindexDocumentEmbeddings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(UpdateDocumentEmbeddings $updateDocumentEmbeddings, SyncSemanticIndex $syncSemanticIndex): int
    {
        if ($this->option('reload')) {
            $syncSemanticIndex->reload();
            $this->components->info('Semantic RAM index restored from persisted embeddings.');

            return self::SUCCESS;
        }

        Document::query()->select('id')->orderBy('id')->each(fn (Document $document) => $updateDocumentEmbeddings->handle($document));
        $this->components->info('Embedding regeneration queued. Run semantic:reindex --reload after the queue is empty.');

        return self::SUCCESS;
    }
}
