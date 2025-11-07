(async function () {
  async function fetchGroups() {
    try {
      const res = await fetch("api.php/groups", { credentials: "include" });
      const json = await res.json();
      if (!res.ok) throw json;
      renderGroups(json.member_groups || [], json.public_groups || []);
    } catch (err) {
      console.error("Error fetching groups", err);
      document.getElementById("memberGroups").innerHTML =
        '<li class="list-group-item text-danger">Error loading</li>';
    }
  }

  function renderGroups(members, publics) {
    const mg = document.getElementById("memberGroups");
    const pg = document.getElementById("publicGroups");
    mg.innerHTML = "";
    pg.innerHTML = "";
    if (!members.length)
      mg.innerHTML =
        '<li class="list-group-item">You are not a member of any groups.</li>';
    members.forEach((g) => {
      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-start";
      li.innerHTML = `<div><div class="fw-bold">${escapeHtml(
        g.name
      )}</div><div class="small text-muted">${escapeHtml(
        g.description || ""
      )}</div></div><div><a class="btn btn-sm btn-outline-primary" href="group.php?id=${
        g.id
      }">Open</a></div>`;
      mg.appendChild(li);
    });
    if (!publics.length)
      pg.innerHTML = '<li class="list-group-item">No public groups yet.</li>';
    publics.forEach((g) => {
      const li = document.createElement("li");
      li.className =
        "list-group-item d-flex justify-content-between align-items-start";
      li.innerHTML = `<div><div class="fw-bold">${escapeHtml(
        g.name
      )}</div><div class="small text-muted">${escapeHtml(
        g.description || ""
      )}</div></div><div><button data-id="${
        g.id
      }" class="btn btn-sm btn-primary joinBtn">Join</button></div>`;
      pg.appendChild(li);
    });

    // wire join buttons
    document.querySelectorAll(".joinBtn").forEach((b) =>
      b.addEventListener("click", async (e) => {
        const id = e.target.dataset.id;
        if (!confirm("Join this group?")) return;
        try {
          const res = await fetch("api.php/groups/" + id + "/join", {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({}),
          });
          const json = await res.json();
          if (!res.ok) throw json;
          UI.toast("Joined group", "success");
          fetchGroups();
        } catch (err) {
          console.error("join error", err);
          UI.toast(err && err.error ? err.error : "Join failed", "danger");
        }
      })
    );
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

  // create group form
  const form = document.getElementById("createGroupForm");
  form &&
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const data = {
        name: form.name.value.trim(),
        description: form.description.value.trim(),
        privacy: form.privacy.value,
      };
      try {
        const res = await fetch("api.php/groups", {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data),
        });
        const json = await res.json();
        if (!res.ok) throw json;
        UI.toast("Group created", "success");
        form.reset();
        fetchGroups();
      } catch (err) {
        console.error("create group error", err);
        UI.toast(err && err.error ? err.error : "Create failed", "danger");
      }
    });

  // initial
  fetchGroups();
})();
