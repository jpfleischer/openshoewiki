#!/bin/sh
set -eu

: "${OLLAMA_PROXY_USER:?OLLAMA_PROXY_USER is required}"
: "${OLLAMA_PROXY_PASSWORD:?OLLAMA_PROXY_PASSWORD is required}"
: "${OLLAMA_UPSTREAM:=ollama:11434}"

PASSWORD_HASH="$(caddy hash-password --plaintext "$OLLAMA_PROXY_PASSWORD")"

sed \
  -e "s|__OLLAMA_PROXY_USER__|$OLLAMA_PROXY_USER|g" \
  -e "s|__OLLAMA_PROXY_PASSWORD_HASH__|$PASSWORD_HASH|g" \
  -e "s|__OLLAMA_UPSTREAM__|$OLLAMA_UPSTREAM|g" \
  /etc/caddy/Caddyfile.template > /etc/caddy/Caddyfile

exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
