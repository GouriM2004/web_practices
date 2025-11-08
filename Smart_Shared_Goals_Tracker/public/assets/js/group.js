(function () {
  // Small helper to avoid XSS when inserting server-provided text into the DOM
  function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  // basePath: strip /group.php and anything after so we can build absolute API paths
  const basePath = window.location.pathname.replace(/\/group\.php.*$/i, "");

  // Prefer explicit data attribute set server-side, fall back to query string
  let gid = null;
  const pageEl = document.getElementById("groupPage");
  if (pageEl && pageEl.dataset && pageEl.dataset.groupId)
    gid = pageEl.dataset.groupId;
  if (!gid) gid = new URLSearchParams(window.location.search).get("id");

  // Quick runtime debug: confirm this file executed and show gid/basePath
  try {
    console.info("group.js loaded", { gid: gid || null, basePath: basePath });
  } catch (e) {
    console.info("group.js loaded (partial)");
  }

  // Join group button (visible when user is not a member)
  const joinBtn = document.getElementById("joinBtn");
  if (joinBtn) {
    joinBtn.addEventListener("click", async (e) => {
      try {
        if (!gid) {
          UI.toast("Missing group id.", "danger");
          return;
        }
        const url =
          basePath + "/api.php/groups/" + encodeURIComponent(gid) + "/join";
        console.info("Joining group", { url });
        const res = await fetch(url, {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({}),
        });
        const txt = await res.text();
        let payload = null;
        try {
          payload = JSON.parse(txt);
        } catch (e) {
          payload = null;
        }
        if (!res.ok) {
          const err =
            payload && payload.error
              ? payload.error
              : txt || "Request failed: " + res.status;
          UI.toast(err, "danger");
          return;
        }
        UI.toast("Joined group", "success");
        setTimeout(() => location.reload(), 600);
      } catch (err) {
        console.error("join error", err);
        UI.toast("Join failed", "danger");
      }
    });
  } else {
    console.info("group.js: joinBtn not found on page");
  }

  // Add member functionality temporarily removed
  console.info("group.js: add-member feature disabled");
})();
