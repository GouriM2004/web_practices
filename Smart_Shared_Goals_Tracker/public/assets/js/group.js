(function () {
  const joinBtn = document.getElementById("joinBtn");
  if (!joinBtn) return;
  const gid = new URLSearchParams(window.location.search).get("id");
  joinBtn.addEventListener("click", async () => {
    const code = prompt(
      "If this is a private group, enter the code (leave blank for public groups):"
    );
    try {
      const res = await fetch("api.php/groups/" + gid + "/join", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code: code || null }),
      });
      const json = await res.json();
      if (!res.ok) throw json;
      UI.toast("Joined group", "success");
      setTimeout(() => location.reload(), 600);
    } catch (err) {
      console.error("join error", err);
      UI.toast(err && err.error ? err.error : "Join failed", "danger");
    }
  });
})();
