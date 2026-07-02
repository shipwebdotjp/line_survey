#!/usr/bin/env bash
set -euo pipefail

IFS=$'\n\t'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REMOTE_HOST="${REMOTE_HOST:-coreserver}"
REMOTE_PATH="${REMOTE_PATH:-/home/shipweb/domains/survey.shipweb.jp}"
DRY_RUN="${DRY_RUN:-0}"

log() {
  printf '[deploy] %s\n' "$*"
}

require_command() {
  local command_name="$1"
  if ! command -v "$command_name" >/dev/null 2>&1; then
    log "missing required command: $command_name"
    exit 1
  fi
}

run() {
  log "+ $*"
  "$@"
}

build_public_html() {
  local staging_public_html="$1"

  rm -rf "$staging_public_html"
  mkdir -p "$staging_public_html/api"

  run npm --prefix "$ROOT_DIR/frontend" ci
  run npm --prefix "$ROOT_DIR/frontend" run build:staging -- --outDir "$staging_public_html"

  if [ ! -f "$ROOT_DIR/public_html/.htaccess" ]; then
    log "public_html/.htaccess is missing"
    exit 1
  fi

  cat > "$staging_public_html/.htaccess" <<'PHP'
RewriteEngine On

# Route /api and /api/* to public_html/api/index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api(?:/(.*))?$ api/index.php [QSA,L]

# For frontend (React router support)
# Exclude /api and /api/* from SPA catch-all
RewriteCond %{REQUEST_URI} !^/api(?:/|$)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [QSA,L]

# Note: BasicAuth is intentionally absent. The PHP AdminAuthMiddleware is the
# sole auth path for the management API. See openspec/changes/admin-to-manage.

PHP

  cat > "$staging_public_html/api/index.php" <<'PHP'
<?php

require __DIR__ . '/../../backend/public/index.php';
PHP
}

stage_backend() {
  local staging_backend="$1"

  rm -rf "$staging_backend"
  mkdir -p "$staging_backend"

  rsync -a \
    --exclude '.env' \
    --exclude '.env.*' \
    --exclude 'storage/' \
    --exclude 'tests/' \
    --exclude '.phpunit.result.cache' \
    --exclude '.php-cs-fixer.cache' \
    --exclude '.phpstan/' \
    "$ROOT_DIR/backend/" \
    "$staging_backend/"
}

sync_to_remote() {
  local staging_root="$1"
  local remote_target="${REMOTE_HOST}:${REMOTE_PATH%/}/"
  local rsync_args=(
    -az
    --delete
    --exclude '.env'
    --exclude '.env.*'
    --exclude '.htpasswd'
    --exclude '.htpasswd.prod'
    --exclude 'backend/.env'
    --exclude 'backend/.env.*'
    --exclude 'backend/storage/'
    --exclude 'backend/tests/'
  )

  if [ "$DRY_RUN" = "1" ]; then
    rsync_args+=(--dry-run --itemize-changes)
  fi

  run rsync "${rsync_args[@]}" "$staging_root/" "$remote_target"
}

main() {
  require_command make
  require_command npm
  require_command rsync
  require_command ssh
  require_command docker

  if [ ! -f "$ROOT_DIR/backend/public/index.php" ]; then
    log "backend/public/index.php is missing"
    exit 1
  fi

  local staging_root
  staging_root="$(mktemp -d "${TMPDIR:-/tmp}/line-solver-deploy.XXXXXX")"
  chmod 711 "$staging_root"
  trap 'rm -rf "$staging_root"' EXIT

  log "staging directory: $staging_root"
  run docker compose -f "$ROOT_DIR/docker-compose.yml" up -d --build
  run make -C "$ROOT_DIR" composer-install

  stage_backend "$staging_root/backend"
  build_public_html "$staging_root/public_html"

  if [ ! -f "$staging_root/public_html/index.html" ]; then
    log "frontend build did not produce public_html/index.html"
    exit 1
  fi

  if [ ! -f "$staging_root/public_html/api/index.php" ]; then
    log "api entrypoint was not generated"
    exit 1
  fi

  sync_to_remote "$staging_root"
  log "deployment complete"
}

main "$@"
