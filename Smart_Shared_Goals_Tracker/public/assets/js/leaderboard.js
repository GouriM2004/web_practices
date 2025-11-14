(function () {
  // Leaderboard client for group pages
  function basePath() {
    return window.location.pathname.replace(/\/group\.php.*$/i, "");
  }
  function qs(s, ctx) {
    return (ctx || document).querySelector(s);
  }
  function escapeHtml(s) {
    if (s == null) return "";
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  const container = qs("#leaderboardContainer");
  const refreshBtn = qs("#refreshLeaderboard");
  const periodSel = qs("#leaderboardPeriod");
  const metricSel = qs("#leaderboardMetric");
  const pageEl = document.getElementById("groupPage");
  const gid =
    pageEl && pageEl.dataset
      ? pageEl.dataset.groupId
      : new URLSearchParams(window.location.search).get("id");
  if (!gid || !container) return;

  async function fetchLeaderboard(period, metric) {
    const url =
      basePath() +
      "/api.php/groups/" +
      encodeURIComponent(gid) +
      "/leaderboard?period=" +
      encodeURIComponent(period) +
      "&metric=" +
      encodeURIComponent(metric);
    try {
      const res = await fetch(url, { credentials: "include" });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const payload = await res.json();
      return payload;
    } catch (e) {
      console.warn("leaderboard fetch failed", e);
      return null;
    }
  }

  function renderList(leaders) {
    if (!leaders || !leaders.length) {
      container.innerHTML =
        '<div class="text-muted">No activity found for this period.</div>';
      return;
    }
    const list = document.createElement("div");
    list.className = "list-group";
    leaders.forEach((u) => {
      const item = document.createElement("div");
      item.className =
        "list-group-item d-flex justify-content-between align-items-center";
      const left = document.createElement("div");
      left.innerHTML =
        "<div><strong>" +
        escapeHtml(u.name) +
        '</strong> <div class="small text-muted">' +
        escapeHtml(u.email || "") +
        "</div></div>";
      const right = document.createElement("div");
      right.style.minWidth = "130px";
      right.style.textAlign = "right";
      const score = document.createElement("div");
      score.innerHTML = "<strong>" + escapeHtml(String(u.score)) + "</strong>";
      const badgesWrap = document.createElement("div");
      badgesWrap.className = "mt-1";
      if (u.badges && u.badges.length) {
        u.badges.slice(0, 3).forEach((b) => {
          const bEl = document.createElement("span");
          bEl.className = "badge bg-secondary me-1";
          bEl.textContent = b;
          badgesWrap.appendChild(bEl);
        });
      }
      right.appendChild(score);
      right.appendChild(badgesWrap);
      item.appendChild(left);
      item.appendChild(right);
      list.appendChild(item);
    });
    container.innerHTML = "";
    container.appendChild(list);
  }

  async function load() {
    container.innerHTML =
      '<div class="text-muted">Loading leaderboard...</div>';
    const period = periodSel ? periodSel.value : "weekly";
    const metric = metricSel ? metricSel.value : "checkins";
    const payload = await fetchLeaderboard(period, metric);
    if (!payload) {
      container.innerHTML =
        '<div class="text-danger">Failed to load leaderboard.</div>';
      return;
    }
    if (!payload.leaders) {
      container.innerHTML = '<div class="text-muted">No results.</div>';
      return;
    }
    renderList(payload.leaders);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener("click", load);
  }
  // auto load
  load();
})();
