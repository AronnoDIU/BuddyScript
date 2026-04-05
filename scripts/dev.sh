#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"
BACKEND_HOST="${BACKEND_HOST:-127.0.0.1}"
BACKEND_PORT="${BACKEND_PORT:-8000}"
FRONTEND_HOST="${FRONTEND_HOST:-127.0.0.1}"
FRONTEND_PORT="${FRONTEND_PORT:-5173}"

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

  if [[ "$cmd" == *"php -S $BACKEND_HOST:$BACKEND_PORT"* && "$cmd" == *"public/index.php"* ]]; then
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

echo "Running database migrations..."
if ! (cd "$BACKEND_DIR" && php bin/console doctrine:migrations:migrate --no-interaction); then
  echo ""
  echo "Database migration failed."
  echo "Check backend/.env.local DATABASE_URL and ensure MySQL is running and credentials are valid."
  exit 1
fi

ensure_backend_port_available

echo "Starting backend on http://$BACKEND_HOST:$BACKEND_PORT"
(
  cd "$BACKEND_DIR"
  php -S "$BACKEND_HOST:$BACKEND_PORT" -t public public/index.php
) &
BACKEND_PID=$!

echo "Starting frontend on http://$FRONTEND_HOST:$FRONTEND_PORT"
(
  cd "$FRONTEND_DIR"
  VITE_API_BASE_URL="http://$BACKEND_HOST:$BACKEND_PORT" npm run dev -- --host "$FRONTEND_HOST" --port "$FRONTEND_PORT"
) &
FRONTEND_PID=$!

wait -n "$BACKEND_PID" "$FRONTEND_PID"

