#!/bin/bash
set -e

SERVER="root@94.241.173.252"
SERVER_PATH="/var/www/narepite-web"

echo "=== 1. Pushing to GitHub ==="
git push

echo ""
echo "=== 2. Pulling on server ==="
ssh $SERVER "cd $SERVER_PATH && git pull"

echo ""
echo "=== 3. Composer install (если composer.lock менялся) ==="
ssh $SERVER "cd $SERVER_PATH && composer install --no-dev --optimize-autoloader --no-interaction"

echo ""
echo "=== 4. Очистка кешей ==="
ssh $SERVER "cd $SERVER_PATH && php artisan optimize:clear"

echo ""
echo "=== 5. Возвращаем владельца apache (важно после работы под root) ==="
ssh $SERVER "chown -R apache:apache $SERVER_PATH"

echo ""
echo "✅ narepite deploy complete!"
