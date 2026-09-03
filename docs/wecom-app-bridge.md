# Website conversations ↔ WeCom self-built application

This bridge sends customer messages from website conversations in `human` mode
to selected Enterprise WeChat (WeCom) support members. A support member replies
with a text message in WeCom, and the reply is saved as an `admin` message for
the website conversation. The existing frontend polling endpoint then displays
it to the customer.

The bridge is separate from the WeCom WeChat Customer Service (`kf`) API. It
uses the self-built application's own access token and does not use the kf
48-hour reply window.

## Environment

Add the following values to the server `.env` file:

```env
WECOM_CORP_ID=ww_replace_with_corp_id

# Self-built application credentials.
WECOM_APP_AGENT_ID=1000002
WECOM_APP_SECRET=replace_with_self_built_application_secret
# Comma-separated WeCom member user IDs.
WECOM_APP_SUPPORT_USER_IDS=zhangsan,lisi

# Optional. Empty values fall back to the kf callback credentials.
WECOM_APP_CALLBACK_TOKEN=
WECOM_APP_ENCODING_AES_KEY=
```

`WECOM_APP_CALLBACK_TOKEN` and `WECOM_APP_ENCODING_AES_KEY` may reuse
`WECOM_CALLBACK_TOKEN` and `WECOM_ENCODING_AES_KEY`. The application access
token is cached separately under `wecom:app:access_token`.

After changing the environment, refresh configuration and run the migration:

```bash
php artisan config:clear
php artisan migrate --force
```

Run a queue worker in production because website notifications and WeCom
operator replies are queued:

```bash
php artisan queue:work --tries=3 --timeout=120
```

## WeCom admin-console setup

1. Open **应用管理 → 创建自建应用** and create an application such as
   “网站客服桥”. Copy its `AgentId` and `Secret` to the environment values.
2. In the application's **接收消息** settings, configure the callback URL:

   ```text
   https://<APP_DOMAIN>/api/wecom/app/callback
   ```

3. Set the callback `Token` and `EncodingAESKey`. They can be the same values
   used by the kf callback when the optional app variables are left empty.
4. Add the support members to the application's visible range. Their WeCom
   member account IDs (the `userid` values) go into
   `WECOM_APP_SUPPORT_USER_IDS`.
5. Confirm that the application has permission to send messages.

The callback URL must be reachable over HTTPS. WeCom first sends an encrypted
GET validation request. Only text messages from the configured application
agent are accepted; other message types are acknowledged and ignored.

## Operator reply syntax

Use an explicit conversation ID when replying:

```text
#123 We are checking your order now.
```

The numeric ID is the website `AiChatConversation` ID. The `#123` prefix is
removed before the reply is saved. The bridge sends a confirmation to the
operator:

```text
已发送到会话 #123
```

After a notification, that conversation becomes the support member's active
conversation for seven days. A reply without a prefix is routed to that active
conversation:

```text
I have checked the order status.
```

If there is no active conversation, the application asks the operator to use
the explicit `#会话ID 内容` form. Each inbound WeCom `MsgId` is recorded, so a
callback retry cannot create a second website message.

## Website flow

1. A customer selects human support or sends a message while the conversation
   is already in human mode.
2. The website queues a notification. The bridge sends both a text message and
   a text card to every configured support member. The card opens the existing
   Filament conversation view.
3. The operator replies in WeCom with `#<conversation id> <content>` or uses
   their active conversation.
4. The callback acknowledges immediately and queues the operator message.
5. The job stores an `admin` message, switches an AI-mode conversation to human
   mode, and refreshes the website's existing polling view.

## Scope and troubleshooting

- Text operator replies are supported. Image and file forwarding from WeCom to
  website customers is intentionally not implemented.
- This bridge does not use a group robot webhook.
- Ending a human session or returning it to AI remains in the existing Filament
  resolve flow.
- A valid callback GET response confirms the Token, EncodingAESKey, corp ID,
  and URL. A 403 normally means one of those values is mismatched.
- If WeCom accepts the callback but no website message appears, check the queue
  worker and the application logs, then verify that `AgentID` and the member's
  `userid` are configured correctly.
- If notifications do not arrive, check `WECOM_APP_AGENT_ID`,
  `WECOM_APP_SECRET`, `WECOM_APP_SUPPORT_USER_IDS`, application visibility, and
  the application's send-message permission.

## Real-account smoke test

Verify the following in order:

1. WeCom GET callback validation succeeds.
2. A website conversation is handed off to human mode.
3. Every configured support member receives the text and card.
4. Replying with `#<id> 内容` creates an administrator message.
5. The website frontend poll returns that message to the customer.
6. The card URL opens the Filament conversation view for an authenticated
   administrator.
