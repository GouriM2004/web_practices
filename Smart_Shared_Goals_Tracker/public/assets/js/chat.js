(function () {
  // Lightweight chat client that supports group- and goal-scoped threads.
  // It uses polling to stay simple and avoid extra server setup.
  function qs(selector, ctx) {
    return (ctx || document).querySelector(selector);
  }
  function qsa(selector, ctx) {
    return Array.from((ctx || document).querySelectorAll(selector));
  }
  function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  // basePath: strip /group.php or /goal.php from path
  const basePath =
    window.location.pathname.replace(/\/(group|goal)\.php.*$/i, "") || "";

  // detect context
  let groupId = null;
  let goalId = null;
  const gp = document.getElementById("groupPage");
  const gj = document.getElementById("goalPage");
  const groupChatEl =
    document.getElementById("groupChat") ||
    document.querySelector("#groupChat");
  const goalChatEl =
    document.getElementById("goalChat") || document.querySelector("#goalChat");
  if (groupChatEl) groupId = groupChatEl.dataset.groupId || null;
  if (goalChatEl) goalId = goalChatEl.dataset.goalId || null;
  // fallback: check top-level containers
  if (!groupId && gp && gp.dataset) groupId = gp.dataset.groupId || null;
  if (!goalId) {
    const goalWrap = document.querySelector("[data-goal-id]");
    if (goalWrap) goalId = goalWrap.dataset.goalId || null;
  }

  // messages state
  let lastFetched = null; // ISO timestamp of last received message
  let polling = null;

  function buildUrl(params) {
    const qs = Object.keys(params)
      .filter((k) => params[k] != null)
      .map((k) => encodeURIComponent(k) + "=" + encodeURIComponent(params[k]))
      .join("&");
    return basePath + "/api.php/threads" + (qs ? "?" + qs : "");
  }

  function formatMessage(m) {
    const when = new Date(m.created_at).toLocaleString();
    const name = escapeHtml(m.user_name || "User");
    const body = escapeHtml(m.body || "");
    return `<div class="mb-2"><div class="small text-muted">${when} <strong>${name}</strong></div><div>${body}</div></div>`;
  }

  async function fetchMessages(since) {
    try {
      const params = {};
      if (groupId) params.group_id = groupId;
      if (goalId) params.goal_id = goalId;
      if (since) params.since = since;
      const url = buildUrl(params);
      const res = await fetch(url, { credentials: "include" });
      if (!res.ok) return null;
      const payload = await res.json();
      return payload.messages || [];
    } catch (e) {
      console.warn("fetchMessages error", e);
      return null;
    }
  }

  async function loadInitial() {
    const messages = await fetchMessages(null);
    if (!messages) return;
    const container = document.querySelector("#messagesContainer");
    if (!container) return;
    container.innerHTML = "";
    messages.forEach((m) =>
      container.insertAdjacentHTML("beforeend", formatMessage(m))
    );
    if (messages.length) lastFetched = messages[messages.length - 1].created_at;
    // scroll to bottom
    container.scrollTop = container.scrollHeight;
  }

  async function poll() {
    if (polling) return;
    polling = setInterval(async () => {
      const messages = await fetchMessages(lastFetched);
      if (!messages) return;
      if (!messages.length) return;
      const container = document.querySelector("#messagesContainer");
      if (!container) return;
      messages.forEach((m) =>
        container.insertAdjacentHTML("beforeend", formatMessage(m))
      );
      lastFetched = messages[messages.length - 1].created_at;
      container.scrollTop = container.scrollHeight;
    }, 3000);
  }

  async function sendMessage(text) {
    const payload = { body: text };
    if (groupId) payload.group_id = groupId;
    if (goalId) payload.goal_id = goalId;
    try {
      const res = await fetch(basePath + "/api.php/threads", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const txt = await res.text();
      let p = null;
      try {
        p = JSON.parse(txt);
      } catch (e) {
        p = null;
      }
      if (!res.ok) {
        const err = p && p.error ? p.error : txt || "Status " + res.status;
        UI.toast("Send failed: " + err, "danger");
        return false;
      }
      const msg = p && p.message ? p.message : null;
      if (msg) {
        const container = document.querySelector("#messagesContainer");
        if (container) {
          container.insertAdjacentHTML("beforeend", formatMessage(msg));
          container.scrollTop = container.scrollHeight;
        }
        lastFetched = msg.created_at || lastFetched;
      }
      return true;
    } catch (e) {
      console.error("sendMessage error", e);
      UI.toast("Send failed", "danger");
      return false;
    }
  }

  // wire UI only if chat UI exists
  const chatInput = qs("#chatInput");
  const sendBtn = qs("#sendChatBtn");
  if (chatInput && sendBtn && (groupId || goalId)) {
    // load initial messages
    loadInitial().then(() => poll());

    sendBtn.addEventListener("click", async () => {
      const text = chatInput.value.trim();
      if (!text) return;
      sendBtn.disabled = true;
      const ok = await sendMessage(text);
      if (ok) chatInput.value = "";
      sendBtn.disabled = false;
      chatInput.focus();
    });

    chatInput.addEventListener("keydown", async (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendBtn.click();
      }
    });
  } else {
    // no chat UI on this page
    // console.info('chat: no UI or no context (group/goal) detected', {groupId, goalId});
  }
})();
