function buildAuthHeader(username, password) {
  return `Basic ${btoa(`${username}:${password}`)}`;
}

chrome.action.onClicked.addListener(async (tab) => {
  if (!tab?.id) {
    return;
  }

  const url = chrome.runtime.getURL(`app.html?sourceTabId=${tab.id}`);
  await chrome.tabs.create({ url });
});

chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
  if (message?.type !== "generate") {
    return false;
  }

  (async () => {
    try {
      const response = await fetch(`${message.baseUrl.replace(/\/+$/, "")}/api/generate`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: buildAuthHeader(message.username, message.password)
        },
        body: JSON.stringify({
          model: message.model,
          prompt: message.prompt,
          format: "json",
          stream: false,
          options: {
            temperature: 0
          }
        })
      });

      const text = await response.text();
      let body;
      try {
        body = JSON.parse(text);
      } catch {
        body = null;
      }

      sendResponse({
        ok: response.ok,
        status: response.status,
        statusText: response.statusText,
        url: response.url,
        headers: Object.fromEntries(response.headers.entries()),
        body,
        text
      });
    } catch (error) {
      sendResponse({
        ok: false,
        networkError: true,
        error: error instanceof Error ? error.message : String(error)
      });
    }
  })();

  return true;
});
