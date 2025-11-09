// Minimal client script: register service worker and simple install prompt handling
if ("serviceWorker" in navigator) {
  navigator.serviceWorker
    .register("/Smart_Shared_Goals_Tracker/public/service-worker.js")
    .then(() => console.log("Service worker registered"))
    .catch((err) => console.warn("SW registration failed", err));
}

// basePath: compute dynamically from current pathname (strip last PHP filename)
const basePath = window.location.pathname.replace(/\/[^\/]*\.php.*$/i, "");

let deferredPrompt;
window.addEventListener("beforeinstallprompt", (e) => {
  e.preventDefault();
  deferredPrompt = e;
  const btn = document.getElementById("installBtn");
  if (btn) btn.style.display = "inline-block";
  btn &&
    btn.addEventListener("click", async () => {
      btn.style.display = "none";
      deferredPrompt.prompt();
      const choice = await deferredPrompt.userChoice;
      console.log("User choice", choice);
      deferredPrompt = null;
    });
});

// Listen to messages from SW
navigator.serviceWorker &&
  navigator.serviceWorker.addEventListener("message", (ev) => {
    console.log("SW message", ev.data);
    if (ev.data && ev.data.type === "sync-outbox") {
      // Trigger client outbox sync (IndexedDB -> /api/sync)
      if (window.Outbox && typeof window.Outbox.sendOutbox === "function") {
        window.Outbox.sendOutbox()
          .then((res) => console.log("Outbox sync result", res))
          .catch((err) => console.warn("Outbox sync err", err));
      }
    }
  });

// helper: uuid v4
function uuidv4() {
  return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
    (
      c ^
      (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))
    ).toString(16)
  );
}

// Check-in helper: try to send, fallback to outbox
async function checkIn(
  goalId,
  { value = null, note = null, date = null } = {}
) {
  date = date || new Date().toISOString().slice(0, 10);
  const payload = {
    user_id: null, // server uses session user
    date,
    value,
    note,
    client_idempotency_key: uuidv4(),
  };
  try {
    const res = await fetch(`${basePath}/api.php/goals/${goalId}/checkins`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
      credentials: "include",
    });
    if (!res.ok) throw new Error("Network error");
    return await res.json();
  } catch (err) {
    // save to outbox
    if (window.Outbox && typeof window.Outbox.add === "function") {
      await window.Outbox.add({
        client_idempotency_key: payload.client_idempotency_key,
        goal_id: goalId,
        date,
        value,
        note,
      });
      // register background sync if available
      if (navigator.serviceWorker && "SyncManager" in window) {
        const reg = await navigator.serviceWorker.ready;
        try {
          await reg.sync.register("sync-outbox");
        } catch (e) {
          console.warn("Background sync register failed", e);
        }
      }
      return {
        status: "queued",
        client_idempotency_key: payload.client_idempotency_key,
      };
    }
    throw err;
  }
}

// expose checkIn globally for quick testing
window.checkIn = checkIn;

// Delegate delete goal actions (works on goals.php and group.php buttons)
document.addEventListener("click", async function (e) {
  const btn = e.target.closest(".delete-goal");
  if (!btn) return;
  const goalId = btn.dataset.goalId;
  if (!goalId) return;
  if (
    !confirm(
      "Delete this goal? This will remove all check-ins and cannot be undone."
    )
  )
    return;
  btn.disabled = true;
  const old = btn.textContent;
  btn.textContent = "Deleting...";
  try {
    const res = await fetch(
      `${basePath}/api.php/goals/${encodeURIComponent(goalId)}`,
      {
        method: "DELETE",
        credentials: "include",
      }
    );
    const txt = await res.text();
    let payload = null;
    try {
      payload = JSON.parse(txt);
    } catch (e) {
      payload = null;
    }
    if (!res.ok) {
      const err =
        payload && (payload.error || payload.message)
          ? payload.error || payload.message
          : txt || "Request failed: " + res.status;
      UI.toast(err, "danger");
      return;
    }
    UI.toast("Goal deleted", "success");
    // remove list item from DOM if present
    const li =
      btn.closest("li[data-id]") ||
      btn.closest("li[data-goal-id]") ||
      btn.closest("li");
    if (li && li.parentNode) li.parentNode.removeChild(li);
  } catch (err) {
    console.error("delete goal failed", err);
    UI.toast("Delete failed", "danger");
  } finally {
    btn.disabled = false;
    btn.textContent = old;
  }
});
