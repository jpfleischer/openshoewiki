#!/usr/bin/env python3
"""Fetch a webpage, extract readable text, and ask Ollama for shoe attributes."""

from __future__ import annotations

import argparse
import json
import re
import sys
from html import unescape
from html.parser import HTMLParser
from pathlib import Path
from typing import Iterable
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen


DEFAULT_MODEL = "gemma3:27b"
DEFAULT_OLLAMA_URL = "http://localhost:11434/api/generate"
MAX_TEXT_CHARS = 24000


class VisibleTextParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self._chunks: list[str] = []
        self._skip_depth = 0

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        if tag in {"script", "style", "noscript", "svg"}:
            self._skip_depth += 1
        elif tag in {"p", "div", "section", "article", "br", "li", "h1", "h2", "h3", "h4"}:
            self._chunks.append("\n")

    def handle_endtag(self, tag: str) -> None:
        if tag in {"script", "style", "noscript", "svg"} and self._skip_depth:
            self._skip_depth -= 1
        elif tag in {"p", "div", "section", "article", "li"}:
            self._chunks.append("\n")

    def handle_data(self, data: str) -> None:
        if not self._skip_depth:
            text = data.strip()
            if text:
                self._chunks.append(text + " ")

    def get_text(self) -> str:
        text = unescape("".join(self._chunks))
        text = re.sub(r"[ \t]+", " ", text)
        text = re.sub(r"\n{3,}", "\n\n", text)
        return text.strip()


def fetch_url(url: str) -> str:
    request = Request(
        url,
        headers={
            "User-Agent": (
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
            )
        },
    )
    with urlopen(request, timeout=30) as response:
        return response.read().decode("utf-8", errors="replace")


def extract_visible_text(html: str) -> str:
    parser = VisibleTextParser()
    parser.feed(html)
    text = parser.get_text()
    return text[:MAX_TEXT_CHARS]


def load_input(args: argparse.Namespace) -> tuple[str, str]:
    if args.file:
        path = Path(args.file)
        return path.name, path.read_text(encoding="utf-8")

    if args.url:
        html = fetch_url(args.url)
        return args.url, extract_visible_text(html)

    raw = sys.stdin.read()
    if raw.strip():
        return "stdin", raw

    raise ValueError("Provide --url, --file, or pipe page text on stdin.")


def build_prompt(source: str, page_text: str) -> str:
    schema = {
        "source": source,
        "brand": None,
        "product_name": None,
        "product_type": None,
        "style_code_or_sku": None,
        "heel_height": None,
        "platform_height": None,
        "upper_material": None,
        "lining_material": None,
        "sole_material": None,
        "closure": None,
        "colorways": [],
        "price": None,
        "currency": None,
        "release_year": None,
        "gender_marketing": None,
        "fit_notes": None,
        "features": [],
        "description_summary": None,
        "confidence_notes": [],
        "evidence": [],
    }

    return (
        "You extract structured shoe product data from product-page text.\n"
        "Return valid JSON only. Do not wrap it in markdown.\n"
        "Use null for unknown scalar values and [] for unknown list values.\n"
        "Do not invent details. Prefer direct evidence from the text.\n"
        "If heel height appears in more than one unit, preserve the clearest form.\n"
        "Add short quotes or paraphrases to the evidence array for the most important fields.\n\n"
        "Desired JSON shape:\n"
        f"{json.dumps(schema, indent=2)}\n\n"
        "Page text:\n"
        f"{page_text}"
    )


def call_ollama(model: str, ollama_url: str, prompt: str) -> str:
    payload = json.dumps(
        {
            "model": model,
            "prompt": prompt,
            "stream": False,
            "options": {
                "temperature": 0.1,
            },
        }
    ).encode("utf-8")

    request = Request(
        ollama_url,
        data=payload,
        headers={"Content-Type": "application/json"},
        method="POST",
    )

    with urlopen(request, timeout=180) as response:
        parsed = json.loads(response.read().decode("utf-8"))
        return parsed["response"].strip()


def parse_args(argv: Iterable[str]) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--url", help="Product-page URL to fetch and analyze.")
    parser.add_argument("--file", help="Path to a saved HTML or text file to analyze.")
    parser.add_argument("--model", default=DEFAULT_MODEL, help=f"Ollama model name. Default: {DEFAULT_MODEL}")
    parser.add_argument(
        "--ollama-url",
        default=DEFAULT_OLLAMA_URL,
        help=f"Ollama generate endpoint. Default: {DEFAULT_OLLAMA_URL}",
    )
    parser.add_argument(
        "--raw-text-out",
        help="Optional path to save the cleaned page text before sending it to Ollama.",
    )
    return parser.parse_args(list(argv))


def main(argv: Iterable[str]) -> int:
    args = parse_args(argv)

    try:
        source, page_text = load_input(args)
        if args.raw_text_out:
            Path(args.raw_text_out).write_text(page_text, encoding="utf-8")

        prompt = build_prompt(source, page_text)
        response = call_ollama(args.model, args.ollama_url, prompt)

        try:
            parsed = json.loads(response)
        except json.JSONDecodeError:
            print(response)
            return 0

        print(json.dumps(parsed, indent=2, ensure_ascii=True))
        return 0
    except (ValueError, FileNotFoundError, HTTPError, URLError) as exc:
        print(f"error: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
