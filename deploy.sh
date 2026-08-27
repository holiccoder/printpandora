#!/bin/bash
# ============================================================
# PrintPandora 生产部署脚本
# 由宝塔 WebHook 触发，也可 SSH 手动执行: bash deploy.sh
# ============================================================
set -e

# ---------- 按服务器实际情况调整（仅需改这里） ----------
PROJECT_DIR="/www/wwwroot/www.inkpavo.com"
PHP_BIN="php"                                   # 宝塔多版本 PHP 时写绝对路径: /www/server/php/84/bin/php
COMPOSER_BIN="composer"
NPM_BIN="npm"
WEB_USER="www"
WEB_GROUP="www"
RELOAD_FPM_CMD="/etc/init.d/php-fpm-84 reload"  # 对应 PHP 8.4
# Ensure we use a modern Node.js version (Vite requires Node.js 20.19+ or 22.12+)
export PATH="/www/server/nodejs/v24.17.0/bin:$PATH"
# -------------------------------------------------------

cd "$PROJECT_DIR"

echo "[$(date '+%F %T')] 开始部署..."

echo "==> 1/6 拉取最新代码"
# 若以 root 执行 WebHook，需先全局信任该目录（一次性）:
#   git config --global --add safe.directory "$PROJECT_DIR"
#
# 使用 fetch + reset --hard（而非 git pull）：
# 服务器被视为仓库的镜像，任何服务器本地的未提交改动都会被丢弃，
# 避免 "Please commit your changes or stash them before you merge"
# 这类错误卡住持续部署。注意：请勿在服务器上直接改文件！
BEFORE=$(git rev-parse HEAD)
git fetch origin master
git reset --hard origin/master

echo "==> 2/6 PHP 依赖（composer.lock 变化或 vendor 缺失时才安装）"
if [ ! -d vendor ] || ! git diff --quiet "$BEFORE" HEAD -- composer.lock; then
    $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction
else
    echo "    composer.lock 无变化，跳过"
fi

echo "==> 3/6 前端依赖（package-lock.json 变化或 node_modules 缺失时才安装）"
if [ ! -d node_modules ] || ! git diff --quiet "$BEFORE" HEAD -- package-lock.json; then
    $NPM_BIN ci --no-audit --no-fund
else
    echo "    package-lock.json 无变化，跳过"
fi

echo "==> 4/6 构建前端资源（始终执行——源码变了就要重新构建）"
# 小内存服务器（<=2G）如构建失败，先加 swap 或改用:
#   NODE_OPTIONS=--max-old-space-size=1024 $NPM_BIN run build
$NPM_BIN run build

echo "==> 5/6 执行数据库迁移（不重置数据）"
$PHP_BIN artisan migrate --force
$PHP_BIN artisan db:seed --class=BusinessCardProductOptionsSeeder --force
$PHP_BIN artisan db:seed --class=HelpCenterSeeder --force

echo "==> 6/6 重建配置 / 路由 / 视图与优化缓存"
$PHP_BIN artisan optimize
$PHP_BIN artisan view:cache

echo "==> 7/7 Fix Laravel runtime directory permissions"
mkdir -p "$PROJECT_DIR/storage/logs" "$PROJECT_DIR/bootstrap/cache"

if [ "$(id -u)" -eq 0 ]; then
    chown -R "${WEB_USER}:${WEB_GROUP}" \
        "$PROJECT_DIR/storage" \
        "$PROJECT_DIR/bootstrap/cache"

    find "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" \
        -type d -exec chmod 775 {} \;
    find "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" \
        -type f -exec chmod 664 {} \;
else
    sudo chown -R "${WEB_USER}:${WEB_GROUP}" \
        "$PROJECT_DIR/storage" \
        "$PROJECT_DIR/bootstrap/cache"

    sudo find "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" \
        -type d -exec chmod 775 {} \;
    sudo find "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" \
        -type f -exec chmod 664 {} \;
fi

if command -v sudo >/dev/null 2>&1; then
    if ! sudo -u "$WEB_USER" test -w "$PROJECT_DIR/storage/logs"; then
        echo "storage/logs is not writable by ${WEB_USER}" >&2
        exit 1
    fi
else
    if ! su -s /bin/sh "$WEB_USER" -c "test -w '$PROJECT_DIR/storage/logs'"; then
        echo "storage/logs is not writable by ${WEB_USER}" >&2
        exit 1
    fi
fi

# 若启用队列工作进程，取消下一行注释:
# $PHP_BIN artisan queue:restart

$RELOAD_FPM_CMD || true

echo "[$(date '+%F %T')] 部署完成 ✅"
