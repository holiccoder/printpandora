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

    // Controls AI-generated replies. Human-support handoff and polling remain
    // available so customers can still reach an agent without an AI provider.
    'enabled' => env('AI_CHAT_ENABLED', true),

    // When enabled, English customer messages are translated to Chinese for
    // support agents, and Chinese agent replies are translated to English for
    // customers. The original message is always retained as the source text.
    'translation' => [
        'enabled' => env('AI_CHAT_TRANSLATION_ENABLED', false),
    ],

    // Bearer token for the external support-chat client API. Keep this value
    // server-side and rotate it if it is ever exposed.
    'api_token' => env('AI_CHAT_API_TOKEN'),

    // Lifetime of one-time Telegram conversation-link tokens, in minutes.
    'telegram_link_ttl' => (int) env('AI_CHAT_TELEGRAM_LINK_TTL', 30),

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

    // Conversation history used when generating replies for WeCom customers.
    'wecom_history_limit' => (int) env('AI_CHAT_WECOM_HISTORY_LIMIT', 10),

    // Messages containing one of these terms are handed to human support.
    'wecom_handoff_keywords' => ['人工', 'human', '转人工'],

    'wecom_handoff_message' => '已为您转接人工客服，请稍候。',

    'wecom_fallback_message' => '抱歉，我暂时无法回答，将为您转接人工客服，请稍候。',

    // Chunking for the knowledge indexer.
    'chunk_size' => 500,

    'chunk_overlap' => 80,

];
