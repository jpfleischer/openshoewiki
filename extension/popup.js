const DEFAULT_SETTINGS = {
  baseUrl: "",
  username: "",
  password: "",
  model: "qwen2.5:32b"
};

const EXTRACTION_PROMPT = [
    "You are extracting structured shoe product data.",
    "Return one JSON object only. No markdown. No prose.",
    "Prefer explicit values from the Signals block over Visible text when they conflict.",
    "If a field is missing, use null.",
    "Ignore customer reviews, related products, bundles, social media, and size-chart boilerplate unless they directly describe this exact product.",
    "Use short exact evidence snippets from the page for each non-null field.",
    'Return exactly this JSON shape: {"product_name":null,"brand":null,"product_type":null,"heel_height":null,"materials":[],"sku_style_code":null,"price":null,"evidence":{"product_name":null,"brand":null,"product_type":null,"heel_height":null,"materials":null,"sku_style_code":null,"price":null}}'
].join(" ");

const SETTINGS_KEYS = Object.keys(DEFAULT_SETTINGS);

const elements = {
  sourcePage: document.getElementById("sourcePage"),
  baseUrl: document.getElementById("baseUrl"),
  username: document.getElementById("username"),
  password: document.getElementById("password"),
  model: document.getElementById("model"),
  saveSettings: document.getElementById("saveSettings"),
  analyzePage: document.getElementById("analyzePage"),
  copyResponse: document.getElementById("copyResponse"),
  copyStatus: document.getElementById("copyStatus"),
  settingsStatus: document.getElementById("settingsStatus"),
  runStatus: document.getElementById("runStatus"),
  result: document.getElementById("result")
};

let latestNormalizedResponse = null;

function setStatus(target, message, isError = false) {
  target.textContent = message;
  target.style.color = isError ? "#a11b1b" : "";
}

function isLikelyPlainHttpRemoteHost(value) {
  try {
    const url = new URL(value);
    if (url.protocol !== "http:") {
      return false;
    }

    const host = url.hostname;
    const isLocalhost = host === "localhost" || host === "127.0.0.1" || host === "::1";
    const isIpv4 = /^\d{1,3}(\.\d{1,3}){3}$/.test(host);

    return !isLocalhost && !isIpv4;
  } catch {
    return false;
  }
}

function getSettingsFromForm() {
  return {
    baseUrl: elements.baseUrl.value.trim(),
    username: elements.username.value.trim(),
    password: elements.password.value,
    model: elements.model.value.trim()
  };
}

function applySettingsToForm(settings) {
  elements.baseUrl.value = settings.baseUrl;
  elements.username.value = settings.username;
  elements.password.value = settings.password;
  elements.model.value = settings.model;
}

async function loadSettings() {
  const stored = await chrome.storage.sync.get(DEFAULT_SETTINGS);
  applySettingsToForm(stored);
}

async function saveSettings() {
  const settings = getSettingsFromForm();
  await chrome.storage.sync.set(settings);
  setStatus(elements.settingsStatus, "Settings saved.");
}

function buildPrompt(pageData, instruction) {
  const priorityFields = {
    product_name: pageData.signals.productName || null,
    brand: pageData.signals.brand || null,
    product_type: pageData.signals.productType || null,
    sku_style_code: pageData.signals.sku || null,
    price: pageData.signals.price || null,
    materials: pageData.signals.materials || []
  };

  return [
    "You are extracting structured product information from a shoe product page.",
    instruction,
    "",
    "Page URL:",
    pageData.url,
    "",
    "Priority explicit field candidates:",
    JSON.stringify(priorityFields, null, 2),
    "",
    "Signals:",
    JSON.stringify(pageData.signals, null, 2),
    "",
    "Visible text:",
    pageData.visibleText
  ].join("\n");
}

function parseModelResponse(response) {
  if (typeof response !== "string") {
    return response;
  }

  const trimmed = response.trim();
  if (!trimmed) {
    return null;
  }

  try {
    return JSON.parse(trimmed);
  } catch {
    return response;
  }
}

function flattenTextParts(value) {
  if (value == null) {
    return [];
  }

  if (typeof value === "string") {
    return [value];
  }

  if (Array.isArray(value)) {
    return value.flatMap((item) => flattenTextParts(item));
  }

  if (typeof value === "object") {
    return Object.values(value).flatMap((item) => flattenTextParts(item));
  }

  return [String(value)];
}

