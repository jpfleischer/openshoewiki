# Ollama Product Extraction

This folder gives you a local Ollama runner, an authenticated proxy for LAN access, and a small extraction script for shoe product pages.

## Files

- `docker-compose.yml`: defines the Ollama container and the Caddy auth proxy container.
- `Makefile`: thin wrapper around Docker Compose plus root `.env` validation.
- `proxy/`: custom proxy image that hashes the password at container startup and renders the final Caddy config from `proxy/Caddyfile.template`.
- `extract_shoe_page.py`: fetches a product page or reads saved text, then asks Ollama for structured shoe attributes.

## Start Ollama

```sh
cd tools/ollama
make yep
```

Required root `.env` values:

- `OLLAMA_PROXY_USER`
- `OLLAMA_PROXY_PASSWORD`

Add placeholders to your root `.env.example`, then set real values in your local `.env`.

The Makefile will:

- read the repo root `.env`
- validate the required proxy credentials
- start both containers via Docker Compose

The proxy container will:

- hash `OLLAMA_PROXY_PASSWORD` at startup
- render its own runtime `Caddyfile`
- start Caddy with that generated config

Defaults:

- Container name: `ollama_container`
- Proxy container name: `ollama_proxy`
- Proxy bind: `11434:8080`
- Ollama server host: `0.0.0.0:11434`
- Model: `gemma3:27b`
- GPU device id: `1`

You can override them:

```sh
make yep OLLAMA_MODEL=llama3.1:8b OLLAMA_BIND=11434:8080 OLLAMA_HOST=0.0.0.0:11434 OLLAMA_GPU_ID=0
```

## Query a Product Page

```sh
python extract_shoe_page.py --url "https://example.com/product-page"
```

Or analyze saved text/HTML:

```sh
python extract_shoe_page.py --file sample-product.html
```

You can also save the cleaned text that gets sent to the model:

```sh
python extract_shoe_page.py --url "https://example.com/product-page" --raw-text-out cleaned.txt
```

## Expected Output

The script asks the model for JSON containing fields like:

- `brand`
- `product_name`
- `product_type`
- `style_code_or_sku`
- `heel_height`
- `platform_height`
- `upper_material`
- `closure`
- `colorways`
- `price`
- `release_year`
- `features`
- `evidence`

## Notes

- The script uses Python standard library only.
- Some pages block scraping or hide details in client-side JSON; in those cases, pass saved page text or improve the fetch/extraction logic.
- The current frontend build stack in this repo is unrelated to this Ollama tooling.
- This setup exposes the proxy on the machine's LAN-visible port, not the raw Ollama container.
- Raw Ollama stays on the Docker network and is only reachable by the proxy container.
- GPU access uses `runtime: nvidia` plus `NVIDIA_VISIBLE_DEVICES`, which is more compatible with older Docker Compose installs than the newer `gpus:` field.
