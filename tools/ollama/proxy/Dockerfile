FROM caddy:2.8-alpine

COPY Caddyfile.template /etc/caddy/Caddyfile.template
COPY entrypoint.sh /usr/local/bin/ollama-proxy-entrypoint.sh

RUN chmod +x /usr/local/bin/ollama-proxy-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/ollama-proxy-entrypoint.sh"]