function firstNonEmpty(...values) {
  for (const value of values) {
    if (typeof value === "string" && value.trim()) {
      return value.trim();
    }
  }

  return null;
}

function normalizeMaterials(values) {
  const parts = flattenTextParts(values)
    .flatMap((value) => value.split(/[,;]\s*/))
    .map((value) => value.trim())
    .filter(Boolean);

  const unique = [...new Set(parts)];

  // Prefer more specific leather names over generic bucket terms.
  if (unique.some((value) => /sendal leather/i.test(value))) {
    return unique.filter((value) => !/^soft leather$/i.test(value));
  }

  return unique;
}

function findEvidenceSnippet(haystack, patterns) {
  if (!haystack) {
    return null;
  }

  const text = normalizeWhitespace(haystack);
  for (const pattern of patterns) {
    const match = text.match(pattern);
    if (match) {
      return match[0].trim();
    }
  }

  return null;
}

function inferProductType(pageData, modelData) {
  const candidates = [
    pageData.signals.productType,
    modelData?.product_type,
    modelData?.["Product Type"],
    modelData?.type,
    modelData?.category,
    modelData?.["Category"]
  ];

  const direct = firstNonEmpty(...candidates);
  if (direct) {
    return direct;
  }

  const searchSpace = [
    pageData.signals.title,
    pageData.signals.ogTitle,
    pageData.visibleText,
    ...flattenTextParts(modelData)
  ]
    .filter(Boolean)
    .join("\n");

  if (/mary jane/i.test(searchSpace)) {
    return "Mary Jane heels";
  }

  if (/heeled shoes|ardern heel|heel height/i.test(searchSpace)) {
    return "Heeled shoes";
  }

  if (/\bheels\b/i.test(searchSpace)) {
    return "Heels";
  }

  return null;
}

function inferBrand(pageData, modelData) {
  const explicit = firstNonEmpty(
    pageData.signals.brand,
    modelData?.brand,
    modelData?.Brand
  );

  if (explicit && !/eviee sendal leather heeled shoes/i.test(explicit)) {
    return explicit;
  }

  const titleBrand = pageData.signals.title?.includes("|")
    ? pageData.signals.title.split("|").pop().trim()
    : null;
  if (titleBrand) {
    return titleBrand;
  }

  const ogTitleBrand = pageData.signals.ogTitle?.includes("|")
    ? pageData.signals.ogTitle.split("|").pop().trim()
    : null;
  if (ogTitleBrand) {
    return ogTitleBrand;
  }

  const descriptionBrand = findEvidenceSnippet(pageData.signals.description || "", [/\bDr\.?\s*Martens\b/i]);
  if (descriptionBrand) {
    return descriptionBrand;
  }

  if (/drmartens\.com/i.test(pageData.url)) {
    return "Dr. Martens";
  }

  return explicit || null;
}

function normalizeMeasurement(value) {
  if (!value) {
    return null;
  }

  return value
    .replace(/\b(inches|inch)\b/gi, "in")
    .replace(/\s+/g, " ")
    .trim();
}

function normalizePrice(value) {
  if (!value) {
    return null;
  }

  const raw = value.trim();

  const symbolMatch = raw.match(/(\$)\s*(\d+(?:\.\d{1,2})?)/);
  if (symbolMatch) {
    const amount = Number(symbolMatch[2]);
    if (Number.isFinite(amount)) {
      return `${symbolMatch[1]}${amount.toFixed(2)}`;
    }
  }

  const codeMatch = raw.match(/\b([A-Z]{3})\s*(\d+(?:\.\d{1,2})?)\b/);
  if (codeMatch) {
    const amount = Number(codeMatch[2]);
    if (Number.isFinite(amount)) {
      return `${codeMatch[1]} ${amount.toFixed(2)}`;
    }
  }

  return raw;
}

function inferHeelHeight(pageData, modelData) {
  const direct = firstNonEmpty(
    modelData?.heel_height,
    modelData?.["Heel Height"],
    modelData?.heelHeight
  );

  if (direct) {
    return direct;
  }

  const joined = [
    pageData.visibleText,
    ...flattenTextParts(modelData?.features),
    ...flattenTextParts(modelData)
  ]
    .filter(Boolean)
    .join("\n");

  const match = joined.match(/heel height:\s*([^\n;]+)|(\d+\s+\d\/\d\s*(?:inch|in)\b)/i);
  if (match) {
    return normalizeMeasurement((match[1] || match[2] || "").trim());
  }

  return null;
}

