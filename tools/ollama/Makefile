.PHONY: yep up build pull-model stop down logs proxy-logs test check-env

SUBMIT_DIR := $(CURDIR)
REPO_ROOT := $(shell git -C "$(SUBMIT_DIR)" rev-parse --show-toplevel 2>/dev/null)

OLLAMA_CONTAINER_NAME ?= ollama_container
OLLAMA_PROXY_CONTAINER_NAME ?= ollama_proxy
OLLAMA_NETWORK ?= ocss
OLLAMA_BIND ?= 11434:8080
OLLAMA_HOST ?= 0.0.0.0:11434
OLLAMA_MODEL ?= gemma3:27b
OLLAMA_GPU_ID ?= 1
ROOT_ENV ?= $(REPO_ROOT)/.env
OLLAMA_UPSTREAM ?= ollama:11434
COMPOSE_FILE ?= docker-compose.yml
COMPOSE := OLLAMA_CONTAINER_NAME=$(OLLAMA_CONTAINER_NAME) OLLAMA_PROXY_CONTAINER_NAME=$(OLLAMA_PROXY_CONTAINER_NAME) OLLAMA_NETWORK=$(OLLAMA_NETWORK) OLLAMA_BIND=$(OLLAMA_BIND) OLLAMA_HOST=$(OLLAMA_HOST) OLLAMA_GPU_ID=$(OLLAMA_GPU_ID) OLLAMA_UPSTREAM=$(OLLAMA_UPSTREAM) docker compose --env-file $(ROOT_ENV) -f $(COMPOSE_FILE)

yep: up

up: down build check-env
	$(COMPOSE) up -d
	sleep 3
	$(COMPOSE) exec ollama ollama pull $(OLLAMA_MODEL)
	@set -a; . "$(ROOT_ENV)"; set +a; \
	curl -u "$$OLLAMA_PROXY_USER:$$OLLAMA_PROXY_PASSWORD" http://localhost:11434/api/tags

check-env:
	@test -f $(ROOT_ENV) || (echo "Missing $(ROOT_ENV). Create the repo root .env with OLLAMA_PROXY_USER and OLLAMA_PROXY_PASSWORD." && exit 1)
	@set -a; . $(ROOT_ENV); set +a; \
	test -n "$$OLLAMA_PROXY_USER" || (echo "Missing OLLAMA_PROXY_USER in $(ROOT_ENV)." && exit 1); \
	test -n "$$OLLAMA_PROXY_PASSWORD" || (echo "Missing OLLAMA_PROXY_PASSWORD in $(ROOT_ENV)." && exit 1)

build:
	$(COMPOSE) pull

pull-model:
	$(COMPOSE) exec ollama ollama pull $(OLLAMA_MODEL)

stop down:
	-$(COMPOSE) down

logs:
	$(COMPOSE) logs -f ollama

proxy-logs:
	$(COMPOSE) logs -f proxy

test:
	@set -a; . "$(ROOT_ENV)"; set +a; \
	curl -u "$$OLLAMA_PROXY_USER:$$OLLAMA_PROXY_PASSWORD" http://localhost:11434/api/tags
