# 网站会话 ↔ 飞书员工通知/回复桥

此桥把网站 AI 客服进入 `human` 模式后的客户消息推送给飞书客服人员，
客服在飞书机器人会话中直接回复，回复会保存为网站会话的 `admin` 消息，
网站前台现有的轮询接口会把它展示给客户。

本功能是员工通知桥，不是客户侧飞书 AI 渠道：不新增
`AiChatChannel provider`，也不修改 `ChatReplyDispatcher`。

## 环境变量

在生产环境 `.env` 中配置：

```env
FEISHU_APP_ID=cli_replace_with_app_id
FEISHU_APP_SECRET=replace_with_app_secret
FEISHU_VERIFICATION_TOKEN=replace_with_verification_token
# 可选。填写后回调会启用签名校验和 AES-256-CBC 解密。
FEISHU_ENCRYPT_KEY=
FEISHU_TIMEOUT=10
FEISHU_BASE_URL=https://open.feishu.cn/open-apis
# 逗号分隔的客服 open_id。
FEISHU_SUPPORT_OPEN_IDS=ou_xxx,ou_yyy
```

`FEISHU_APP_ID`、`FEISHU_APP_SECRET` 和至少一个
`FEISHU_SUPPORT_OPEN_IDS` 是发送通知的必要配置。Verification Token 用于
未启用 Encrypt Key 时验证回调；Encrypt Key 可选，但如果飞书后台启用了它，
必须与 `.env` 完全一致。客服人员的 `open_id` 可通过飞书开放平台 API 调试
工具批量查询。

修改环境变量后刷新配置并执行迁移：

```bash
php artisan config:clear
php artisan migrate --force
```

生产环境必须运行队列 worker：

```bash
php artisan queue:work --tries=3 --timeout=120
```

## 飞书开放平台配置

1. 创建企业自建应用并启用「机器人」能力。
2. 授予应用权限：`im:message`、`im:message:send_as_bot`。
3. 在「事件订阅」中选择「将回调发送至开发者服务器」，不要使用长连接模式。
4. 订阅事件 `im.message.receive_v1`。
5. 请求地址填写：

   ```text
   https://<生产环境域名>/api/feishu/callback
   ```

   该域名必须是网站前台使用的域名，并与 `.env` 的 `APP_URL` 一致；必须
   使用 HTTPS，不带端口和尾斜杠。例如：
   `https://www.example.com/api/feishu/callback`。
6. 复制 App ID、App Secret、Verification Token 和可选 Encrypt Key 到环境变量。
7. 把客服人员加入应用可见范围，并把他们的 `open_id` 配置到
   `FEISHU_SUPPORT_OPEN_IDS`。

必须先部署包含本回调控制器的代码并刷新生产配置，再在飞书后台保存回调地址。
保存时飞书会立即发送 `url_verification` challenge；控制器会在验证通过后原样
返回 `{"challenge":"..."}`。

飞书事件回调只要求公网可达的 HTTPS，不校验域名 ICP 备案。是否需要备案取决于
服务器所在地；本项目服务器在境外，因此接入飞书不需要 ICP 备案。

## 回调验证与支持范围

- 未配置 Encrypt Key：校验 JSON 顶层 `token` 是否等于
  `FEISHU_VERIFICATION_TOKEN`。
- 配置 Encrypt Key：校验请求头
  `X-Lark-Signature`、`X-Lark-Request-Timestamp` 和
  `X-Lark-Request-Nonce`，签名算法为
  `SHA256(timestamp + nonce + EncryptKey + rawBody)`，然后解密
  `encrypt` 字段。
- 只处理 `im.message.receive_v1` 的 `text` 消息。
- `sender_type=app` 的机器人自身消息会忽略，避免回执再次触发桥接。
- 所有已验证但不支持的事件都会立即返回 `{"code":0}`，避免飞书重试。

## 回复语法

推荐显式指定会话：

```text
#123 我们正在帮你核实订单。
```

其中 `123` 是网站 `AiChatConversation` 的 ID，`#123` 前缀不会保存到客户消息中。
处理成功后机器人会回执：

```text
已发送到会话 #123
```

每次网站通知都会把该会话设为所有客服的活跃会话，缓存有效期为 7 天。因此也
可以直接回复：

```text
我已经核实了订单状态。
```

没有活跃会话时，机器人会提示使用 `#会话ID 内容`。每个入站
`message_id` 只处理一次，飞书回调重试不会生成重复网站消息。客服回复 AI 模式
会话时，系统会自动切换到 `human` 模式。

## 网站消息流程

1. 客户选择人工客服，或在已有人工模式会话中发送消息。
2. 网站将通知任务加入队列，机器人向每个配置的客服发送文本和 interactive 卡片。
3. 卡片中的「去回复」按钮打开现有 Filament 会话页。
4. 客服在飞书中回复，回调立即确认并将处理任务加入队列。
5. 队列任务创建 `admin` 消息，网站前台轮询后显示给客户。

## 手动联调

按以下顺序验证：

1. 代码部署完成，`FEISHU_*` 与 `APP_URL` 已配置，执行
   `php artisan config:clear` 或 `php artisan config:cache`。
2. 在飞书后台保存回调 URL，确认 challenge 验证通过。
3. 客户在网站点击转人工并发送消息。
4. 确认每个客服收到文本通知和卡片。
5. 客服回复 `#<会话ID> 内容`。
6. 确认网站 poll 端点返回新的 `admin` 消息。
7. 确认卡片链接能由已登录管理员打开 Filament 会话页。

## 排错

- 保存回调地址时报「Challenge code 没有返回」：通常是代码还未部署，或接口
  返回了 404。先用以下命令确认端点可达；无 body 返回 403 属于正常的未验证请求：

  ```bash
  curl -X POST https://<域名>/api/feishu/callback
  ```

- 仍然无法验证时，检查 `FEISHU_VERIFICATION_TOKEN` 是否与事件订阅页完全一致。
  如果后台启用了 Encrypt Key，检查 `FEISHU_ENCRYPT_KEY` 是否一致。
- 确认生产配置缓存已刷新，并确认 CDN/WAF 没有拦截 POST 或移除 Lark 请求头。
- 回调已接受但网站没有出现回复：检查队列 worker、应用日志、客服
  `open_id` 和 `FEISHU_SUPPORT_OPEN_IDS`。
- 通知没有到达：检查 App ID、App Secret、机器人能力、应用可见范围以及
  `im:message:send_as_bot` 权限。
- 机器人能够发通知但无法收到员工回复：确认订阅的是
  `im.message.receive_v1`，且回调 URL 使用的是 webhook 模式而不是长连接模式。