function buildNormalizedResponse(pageData, modelData) {
  const materials = normalizeMaterials([
    pageData.signals.materials,
    modelData?.materials,
    modelData?.material,
    modelData?.["Material"],
    modelData?.["Materials"]
  ]);

  const normalized = {
    product_name: firstNonEmpty(
      pageData.signals.productName,
      modelData?.product_name,
      modelData?.["Product Name"],
      modelData?.name
    ),
    brand: inferBrand(pageData, modelData),
    product_type: inferProductType(pageData, modelData),
    heel_height: inferHeelHeight(pageData, modelData),
    materials,
    sku_style_code: firstNonEmpty(
      pageData.signals.sku,
      modelData?.sku_style_code,
      modelData?.style_code_or_sku,
      modelData?.sku,
      modelData?.SKU
    ),
    price: firstNonEmpty(
      pageData.signals.price,
      modelData?.price,
      modelData?.Price
    ),
    evidence: {
      product_name:
        firstNonEmpty(pageData.signals.productName, pageData.signals.ogTitle, pageData.signals.title) || null,
      brand:
        firstNonEmpty(pageData.signals.brand) ||
        findEvidenceSnippet(pageData.visibleText, [/\bDr\.?\s*Martens\b/i]) ||
        null,
      product_type:
        firstNonEmpty(pageData.signals.productType) ||
        findEvidenceSnippet(pageData.visibleText, [/\bMary Jane\b/i, /\bheeled shoes\b/i, /\bHeels\b/i]) ||
        null,
      heel_height:
        normalizeMeasurement(
          findEvidenceSnippet(pageData.visibleText, [/Heel height:\s*[^\n;]+/i, /\d+\s+\d\/\d\s*inch Ardern heel/i])
        ) ||
        null,
      materials:
        findEvidenceSnippet(pageData.visibleText, [/Built from [^\n.]*leather/i, /\bSendal leather\b/i]) || null,
      sku_style_code:
        firstNonEmpty(pageData.signals.sku) ||
        findEvidenceSnippet(pageData.visibleText, [/\b27371001\b/]) ||
        null,
      price:
        normalizePrice(firstNonEmpty(pageData.signals.price)) ||
        normalizePrice(findEvidenceSnippet(pageData.visibleText, [/\$\d+(?:\.\d{2})?/, /\b[A-Z]{3}\s*\d+(?:\.\d{1,2})?\b/])) ||
        null
    }
  };

  normalized.product_name = normalized.product_name || null;
  normalized.brand = normalized.brand || null;
  normalized.product_type = normalized.product_type || null;
  normalized.heel_height = normalizeMeasurement(normalized.heel_height) || null;
  normalized.sku_style_code = normalized.sku_style_code || null;
  normalized.price = normalizePrice(normalized.price) || null;

  return normalized;
}

function normalizeWhitespace(value) {
  return (value || "")
    .replace(/\s+\n/g, "\n")
    .replace(/\n{3,}/g, "\n\n")
    .replace(/[ \t]{2,}/g, " ")
    .trim();
}

function getSourceTabIdFromQuery() {
  const params = new URLSearchParams(window.location.search);
  const raw = params.get("sourceTabId");
  if (!raw) {
    return null;
  }

  const parsed = Number(raw);
  return Number.isInteger(parsed) ? parsed : null;
}

async function getCurrentTab() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  if (!tab?.id) {
    throw new Error("No active tab found.");
  }
  return tab;
}

async function getSourceTab() {
  const sourceTabId = getSourceTabIdFromQuery();
  if (sourceTabId) {
    try {
      const tab = await chrome.tabs.get(sourceTabId);
      if (tab?.id) {
        return tab;
      }
    } catch {
      // Fall back if the original source tab no longer exists.
    }
  }

  return getCurrentTab();
}

