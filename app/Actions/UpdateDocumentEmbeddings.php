<?php

namespace App\Actions;

use App\Jobs\GenerateDocumentEmbeddings;
use App\Models\Document;

class UpdateDocumentEmbeddings
{
    public function handle(Document $document): void
    {
        GenerateDocumentEmbeddings::dispatch($document->getKey())->onConnection('database');
    }
}
