<?php

namespace App\Services;

use App\Models\AiKnowledgeChunk;
use Laravel\Ai\Embeddings;

/**
 * Retrieves the most relevant knowledge chunks for a customer question by
 * cosine similarity over the stored embeddings.
 */
class AiKnowledgeRetriever
{
    /**
     * @return string[] the top matching chunk contents
     */
    public function retrieve(string $question, ?int $topK = null): array
    {
        $topK = $topK ?? (int) config('aichat.top_k', 5);

        $response = Embeddings::for([$question])
            ->generate(null, config('aichat.embedding_model'));

        $query = $response->embeddings[0] ?? null;

        if (! is_array($query) || $query === []) {
            return [];
        }

        return AiKnowledgeChunk::query()
            ->get()
            ->map(function (AiKnowledgeChunk $chunk) use ($query) {
                return [
                    'content' => $chunk->content,
                    'score' => $this->cosine($query, $chunk->embedding ?? []),
                ];
            })
            ->sortByDesc('score')
            ->take($topK)
            ->pluck('content')
            ->values()
            ->all();
    }

    /**
     * @param  float[]  $a
     * @param  float[]  $b
     */
    public function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $other = $b[$i] ?? 0.0;
            $dot += $value * $other;
            $normA += $value * $value;
            $normB += $other * $other;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
