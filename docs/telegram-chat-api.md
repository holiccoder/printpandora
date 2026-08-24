# External Support Chat API and Telegram Bot

This manual explains how to connect a separate admin client to the support chat
and deliver administrator replies through Telegram.

## How it works

The application uses one Telegram bot for all customers. Each Telegram chat is
associated with one `AiChatConversation` through its Telegram `chat_id`.

```text
Customer Telegram chat
        │
        ▼
Telegram webhook → conversation + customer message
        │
        ▼
Separate admin client → support API → Telegram sendMessage
```

The Telegram bot token stays on the application server. The external admin
client authenticates with the support API using a Bearer token.

## Environment configuration

Add these values to `.env`:

```env
# Token used by the separate admin/support client.
AI_CHAT_API_TOKEN=generate_a_long_random_value

# Telegram bot settings.
TELEGRAM_BOT_TOKEN=123456789:replace_with_token_from_botfather
TELEGRAM_BOT_USERNAME=your_bot_username_without_at_sign
TELEGRAM_WEBHOOK_SECRET=generate_a_long_random_value
TELEGRAM_TIMEOUT=10

# Internal operator chat for website human-support notifications.
# Use a private admin chat ID or a Telegram supergroup ID (usually starts with -100).
TELEGRAM_SUPPORT_CHAT_ID=
# Comma-separated Telegram user IDs allowed to reply to support notifications.
TELEGRAM_SUPPORT_USER_IDS=

# Lifetime of a website-to-Telegram linking URL, in minutes.
AI_CHAT_TELEGRAM_LINK_TTL=30
```

Do not commit these values or expose `TELEGRAM_BOT_TOKEN` to a browser or
third-party client. After changing `.env`, refresh configuration:

```bash
php artisan config:clear
```

In production, use `php artisan config:cache` after verifying the values.

## Create and configure the Telegram bot

1. Open `@BotFather` in Telegram.
2. Run `/newbot` and follow the prompts.
3. Copy the generated bot token into `TELEGRAM_BOT_TOKEN`.
4. Copy the bot username without the `@` into `TELEGRAM_BOT_USERNAME`.
5. Set a random webhook secret containing only letters, numbers, `_`, or `-`.

Run the migration before receiving messages:

```bash
php artisan migrate --force
```

The application must be reachable from the public internet over HTTPS. Register
the webhook with Telegram:

```bash
curl -X POST "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://<APP_DOMAIN>/api/telegram/webhook","secret_token":"<WEBHOOK_SECRET>","allowed_updates":["message"]}'
```

The webhook URL implemented by this application is:

```text
POST https://<APP_DOMAIN>/api/telegram/webhook
Header: X-Telegram-Bot-Api-Secret-Token: <WEBHOOK_SECRET>
```

Telegram sends incoming updates to the webhook and retries unsuccessful
deliveries. The application records the Telegram update ID to avoid processing
the same delivery more than once.

Official references:

- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Telegram webhooks and `setWebhook`](https://core.telegram.org/bots/api#setwebhook)
- [Telegram deep linking](https://core.telegram.org/bots/features#deep-linking)

## Website customer messages in an operator Telegram chat

When a website conversation is in human mode, each customer message is sent
to TELEGRAM_SUPPORT_CHAT_ID. The notification contains the numeric website
conversation ID. An operator must reply to that exact Telegram notification
message; the webhook uses the reply_to_message.message_id to route the reply
back to the matching website conversation.

Only Telegram users listed in TELEGRAM_SUPPORT_USER_IDS can send operator
replies. This allows one Telegram group to handle many conversations without
mixing messages. The website frontend receives the saved administrator message
through its existing conversation polling flow.

This bridge is intentionally enabled for human-support messages only. AI-mode
messages continue to receive their DeepSeek/OpenAI response in the website
chat. A customer can be switched to human mode through the existing handoff
flow.

## External admin API

All endpoints below require:

```http
Authorization: Bearer <AI_CHAT_API_TOKEN>
Accept: application/json
```

The API is rate-limited to 60 requests per minute per client.

### List conversations

```http
GET /api/v1/support/chat/conversations?limit=50
```

Example:

```bash
curl "https://<APP_DOMAIN>/api/v1/support/chat/conversations?limit=50" \
  -H "Authorization: Bearer <AI_CHAT_API_TOKEN>"
```

Each conversation includes its numeric `id`, mode, latest message, waiting
status, and Telegram connection details:

```json
{
  "id": 42,
  "mode": "human",
  "waiting": true,
  "telegram": {
    "connected": true,
    "chat_id": "987654321",
    "username": "customer_name",
    "link_pending": false
  }
}
```

Use the conversation `id` for subsequent message and reply requests. Different
Telegram chats have different conversation IDs and are routed independently.

### Read messages

```http
GET /api/v1/support/chat/conversations/{conversation_id}/messages?after_id=0&limit=100
```

`after_id` allows a client to poll only messages newer than the last one it has
received.

```bash
curl "https://<APP_DOMAIN>/api/v1/support/chat/conversations/42/messages?after_id=0" \
  -H "Authorization: Bearer <AI_CHAT_API_TOKEN>"
```

### Send a reply

```http
POST /api/v1/support/chat/conversations/{conversation_id}/reply
Content-Type: application/json

{"message":"Your order is being prepared."}
```

```bash
curl -X POST "https://<APP_DOMAIN>/api/v1/support/chat/conversations/42/reply" \
  -H "Authorization: Bearer <AI_CHAT_API_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"message":"Your order is being prepared."}'
```

When the conversation is connected to Telegram, the server calls Telegram's
`sendMessage` API with that conversation's `chat_id`, then saves the admin
message. The response includes `telegram_delivered` and
`telegram_message_id`.

If Telegram delivery fails, the endpoint returns `502` and does not save the
admin message as successfully delivered. The client can retry the reply.

### Generate a Telegram link for an existing conversation

```http
POST /api/v1/support/chat/conversations/{conversation_id}/telegram-link
```

```bash
curl -X POST "https://<APP_DOMAIN>/api/v1/support/chat/conversations/42/telegram-link" \
  -H "Authorization: Bearer <AI_CHAT_API_TOKEN>"
```

The response contains a short-lived URL like:

```text
https://t.me/your_bot?start=<one-time-token>
```

Send this URL to the customer. When the customer opens it and taps **Start**,
Telegram sends `/start <one-time-token>` to the webhook. The application then
links that Telegram chat to conversation `42`.

Telegram deep-link parameters are designed for passing an authentication or
linking token when a user starts a bot conversation.

### Switch conversation mode

Take over an AI conversation as a human conversation:

```http
POST /api/v1/support/chat/conversations/{conversation_id}/takeover
```

Return a conversation to AI mode:

```http
POST /api/v1/support/chat/conversations/{conversation_id}/resolve
```

These endpoints also use the Bearer token and return the new `mode`.

## Customer flows

### Existing website conversation

1. The admin client lists conversations.
2. It calls `telegram-link` for the selected conversation.
3. It sends the returned URL to the customer.
4. The customer taps **Start** in Telegram.
5. Future Telegram messages appear in the same conversation.
6. Replies sent through the admin API go back to that Telegram chat.

### New Telegram conversation

1. The customer opens the bot directly and taps **Start**, or sends a message.
2. The webhook creates a new human support conversation.
3. The admin client polls the conversations endpoint.
4. The admin replies using the returned conversation ID.

The bot must be started by the customer before the application can associate a
Telegram chat and send messages to it.

## Troubleshooting

Check the webhook status:

```bash
curl "https://api.telegram.org/bot<BOT_TOKEN>/getWebhookInfo"
```

Common causes of failure:

- `TELEGRAM_BOT_TOKEN` is incorrect or has whitespace around it.
- `TELEGRAM_BOT_USERNAME` includes `@`; store only the username.
- The application URL is not publicly reachable over HTTPS.
- The configured webhook secret does not match `TELEGRAM_WEBHOOK_SECRET`.
- The migration has not been run.
- The external client is missing the `Authorization: Bearer ...` header.
- The customer has not opened the bot and pressed **Start**.