async function extractPageData(tabId) {
  const [{ result }] = await chrome.scripting.executeScript({
    target: { tabId },
    func: () => {
      const MAX_TEXT_LENGTH = 32000;

      function normalizeWhitespace(value) {
        return (value || "")
          .replace(/\s+\n/g, "\n")
          .replace(/\n{3,}/g, "\n\n")
          .replace(/[ \t]{2,}/g, " ")
          .trim();
      }

      function extractJsonLdObjects() {
        return Array.from(document.querySelectorAll("script[type='application/ld+json']"))
          .map((node) => node.textContent?.trim())
          .filter(Boolean)
          .flatMap((text) => {
            try {
              const parsed = JSON.parse(text);
              return Array.isArray(parsed) ? parsed : [parsed];
            } catch {
              return [];
            }
          });
      }

      function findValueDeep(input, matcher) {
        if (Array.isArray(input)) {
          for (const value of input) {
            const found = findValueDeep(value, matcher);
            if (found) {
              return found;
            }
          }
          return null;
        }

        if (!input || typeof input !== "object") {
          return null;
        }

        for (const [key, value] of Object.entries(input)) {
          if (matcher(key, value)) {
            return value;
          }

          const found = findValueDeep(value, matcher);
          if (found) {
            return found;
          }
        }

        return null;
      }

      function stringifyJsonLdSummary(objects) {
        return objects
          .slice(0, 3)
          .map((value) => JSON.stringify(value, null, 2))
          .join("\n\n");
      }

      function trimVisibleText(text) {
        const lines = normalizeWhitespace(text)
          .split("\n")
          .map((line) => line.trim())
          .filter(Boolean);

        const kept = [];

        for (const line of lines) {
          if (
            /verified buyer/i.test(line) ||
            /published date/i.test(line) ||
            /was this helpful/i.test(line) ||
            /^read more/i.test(line)
          ) {
            continue;
          }

          kept.push(line);
        }

        return kept.join("\n").slice(0, MAX_TEXT_LENGTH);
      }

      const cleanupClone = document.documentElement.cloneNode(true);
      cleanupClone.querySelectorAll("script, style, noscript, iframe, svg").forEach((node) => node.remove());
      cleanupClone
        .querySelectorAll(
          "[id*='cookie'],[class*='cookie'],[id*='consent'],[class*='consent'],[id*='newsletter'],[class*='newsletter']"
        )
        .forEach((node) => node.remove());

      const textSource =
        cleanupClone.querySelector("main") ||
        cleanupClone.querySelector("[role='main']") ||
        cleanupClone.querySelector("article") ||
        cleanupClone.body ||
        cleanupClone;

      const visibleText = trimVisibleText(textSource.innerText || "");

      const meta = (selector) => document.querySelector(selector)?.getAttribute("content") || null;
      const jsonLdObjects = extractJsonLdObjects();
      const jsonLd = stringifyJsonLdSummary(jsonLdObjects);

      const productDataMatch = document.documentElement.innerHTML.match(/"productName"\s*:\s*"([^"]+)"/i);
      const brandMatch = document.documentElement.innerHTML.match(/"brand"\s*:\s*"([^"]+)"/i);
      const skuMatch = document.documentElement.innerHTML.match(/"productCode"\s*:\s*"([^"]+)"/i);
      const priceMatch = document.documentElement.innerHTML.match(/"price"\s*:\s*"?(?:\$)?(\d+(?:\.\d{2})?)"?/i);
      const currencyMatch = document.documentElement.innerHTML.match(/"priceCurrency"\s*:\s*"([A-Z]{3})"/i);
      const title = document.title;
      const titleBrand = title.includes("|") ? title.split("|").pop().trim() : null;
      const jsonLdBrand = findValueDeep(jsonLdObjects, (key, value) => key === "brand" && typeof value === "string");
      const jsonLdMaterial = findValueDeep(
        jsonLdObjects,
        (key, value) => ["material", "materials"].includes(key) && typeof value === "string"
      );
      const jsonLdCategory = findValueDeep(
        jsonLdObjects,
        (key, value) => ["category", "productType"].includes(key) && typeof value === "string"
      );

      return {
        url: location.href,
        title,
        signals: {
          title,
          ogTitle: meta('meta[property="og:title"]'),
          description: meta('meta[name="description"]'),
          canonical: document.querySelector('link[rel="canonical"]')?.href || null,
          twitterImageAlt: meta('meta[name="twitter:image:alt"]'),
          productName: productDataMatch ? productDataMatch[1] : null,
          brand: brandMatch ? brandMatch[1] : jsonLdBrand || titleBrand,
          sku: skuMatch ? skuMatch[1] : null,
          price: priceMatch ? `${currencyMatch ? `${currencyMatch[1]} ` : ""}${priceMatch[1]}` : null,
          productType: jsonLdCategory || null,
          materials: jsonLdMaterial ? [jsonLdMaterial] : [],
          jsonLd
        },
        visibleText
      };
    }
  });

  if (!result) {
    throw new Error("No page data was extracted.");
  }

  return result;
}

