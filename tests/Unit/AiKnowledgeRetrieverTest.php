<?php

namespace Tests\Unit;

use App\Services\AiKnowledgeRetriever;
use PHPUnit\Framework\TestCase;

class AiKnowledgeRetrieverTest extends TestCase
{
    public function test_cosine_similarity_orders_and_normalizes_vectors(): void
    {
        $retriever = new AiKnowledgeRetriever;

        $this->assertEqualsWithDelta(1.0, $retriever->cosine([1, 2, 3], [1, 2, 3]), 1e-9);
        $this->assertEqualsWithDelta(1.0, $retriever->cosine([1, 2, 3], [2, 4, 6]), 1e-9);
        $this->assertEqualsWithDelta(0.0, $retriever->cosine([1, 0], [0, 1]), 1e-9);
        $this->assertEqualsWithDelta(-1.0, $retriever->cosine([1, 0], [-1, 0]), 1e-9);
    }

    public function test_cosine_handles_zero_vectors(): void
    {
        $retriever = new AiKnowledgeRetriever;

        $this->assertSame(0.0, $retriever->cosine([0, 0], [1, 2]));
        $this->assertSame(0.0, $retriever->cosine([1, 2], []));
    }
}
