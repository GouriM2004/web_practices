(function () {
  // Challenges UI for group page
  const pageEl = document.getElementById("groupPage");
  const gid =
    pageEl && pageEl.dataset
      ? pageEl.dataset.groupId
      : new URLSearchParams(window.location.search).get("id");
  const listEl = document.getElementById("challengesList");
  const createBtn = document.getElementById("createChallengeBtn");
  if (!listEl || !gid) return;

  function escapeHtml(s) {
    if (s == null) return "";
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  async function fetchChallenges() {
    try {
      const res = await fetch(
        "/Smart_Shared_Goals_Tracker/public/api.php/challenges?group_id=" +
          encodeURIComponent(gid),
        { credentials: "include" }
      );
      if (!res.ok) throw res;
      const j = await res.json();
      return j.challenges || [];
    } catch (e) {
      console.warn("fetchChallenges", e);
      return null;
    }
  }

  function render(challs) {
    if (!challs || !challs.length) {
      listEl.innerHTML = '<div class="text-muted">No active challenges.</div>';
      return;
    }
    const wrap = document.createElement("div");
    wrap.className = "list-group";
    challs.forEach((c) => {
      const item = document.createElement("div");
      item.className =
        "list-group-item d-flex justify-content-between align-items-start";
      const left = document.createElement("div");
      left.innerHTML =
        "<div><strong>" +
        escapeHtml(c.title) +
        '</strong><div class="small text-muted">' +
        escapeHtml(c.description || "") +
        "</div></div>";
      const right = document.createElement("div");
      right.style.minWidth = "140px";
      right.style.textAlign = "right";
      const joinBtn = document.createElement("button");
      joinBtn.className = "btn btn-sm btn-primary me-2";
      joinBtn.textContent = "Join";
      joinBtn.addEventListener("click", async () => {
        joinBtn.disabled = true;
        try {
          const r = await fetch(
            "/Smart_Shared_Goals_Tracker/public/api.php/challenges/" +
              encodeURIComponent(c.id) +
              "/join",
            { method: "POST", credentials: "include" }
          );
          const p = await r.json();
          if (!r.ok) {
            alert(p.error || "Failed to join");
            return;
          }
          UI.toast("Joined challenge", "success");
          // optionally refresh list or show joined state
          load();
        } catch (e) {
          console.error("join error", e);
          UI.toast("Join failed", "danger");
        } finally {
          joinBtn.disabled = false;
        }
      });
      right.appendChild(joinBtn);
      const meta = document.createElement("div");
      meta.className = "small text-muted";
      meta.textContent =
        (c.start_date || "Starts now") +
        " • " +
        (c.duration_days || "30") +
        " days";
      right.appendChild(meta);
      item.appendChild(left);
      item.appendChild(right);
      wrap.appendChild(item);
    });
    listEl.innerHTML = "";
    listEl.appendChild(wrap);
  }

  async function load() {
    listEl.innerHTML = '<div class="text-muted">Loading challenges...</div>';
    const challs = await fetchChallenges();
    render(challs);
  }

  if (createBtn) {
    createBtn.addEventListener("click", () => {
      // simple prompt-based create flow
      const title = prompt("Challenge title (e.g. 30-Day Reading Challenge)");
      if (!title) return;
      const days = prompt("Duration in days", "30");
      const duration = parseInt(days, 10) || 30;
      const unit = prompt("Unit (e.g. pages, minutes) or leave empty", "");
      const cadence = prompt("Cadence (daily|weekly)", "daily");
      (async () => {
        try {
          const body = {
            title: title,
            duration_days: duration,
            unit: unit || null,
            cadence: cadence,
          };
          const res = await fetch(
            "/Smart_Shared_Goals_Tracker/public/api.php/challenges",
            {
              method: "POST",
              credentials: "include",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(Object.assign(body, { group_id: gid })),
            }
          );
          const p = await res.json();
          if (!res.ok) {
            alert(p.error || "Create failed");
            return;
          }
          UI.toast("Challenge created", "success");
          load();
        } catch (e) {
          console.error("create failed", e);
          UI.toast("Create failed", "danger");
        }
      })();
    });
  }

  // initial
  load();
})();
