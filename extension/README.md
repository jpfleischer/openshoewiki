# OpenShoeWiki Browser Extension

This is a minimal Manifest V3 browser extension for sending the current product page to a configured Ollama host.

## What it does

- lets the user configure:
  - Ollama base URL
  - Basic auth username
  - Basic auth password
  - model name
- uses a built-in structured extraction prompt rather than an editable prompt field
- reads the current page DOM in the browser
- strips obvious noise such as `script`, `style`, `noscript`, `iframe`, `svg`, and simple cookie/consent blocks
- extracts:
  - page title
  - `og:title`
  - description
  - canonical URL
  - a few product-like inline fields when present
  - trimmed visible text
- sends the resulting prompt to the configured Ollama host
- shows the raw JSON/text response in a persistent extension tab

## Load it

Chrome:

1. Open `chrome://extensions`
2. Enable `Developer mode`
3. Click `Load unpacked`
4. Select the `extension` folder

Usage:

1. Open the product page you want to analyze
2. Click the extension icon
3. The extension opens a normal tab and keeps the source tab ID in the URL
4. Enter or reuse your Ollama settings
5. Click `Analyze Source Page`

## Notes

- The host URL and credentials are not hardcoded. They are stored in browser extension storage after the user enters them.
- The extension currently requests `<all_urls>` host access because the Ollama host is user-configurable.
- This is a first-pass tool, not a polished store-ready extension.
