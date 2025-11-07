// Registration page script. Runs after app.js (which defines Api) and ui.js (which defines UI.toast)
(function () {
  const form = document.getElementById("registerForm");
  const msg = document.getElementById("msg");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    msg.textContent = "";

    const data = {
      name: form.name.value.trim(),
      email: form.email.value.trim(),
      password: form.password.value,
    };

    // Build an absolute api.php path based on current location so path-resolution works under subfolders
    const base = window.location.pathname.replace(/\/register\.php$/, "");
    const apiPath = base + "/api.php/register";

    try {
      const json = await Api.post(apiPath, data);
      UI.toast("Registered — redirecting...", "success");
      setTimeout(() => (location.href = "dashboard.php"), 700);
    } catch (err) {
      console.error("register error", err);
      msg.textContent =
        err && err.body && err.body.error
          ? err.body.error
          : err && err.status
          ? "Request failed (status " + err.status + ")"
          : "Registration failed";
      console.info(
        "If registration keeps failing, open DevTools -> Network, inspect POST",
        apiPath,
        "and paste response body here."
      );
    }
  });
})();
