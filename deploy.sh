#!/bin/bash
# ============================================================
# PrintPandora 生产部署脚本
# 由宝塔 WebHook 触发，也可 SSH 手动执行: bash deploy.sh
# ============================================================
set -e

# ---------- 按服务器实际情况调整（仅需改这里） ----------
PROJECT_DIR="/www/wwwroot/printpandora"
PHP_BIN="php"                                   # 宝塔多版本 PHP 时写绝对路径: /www/server/php/84/bin/php
COMPOSER_BIN="composer"
NPM_BIN="npm"
RELOAD_FPM_CMD="/etc/init.d/php-fpm-84 reload"  # 对应 PHP 8.4
# -------------------------------------------------------

cd "$PROJECT_DIR"

echo "[$(date '+%F %T')] 开始部署..."

echo "==> 1/6 拉取最新代码"
# 若以 root 执行 WebHook，需先全局信任该目录（一次性）:
#   git config --global --add safe.directory "$PROJECT_DIR"
git pull origin master

echo "==> 2/6 安装 PHP 依赖（生产模式）"
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction

echo "==> 3/6 安装前端依赖"
$NPM_BIN ci --no-audit --no-fund

echo "==> 4/6 构建前端资源"
# 小内存服务器（<=2G）如构建失败，先加 swap 或改用:
#   NODE_OPTIONS=--max-old-space-size=1024 $NPM_BIN run build
$NPM_BIN run build

echo "==> 5/6 执行数据库迁移（不重置数据）"
$PHP_BIN artisan migrate --force

echo "==> 6/6 重建配置 / 路由 / 视图缓存"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 若启用队列工作进程，取消下一行注释:
# $PHP_BIN artisan queue:restart

$RELOAD_FPM_CMD || true

echo "[$(date '+%F %T')] 部署完成 ✅"
