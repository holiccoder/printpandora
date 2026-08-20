<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Support Chat
    |--------------------------------------------------------------------------
    |
    | Settings for the storefront AI support widget. Chat replies and knowledge
    | embeddings use the "openai" provider from config/ai.php, so a valid
    | OPENAI_API_KEY is required for both.
    |
    */

    'enabled' => env('AI_CHAT_ENABLED', true),

    // Chat provider/model. DeepSeek is supported for chat replies, but has no
    // embeddings API — knowledge indexing/retrieval always uses the embedding
    // provider below (openai by default), regardless of this setting.
    'provider' => env('AI_CHAT_PROVIDER', 'openai'),

    'model' => env('AI_CHAT_MODEL', 'gpt-4o-mini'),

    'embedding_model' => env('AI_CHAT_EMBEDDING_MODEL', 'text-embedding-3-small'),

    // Number of knowledge chunks injected into the system prompt.
    'top_k' => env('AI_CHAT_TOP_K', 5),

    // Conversation history messages sent along with each request.
    'max_history' => env('AI_CHAT_MAX_HISTORY', 10),

    // Chunking for the knowledge indexer.
    'chunk_size' => 500,

    'chunk_overlap' => 80,

];
