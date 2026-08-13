#!/bin/bash
# cPanel Git Version Control runs this from the repository root.
# Keep it fast: rsync (not cp -R), never copy .git/vendor, skip composer when
# composer.lock is unchanged, and do not chmod -R the live storage tree.
set -euo pipefail

DEPLOYPATH="${DEPLOYPATH:-/home/voxsignco/pearledu-app}"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "==> PearlEdu deploy $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
echo "    source: $REPO_ROOT"
echo "    target: $DEPLOYPATH"
if git -C "$REPO_ROOT" rev-parse --short HEAD >/dev/null 2>&1; then
    echo "    commit: $(git -C "$REPO_ROOT" rev-parse --short HEAD) ($(git -C "$REPO_ROOT" log -1 --pretty=%s))"
fi

mkdir -p "$DEPLOYPATH"

RSYNC_EXCLUDES=(
    --exclude '.git/'
    --exclude 'vendor/'
    --exclude 'node_modules/'
    --exclude '.env'
    --exclude '.env.*'
    --exclude 'storage/'
    --exclude 'bootstrap/cache/'
    --exclude 'public/storage'
    --exclude 'public/hot'
    --exclude 'public/build/'
    --exclude 'tests/'
    --exclude 'docs/'
    --exclude '.phpunit.cache/'
    --exclude '.pgsql/'
    --exclude '.cursor/'
)

if command -v rsync >/dev/null 2>&1; then
    echo "==> rsync application files"
    rsync -a --delete "${RSYNC_EXCLUDES[@]}" "$REPO_ROOT"/ "$DEPLOYPATH"/
else
    echo "==> rsync missing; tar fallback (will not delete stale files)"
    tar -C "$REPO_ROOT" \
        --exclude='.git' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='.env' \
        --exclude='storage' \
        --exclude='bootstrap/cache' \
        --exclude='tests' \
        --exclude='docs' \
        -cf - . | tar -C "$DEPLOYPATH" -xf -
fi

mkdir -p \
    "$DEPLOYPATH/storage/app/public" \
    "$DEPLOYPATH/storage/app/private" \
    "$DEPLOYPATH/storage/framework/cache/data" \
    "$DEPLOYPATH/storage/framework/sessions" \
    "$DEPLOYPATH/storage/framework/views" \
    "$DEPLOYPATH/storage/logs" \
    "$DEPLOYPATH/bootstrap/cache"

# Directories only — never chmod -R live logs/sessions (that alone can take minutes).
chmod 775 \
    "$DEPLOYPATH/storage" \
    "$DEPLOYPATH/storage/logs" \
    "$DEPLOYPATH/storage/framework" \
    "$DEPLOYPATH/storage/framework/cache" \
    "$DEPLOYPATH/storage/framework/sessions" \
    "$DEPLOYPATH/storage/framework/views" \
    "$DEPLOYPATH/bootstrap/cache" || true

cd "$DEPLOYPATH"

export COMPOSER_MEMORY_LIMIT="${COMPOSER_MEMORY_LIMIT:-512M}"
export COMPOSER_PROCESS_TIMEOUT="${COMPOSER_PROCESS_TIMEOUT:-600}"

LOCK_HASH="$(sha1sum composer.lock | awk '{print $1}')"
STAMP="$DEPLOYPATH/vendor/.composer-lock-sha"

if [ ! -f vendor/autoload.php ] || [ ! -f "$STAMP" ] || [ "$(cat "$STAMP")" != "$LOCK_HASH" ]; then
    echo "==> composer.lock changed (or vendor missing); composer install"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress --no-ansi
    mkdir -p vendor
    echo "$LOCK_HASH" > "$STAMP"
else
    echo "==> composer.lock unchanged; skipping composer install"
fi

echo "==> artisan migrate + caches"
php artisan db:verify-security
php artisan migrate --force
php artisan app:production-check || echo "WARN: production-check reported issues; files are still deployed."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo "==> deploy finished $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