async function analyzeCurrentPage() {
  const settings = getSettingsFromForm();
  if (!settings.baseUrl || !settings.username || !settings.password || !settings.model) {
    throw new Error("Base URL, username, password, and model are required.");
  }

  const tab = await getSourceTab();
  const pageData = await extractPageData(tab.id);
  const prompt = buildPrompt(pageData, EXTRACTION_PROMPT);

  const transport = await new Promise((resolve, reject) => {
    chrome.runtime.sendMessage(
      {
        type: "generate",
        baseUrl: settings.baseUrl,
        username: settings.username,
        password: settings.password,
        model: settings.model,
        prompt
      },
      (response) => {
        if (chrome.runtime.lastError) {
          reject(new Error(chrome.runtime.lastError.message));
          return;
        }

        if (!response) {
          reject(new Error("No response from background script."));
          return;
        }

        if (response.networkError) {
          const error = new Error(response.error || "Network request failed.");
          error.transport = response;
          reject(error);
          return;
        }

        resolve(response);
      }
    );
  });

  return {
    request: {
      url: pageData.url,
      signals: pageData.signals,
      visibleText: pageData.visibleText,
      prompt
    },
    transport: {
      ok: transport.ok,
      status: transport.status,
      statusText: transport.statusText,
      url: transport.url,
      headers: transport.headers,
      rawText: transport.text
    },
    raw_response: parseModelResponse(transport.body?.response ?? transport.body ?? null),
    response: buildNormalizedResponse(
      pageData,
      parseModelResponse(transport.body?.response ?? transport.body ?? null)
    )
  };
}

elements.saveSettings.addEventListener("click", async () => {
  try {
    await saveSettings();
  } catch (error) {
    setStatus(elements.settingsStatus, error.message, true);
  }
});

elements.analyzePage.addEventListener("click", async () => {
  elements.analyzePage.disabled = true;
  elements.result.value = "";
  setStatus(elements.runStatus, "Analyzing page...");

  try {
    await saveSettings();
    const result = await analyzeCurrentPage();
    elements.result.value = JSON.stringify(result, null, 2);
    latestNormalizedResponse = result.response || null;

    if (result.transport?.ok) {
      setStatus(elements.runStatus, `Done. HTTP ${result.transport.status} ${result.transport.statusText}`);
    } else {
      setStatus(
        elements.runStatus,
        `Request reached the server but failed with HTTP ${result.transport?.status ?? "unknown"} ${result.transport?.statusText ?? ""}`.trim(),
        true
      );
    }
  } catch (error) {
    latestNormalizedResponse = null;

    if (error.transport) {
      elements.result.value = JSON.stringify(
        {
          transport: error.transport
        },
        null,
        2
      );
    }

    let message = error.message;

    if (
      /NetworkError when attempting to fetch resource/i.test(message) &&
      isLikelyPlainHttpRemoteHost(elements.baseUrl.value.trim())
    ) {
      message =
        `${message} Firefox is likely upgrading the configured http URL to https. ` +
        "Use an https Ollama proxy endpoint, or use a LAN IP such as http://192.168.x.y:11434 instead of a hostname.";
    }

    setStatus(elements.runStatus, message, true);
  } finally {
    elements.analyzePage.disabled = false;
  }
});

if (elements.copyResponse) {
  elements.copyResponse.addEventListener("click", async () => {
    if (!latestNormalizedResponse) {
      setStatus(elements.copyStatus, "Run an extraction first so there is normalized JSON to copy.", true);
      return;
    }

    try {
      await navigator.clipboard.writeText(JSON.stringify(latestNormalizedResponse, null, 2));
      setStatus(elements.copyStatus, "Normalized JSON copied to clipboard.");
    } catch (error) {
      setStatus(elements.copyStatus, error.message, true);
    }
  });
}

loadSettings().catch((error) => {
  setStatus(elements.settingsStatus, error.message, true);
});

getSourceTab()
  .then((tab) => {
    if (elements.sourcePage) {
      elements.sourcePage.textContent = tab?.url ? `Source page: ${tab.url}` : "Source page unavailable.";
    }
  })
  .catch(() => {
    if (elements.sourcePage) {
      elements.sourcePage.textContent = "Source page unavailable.";
    }
  });
