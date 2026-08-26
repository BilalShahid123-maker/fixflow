<?php

namespace App\Console\Commands;

use App\AI\RAG\KnowledgeIngestion;
use App\Models\KnowledgeDocument;
use Illuminate\Console\Command;

class IngestKnowledgeDocument extends Command
{
    protected $signature = 'knowledge:ingest {documentId?}
                            {--fresh : Delete existing chunks and re-embed from scratch}';

    protected $description = 'Chunk and embed a knowledge document into the vector store';

    public function handle(KnowledgeIngestion $ingestion): int
    {
        $documentId = $this->argument('documentId');

        $query = $documentId
            ? KnowledgeDocument::query()->where('id', $documentId)
            : KnowledgeDocument::query()->published();

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->warn('No documents found to ingest.');

            return self::SUCCESS;
        }

        foreach ($documents as $doc) {
            $chunks = $this->option('fresh')
                ? $ingestion->reIngestDocument($doc)
                : $ingestion->ingestDocument($doc);

            $this->info("Document {$doc->getKey()} [{$doc->title}]: {$chunks} chunks embedded.");
        }

        return self::SUCCESS;
    }
}
