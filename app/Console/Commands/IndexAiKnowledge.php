<?php

namespace App\Console\Commands;

use App\Services\AiKnowledgeIndexer;
use Illuminate\Console\Command;

class IndexAiKnowledge extends Command
{
    protected $signature = 'ai:index-knowledge';

    protected $description = 'Rebuild the AI support chat knowledge base (chunk + embed all content sources)';

    public function handle(AiKnowledgeIndexer $indexer): int
    {
        $this->info('Indexing AI knowledge base...');

        $stats = $indexer->reindex(function (int $chunks) {
            $this->line("  embedded {$chunks} chunks so far...");
        });

        $this->info("Done. {$stats['documents']} documents, {$stats['chunks']} chunks indexed.");

        return self::SUCCESS;
    }
}
