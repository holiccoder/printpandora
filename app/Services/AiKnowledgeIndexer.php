<?php

namespace App\Services;

use App\Models\AiKnowledgeChunk;
use App\Models\Faq;
use App\Models\HelpArticle;
use App\Models\Product;
use App\Support\HardcodedContent;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Embeddings;

/**
 * Builds the AI support knowledge base: collects text documents from the
 * site's content sources, splits them into chunks, embeds them, and stores
 * the vectors in the ai_knowledge_chunks table.
 */
class AiKnowledgeIndexer
{
    /** Content JSON sections worth feeding to the support agent. */
    private const CONTENT_SECTIONS = [
        'help_center_page',
        'about_page',
        'product_detail_page',
        'business_cards_landing_page',
        'postcards_page',
        'stickers_page',
        'flyers_page',
        'sample_pack_page',
        'free_sample_pack_page',
        'shipping_page',
        'affiliate_program_page',
    ];

    /**
     * Yield all knowledge documents as [source_type, source_id, content].
     *
     * @return iterable<array{0: string, 1: string, 2: string}>
     */
    public function documents(): iterable
    {
        foreach (HelpArticle::query()->where('is_published', true)->get() as $article) {
            $text = $this->clean(implode("\n\n", array_filter([
                $article->title,
                $article->excerpt,
                $article->body,
            ])));

            if ($text !== '') {
                yield ['help_article', (string) $article->id, $text];
            }
        }

        foreach (Faq::query()->where('is_published', true)->get() as $faq) {
            $text = $this->clean("Q: {$faq->question}\n\nA: {$faq->answer}");

            if ($text !== '') {
                yield ['faq', (string) $faq->id, $text];
            }
        }

        foreach (Product::query()->where('is_active', true)->get() as $product) {
            $text = $this->clean(implode("\n\n", array_filter([
                $product->name,
                $product->subtitle,
                $product->description_title,
                $product->description,
                implode("\n", (array) ($product->bullet_points ?? [])),
                $product->price_line,
            ])));

            if ($text !== '') {
                yield ['product', (string) $product->id, $text];
            }
        }

        $content = app(HardcodedContent::class)->all();

        foreach (self::CONTENT_SECTIONS as $section) {
            if (! isset($content[$section])) {
                continue;
            }

            $text = $this->clean($this->flatten($content[$section]));

            if ($text !== '') {
                yield ['content', $section, $text];
            }
        }

        $dir = base_path('content/ai-knowledge');

        if (is_dir($dir)) {
            foreach (File::files($dir) as $file) {
                if (! in_array($file->getExtension(), ['md', 'txt'], true)) {
                    continue;
                }

                $text = $this->clean($file->getContents());

                if ($text !== '') {
                    yield ['file', $file->getFilename(), $text];
                }
            }
        }
    }

    /**
     * Rebuild the whole knowledge base. Returns counters for reporting.
     *
     * @return array{documents: int, chunks: int}
     */
    public function reindex(?callable $progress = null): array
    {
        AiKnowledgeChunk::query()->delete();

        $documents = 0;
        $chunks = 0;
        $pending = [];

        $flush = function () use (&$pending, &$chunks) {
            if ($pending === []) {
                return;
            }

            $response = Embeddings::for(array_column($pending, 'content'))
                ->generate(null, config('aichat.embedding_model'));

            foreach ($pending as $i => $row) {
                AiKnowledgeChunk::create([
                    ...$row,
                    'embedding' => $response->embeddings[$i],
                ]);
            }

            $chunks += count($pending);
            $pending = [];
        };

        foreach ($this->documents() as [$sourceType, $sourceId, $content]) {
            $documents++;

            foreach ($this->chunk($content) as $index => $chunk) {
                $pending[] = [
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'chunk_index' => $index,
                    'content' => $chunk,
                ];

                if (count($pending) >= 50) {
                    $flush();
                    $progress?->call($this, $chunks);
                }
            }
        }

        $flush();

        return ['documents' => $documents, 'chunks' => $chunks];
    }

    /**
     * Split text into overlapping chunks of roughly `aichat.chunk_size`
     * characters, preferring paragraph boundaries.
     *
     * @return string[]
     */
    public function chunk(string $text): array
    {
        $size = (int) config('aichat.chunk_size', 500);
        $overlap = (int) config('aichat.chunk_overlap', 80);

        if (mb_strlen($text) <= $size) {
            return [$text];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $size, $length);

            if ($end < $length) {
                // Prefer to break at a paragraph or sentence boundary.
                $window = mb_substr($text, $start, $size);
                $break = max(
                    (int) mb_strrpos($window, "\n\n"),
                    (int) mb_strrpos($window, '. '),
                    (int) mb_strrpos($window, "\n"),
                );

                if ($break > $size / 2) {
                    $end = $start + $break + 1;
                }
            }

            $chunks[] = trim(mb_substr($text, $start, $end - $start));

            $start = max($end - $overlap, $start + 1);
        }

        return array_values(array_filter($chunks));
    }

    /**
     * Recursively flatten a content-JSON subtree into plain text lines.
     */
    private function flatten(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        return implode("\n", array_filter(array_map(
            fn ($item) => $this->flatten($item),
            $value,
        )));
    }

    private function clean(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
