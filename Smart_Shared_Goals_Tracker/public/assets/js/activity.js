(async function () {
  const container = document.getElementById("activityList");
  try {
    const res = await fetch("api.php/activity", { credentials: "include" });
    const json = await res.json();
    if (!res.ok) throw json;
    render(json.activities || []);
  } catch (err) {
    console.error("activity error", err);
    container.innerHTML = '<p class="text-danger">Error loading activity</p>';
  }

  function render(items) {
    if (!items.length) {
      container.innerHTML = "<p>No recent activity.</p>";
      return;
    }
    const ul = document.createElement("ul");
    ul.className = "list-group";
    items.forEach((it) => {
      const li = document.createElement("li");
      li.className = "list-group-item";
      const who = it.user_id ? "User " + it.user_id : "System";
      const at = new Date(it.created_at).toLocaleString();
      const action = escapeHtml(it.action);
      let meta = "";
      try {
        meta = it.meta
          ? " — " + escapeHtml(JSON.stringify(JSON.parse(it.meta)))
          : "";
      } catch (e) {
        meta = "";
      }
      li.innerHTML = `<div class="small text-muted">${at} • ${who}</div><div>${action}${meta}</div>`;
      ul.appendChild(li);
    });
    container.innerHTML = "";
    container.appendChild(ul);
  }

  function escapeHtml(s) {
    return String(s || "").replace(/[&<>"']/g, function (c) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[c];
    });
  }
})();
