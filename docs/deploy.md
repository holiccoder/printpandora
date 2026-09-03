# PrintPandora 宝塔（BT Panel）部署指南

> 目标：宝塔面板一键环境 + **git push 自动持续部署**。
> 架构：`git push → GitHub Webhook → 宝塔 WebHook 插件 → 服务器执行 deploy.sh`

***

## 0. 前置要求

| 软件       | 版本               | 说明                                                                                                      |
| -------- | ---------------- | ------------------------------------------------------------------------------------------------------- |
| PHP      | **8.4**（必须 ≥8.4） | 宝塔「软件商店 → PHP-8.4」安装；扩展需含：`pdo_mysql` `mysqli` `mbstring` `openssl` `curl` `fileinfo` `intl` `gd` `zip` |
| Nginx    | 任意稳定版            | 宝塔默认即可                                                                                                  |
| MySQL    | **8.0+**         | 宝塔「软件商店 → MySQL」安装（生产数据库）                                                                               |
| Node.js  | **20+**          | 宝塔「软件商店 → Node 版本管理器」安装                                                                                 |
| Composer | 2.x              | 宝塔软件商店安装，或命令行安装                                                                                         |

生产环境使用 **MySQL**；本地开发环境可继续用 SQLite，互不影响（迁移与 seeder 两者通用）。

### 上传文件限制

产品页的设计文件上传单文件上限为 **75 MB**。Docker 镜像已通过
`docker/php/uploads.ini` 设置 `upload_max_filesize=75M` 和
`post_max_size=80M`。宝塔 PHP-FPM 生产环境也必须在 PHP 8.4 的 `php.ini`
（或对应 PHP-FPM pool 配置）中设置相同值，并重载 PHP-FPM。

Nginx 站点配置还需要允许相同的请求体大小：

```nginx
client_max_body_size 80m;
```

如果生产站点使用 Apache，请将 `LimitRequestBody` 设置为至少 `83886080`
字节，并重载 Apache。

***

## 1. 创建网站

1. 宝塔 →「网站」→「添加站点」：

   * 域名：填你的域名（先解析到服务器 IP）

   * PHP 版本：选 **PHP-84**
2. 站点设置：

   * **运行目录**：`/public`

   * **伪静态**：选 `laravel5`（或填入 `try_files $uri $uri/ /index.php?$query_string;`）
3. SSL：站点设置 → SSL → Let's Encrypt 一键申请 → 强制 HTTPS

***

## 2. 首次部署（手动执行一次）

```bash
# 1. 克隆代码（目录替换为你的站点目录）
cd /www/wwwroot
git clone https://github.com/holiccoder/printpandora.git
cd printpandora

# 2. 创建数据库（宝塔 → 数据库 → 添加数据库）:
#    数据库名: printpandora
#    用户名:   printpandora（建议独立账号，勿用 root）
#    密码:     生成强密码
#    访问权限: 本地服务器（127.0.0.1）
#    字符集:   utf8mb4 / 排序规则 utf8mb4_unicode_ci

# 3. 配置环境
cp .env.example .env
# 编辑 .env，至少修改:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://你的域名
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=printpandora
#   DB_USERNAME=printpandora
#   DB_PASSWORD=上一步的数据库密码
php artisan key:generate

# 4. 安装依赖并构建
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. 初始化数据库（仅此一次！用快照数据填充 MySQL）
php artisan migrate:fresh --seed

# 5. 缓存与权限
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www:www /www/wwwroot/printpandora
chmod -R 775 storage bootstrap/cache

# 6. git 安全目录信任（避免 WebHook 以 root 执行时 pull 失败）
git config --global --add safe.directory /www/wwwroot/printpandora
```

> ⚠️ `migrate:fresh --seed` **只在首次执行**——它会重建数据库。之后部署只用 `migrate --force`（deploy.sh 已如此配置），数据安全。

浏览器访问域名，确认站点正常。

## 3. Laravel 调度任务（计划任务）

宝塔 →「计划任务」→ 添加 Shell 脚本任务，周期 **每分钟**：

```bash
cd /www/wwwroot/printpandora && php artisan schedule:run >> /dev/null 2>&1
```

***

## 4. 持续部署（push 自动发布）

### 4.1 配置宝塔 WebHook 插件

1. 宝塔「软件商店」搜索并安装 **WebHook** 插件
2. 打开插件 →「添加」：

   * 名称：`printpandora-deploy`

   * 执行脚本：粘贴 `deploy.sh` 的内容（或写 `bash /www/wwwroot/printpandora/deploy.sh`）

   * 密钥：自定义一串复杂随机字符串（记下来）
3. 保存后插件会显示完整的 Hook URL，形如：
   `http://面板地址:8888/hook?access_key=你的密钥`
4. **放行端口**：宝塔「安全」+ 云服务器安全组，确认面板端口（默认 8888）对 GitHub 可达

### 4.2 配置 GitHub Webhook

仓库 → **Settings → Webhooks → Add webhook**：

| 字段           | 值                     |
| ------------ | --------------------- |
| Payload URL  | 上一步宝塔给的完整 Hook URL    |
| Content type | `application/json`    |
| Secret       | 宝塔 WebHook 的密钥（如插件要求） |
| 触发事件         | Just the push event   |

### 4.3 验证

1. 本地 `git push` 一个小改动到 `master`
2. GitHub → Webhooks → **Recent Deliveries**：绿色勾 + 200 即成功
3. 宝塔 WebHook 插件 → 查看执行日志，确认 deploy.sh 各步骤输出
4. 网站刷新确认改动生效

***

## 5. 日常运维

| 操作     | 命令 / 位置                                                                          |
| ------ | -------------------------------------------------------------------------------- |
| 手动部署   | `bash /www/wwwroot/printpandora/deploy.sh`                                       |
| 回滚     | `cd /www/wwwroot/printpandora && git fetch origin && git reset --hard <上一个commit>`，然后**手动执行 deploy.sh 的第 2–6 步**（不要直接跑 deploy.sh——它第 1 步会把代码重置回最新 master，回滚会被抵消） |
| 查看部署日志 | 宝塔 WebHook 插件日志面板                                                                |
| 查看应用日志 | `storage/logs/laravel.log`                                                       |

## 6. 故障排查

| 症状                                       | 原因与解法                                                                                            |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------ |
| WebHook 日志报 `detected dubious ownership` | root 操作 www 目录的 git 仓库 → 执行 `git config --global --add safe.directory /www/wwwroot/printpandora` |
| `npm run build` 被杀（Killed）               | 内存不足 → 加 swap，或把 deploy.sh 第 4 步换成注释里的 `NODE_OPTIONS=--max-old-space-size=1024` 版本               |
| 页面 500 / 空白                              | 查 `storage/logs/laravel.log`；多半是 `.env` 或 `storage` 权限问题                                         |
| composer 报 PHP 版本不符                      | 网站设置里 PHP 版本没切到 84；或 CLI 的 php 是旧版 → `deploy.sh` 里 `PHP_BIN` 改绝对路径 `/www/server/php/84/bin/php`  |
| GitHub delivery 超时/403                   | 面板端口未放行 / access\_key 不匹配 / 宝塔防火墙拦截                                                              |

## 7. 备选方案：GitHub Actions + SSH

若宝塔 WebHook 插件不可用，可用 CI 代替（日志在 GitHub 可见）：

```yaml
# .github/workflows/deploy.yml
on:
  push:
    branches: [master]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: bash /www/wwwroot/printpandora/deploy.sh
```

仓库 Settings → Secrets 配置 `SERVER_HOST` / `SERVER_USER` / `SSH_PRIVATE_KEY` 即可。
