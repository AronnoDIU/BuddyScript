#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"
BACKEND_HOST="${BACKEND_HOST:-127.0.0.1}"
BACKEND_PORT="${BACKEND_PORT:-8000}"
FRONTEND_HOST="${FRONTEND_HOST:-127.0.0.1}"
FRONTEND_PORT="${FRONTEND_PORT:-5173}"

select_php_binary() {
  if [[ -n "${PHP_BIN:-}" ]]; then
    echo "$PHP_BIN"
    return 0
  fi

  if [[ -n "${PHP_BINARY:-}" ]]; then
    echo "$PHP_BINARY"
    return 0
  fi

  if command -v php8.5 >/dev/null 2>&1; then
    command -v php8.5
    return 0
  fi

  if command -v php >/dev/null 2>&1; then
    command -v php
    return 0
  fi

  return 1
}

require_php_driver() {
  local php_bin="$1"

  if ! "$php_bin" -m 2>/dev/null | grep -qx 'pdo_mysql'; then
    echo ""
    echo "The PHP CLI at '$php_bin' does not have the pdo_mysql extension enabled."
    echo "BuddyScript uses MySQL for Doctrine migrations, so the dev server cannot start until that driver is installed."
    echo ""
    echo "On Debian/Ubuntu, for example, install the PHP 8.5 MySQL package:"
    echo "  sudo apt install php8.5-mysql"
    echo ""
    echo "Then verify it with:"
    echo "  $php_bin -m | grep pdo_mysql"
    exit 1
  fi

  if ! "$php_bin" -r 'exit(defined("XML_PI_NODE") ? 0 : 1);' >/dev/null 2>&1; then
    echo ""
    echo "The PHP CLI at '$php_bin' is missing the XML extension required by Symfony's serializer."
    echo "BuddyScript needs XML support during backend requests, and PHP is currently failing with undefined XML constants."
    echo ""
    echo "On Debian/Ubuntu, for example, install the PHP 8.5 XML package:"
    echo "  sudo apt install php8.5-xml"
    echo ""
    echo "Then verify it with:"
    echo "  $php_bin -r 'var_dump(defined(\"XML_PI_NODE\"));'"
    exit 1
  fi
}

PHP_BIN="$(select_php_binary || true)"

if [[ -z "$PHP_BIN" ]]; then
  echo "No PHP CLI binary was found. Install PHP 8.5 and try again."
  exit 1
fi

require_php_driver "$PHP_BIN"

find_listener_pid() {
  local port="$1"

  if command -v lsof >/dev/null 2>&1; then
    lsof -nP -iTCP:"$port" -sTCP:LISTEN -t 2>/dev/null | head -n 1 || true
    return 0
  fi

  if command -v ss >/dev/null 2>&1; then
    ss -ltnp "sport = :$port" 2>/dev/null | awk -F 'pid=' '/pid=/{split($2, a, ","); print a[1]; exit}' || true
  fi

  return 0
}

ensure_backend_port_available() {
  local pid cmd

  pid="$(find_listener_pid "$BACKEND_PORT")"
  if [[ -z "$pid" ]]; then
    return
  fi

  cmd="$(ps -p "$pid" -o cmd= 2>/dev/null || true)"

  if [[ "$cmd" == *"-S $BACKEND_HOST:$BACKEND_PORT"* && "$cmd" == *"public/router.php"* ]]; then
    echo "Found stale BuddyScript backend process on $BACKEND_HOST:$BACKEND_PORT (PID $pid). Stopping it..."
    kill "$pid" 2>/dev/null || true
    sleep 1
    return
  fi

  echo ""
  echo "Port $BACKEND_PORT is already in use by PID $pid."
  echo "Process: $cmd"
  echo "Stop it manually and run npm run dev again."
  echo "Example: kill $pid"
  exit 1
}

cleanup() {
  if [[ -n "${BACKEND_WATCHER_PID:-}" ]] && kill -0 "$BACKEND_WATCHER_PID" 2>/dev/null; then
    kill "$BACKEND_WATCHER_PID" 2>/dev/null || true
  fi

  if [[ -n "${BACKEND_PID:-}" ]] && kill -0 "$BACKEND_PID" 2>/dev/null; then
    kill "$BACKEND_PID" 2>/dev/null || true
  fi

  if [[ -n "${FRONTEND_PID:-}" ]] && kill -0 "$FRONTEND_PID" 2>/dev/null; then
    kill "$FRONTEND_PID" 2>/dev/null || true
  fi
}

trap cleanup EXIT INT TERM

if [[ ! -d "$BACKEND_DIR/vendor" ]]; then
  echo "Installing backend dependencies..."
  (cd "$BACKEND_DIR" && composer install --no-interaction)
fi

if [[ ! -d "$FRONTEND_DIR/node_modules" ]]; then
  echo "Installing frontend dependencies..."
  (cd "$FRONTEND_DIR" && npm install)
fi

echo "Synchronizing database schema..."
if ! (cd "$BACKEND_DIR" && "$PHP_BIN" bin/console doctrine:schema:update --force); then
  echo ""
  echo "Database schema update failed."
  echo "Check backend/.env.local DATABASE_URL and ensure MySQL is running and credentials are valid."
  exit 1
fi

ensure_backend_port_available

echo "Starting backend on http://$BACKEND_HOST:$BACKEND_PORT"
(
  cd "$BACKEND_DIR"
  "$PHP_BIN" -S "$BACKEND_HOST:$BACKEND_PORT" -t public public/router.php
) &
BACKEND_PID=$!

(
  while kill -0 "$BACKEND_PID" 2>/dev/null; do
    sleep 1
  done
  echo ""
  echo "Backend exited, but the frontend will keep running."
) &
BACKEND_WATCHER_PID=$!

echo "Starting frontend on http://$FRONTEND_HOST:$FRONTEND_PORT"
(
  cd "$FRONTEND_DIR"
  VITE_API_BASE_URL="http://$BACKEND_HOST:$BACKEND_PORT" npm run dev -- --host "$FRONTEND_HOST" --port "$FRONTEND_PORT"
) &
FRONTEND_PID=$!

wait "$FRONTEND_PID"

