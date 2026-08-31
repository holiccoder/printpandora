# WeCom WeChat Customer Service API

This application can receive customers through Enterprise WeChat's WeChat
Customer Service (微信客服, `kf`) API. Customer messages create a local
`AiChatConversation` in AI mode, receive a non-streaming AI reply, and can be
handed over to human support. Existing Telegram conversations and the Telegram
operator bridge remain unchanged.

## How it works

```text
WeCom customer
       │ encrypted kf_msg_or_event callback
       ▼
/api/wecom/kf/callback ── queue ── sync_msg
       │                         │
       │                         ├─ AI reply ── send_msg
       │                         └─ human mode ── existing Filament/REST API
       ▼
ai_chat_conversation + ai_chat_messages
```

The callback only acknowledges the notification after signature verification
and decryption. Message bodies are retrieved asynchronously through
`sync_msg`; the callback does not contain the customer message itself.

## Environment configuration

Add these values to the application server's `.env`:

```env
# Enterprise WeChat / WeChat Customer Service API.
WECOM_CORP_ID=ww_replace_with_corp_id
WECOM_KF_SECRET=replace_with_wechat_customer_service_secret
WECOM_CALLBACK_TOKEN=generate_a_long_random_value
WECOM_ENCODING_AES_KEY=43_character_encoding_aes_key
WECOM_OPEN_KFID=wk_replace_with_default_customer_service_account_id
WECOM_TIMEOUT=10

# Number of previous local messages supplied to the WeCom AI prompt.
AI_CHAT_WECOM_HISTORY_LIMIT=10

# The AI provider/model and knowledge-base settings are shared with website
# support chat.
AI_CHAT_ENABLED=true
AI_CHAT_PROVIDER=openai
AI_CHAT_MODEL=gpt-4o-mini
```

Keep the corp secret, callback token, and EncodingAESKey server-side. After
changing `.env`, refresh configuration:

```bash
php artisan config:clear
php artisan migrate --force
```

In production, use `php artisan config:cache` after verifying the values.

## Configure the WeCom customer service account

In the Enterprise WeChat admin console:

1. Open **应用管理 → 微信客服** and enable API access.
2. Copy the **微信客服 secret** into `WECOM_KF_SECRET`.
3. In the callback settings, configure the URL as:

   ```text
   https://<APP_DOMAIN>/api/wecom/kf/callback
   ```

4. Enter the same callback token and 43-character EncodingAESKey as
   `WECOM_CALLBACK_TOKEN` and `WECOM_ENCODING_AES_KEY`.
5. Copy the target customer-service account ID (`open_kfid`) into
   `WECOM_OPEN_KFID`.
6. Set the account to API management in **通过 API 管理微信客服帐号**. Only
   accounts managed by API can be read with `sync_msg` and answered with
   `send_msg`.

The URL must be reachable over HTTPS. The application supports the one-time
GET validation request and encrypted POST notifications. The GET validation
must succeed before the admin console accepts the callback URL.

## Customer flow and handoff

The first customer message automatically creates a WeCom channel whose
`external_chat_id` is the WeCom `external_userid`, plus a local conversation in
`mode=ai`.

By default, a message containing `人工`, `human`, or `转人工` changes the
conversation to `mode=human` and sends a handoff notice. An administrator can
also use the existing Filament chat controls or REST takeover endpoint. In
human mode, customer messages are stored but do not trigger an AI reply.

When an administrator resolves the conversation, it returns to AI mode and
future WeCom messages can be answered automatically.

## Replies from Filament and the support REST API

The existing reply endpoints now dispatch through the conversation's first
connected channel. They continue to work for Telegram and can also send to a
WeCom conversation:

```http
POST /api/v1/support/chat/conversations/{conversation_id}/reply
Authorization: Bearer <AI_CHAT_API_TOKEN>
Content-Type: application/json

{"message":"We are checking this for you."}
```

The response includes the provider-neutral fields `channel_delivered` and
`channel_message_id`. The legacy `telegram_delivered` and
`telegram_message_id` fields remain for existing Telegram clients; they are
false/null for a WeCom conversation.

## Queue worker requirement

Both callback synchronization and AI replies are queued. Run a worker in every
production environment:

```bash
php artisan queue:work --tries=3 --timeout=120
```

The synchronization job uses a shared cache lock so a burst of callback
notifications does not run concurrent `sync_msg` pulls. Its cursor is stored
under `wecom:kf:cursor`; do not use an ephemeral cache store in production.

## Operational limits and current scope

- `send_msg` can reply only within 48 hours after the customer's last
  message. WeCom errors such as `95001` are logged and the queue/API caller
  sees the delivery failure.
- Text messages are fully supported.
- Image, file, link, location, and other non-text messages are stored as a
  placeholder such as `[WeCom image message received]`. Binary media upload
  and two-way media forwarding are intentionally deferred.
- Existing website conversations cannot currently be deep-linked to a WeCom
  customer. WeCom customers entering through the customer-service entry point
  are automatically assigned a new local conversation.
- A real-account smoke test should verify URL validation, callback reachability,
  API-managed account permissions, the worker, and the 48-hour reply window.

## Troubleshooting

- A GET validation failure usually means the callback token, EncodingAESKey,
  corp ID, or callback URL is mismatched.
- A callback that returns `success` but creates no message should be checked
  in the queue worker logs and in the cached `wecom:kf:cursor` state.
- `sync_msg` requires the WeChat Customer Service secret's access token and an
  API-managed `open_kfid`; do not substitute an ordinary application secret.
- A `send_msg` delivery failure may mean the customer has passed the 48-hour
  reply window, the account is not API-managed, or the application lacks the
  WeChat Customer Service permission.

References:

- [WeCom WeChat Customer Service overview](https://developer.work.weixin.qq.com/document/path/94638)
- [WeCom WeChat Customer Service callback](https://developer.work.weixin.qq.com/document/path/94670)
- [WeCom WeChat Customer Service sync_msg](https://developer.work.weixin.qq.com/document/path/94745)
