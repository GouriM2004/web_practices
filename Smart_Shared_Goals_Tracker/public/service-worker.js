const CACHE_NAME = "smart-goals-shell-v1";
const ASSETS = [
  "/Smart_Shared_Goals_Tracker/public/",
  "/Smart_Shared_Goals_Tracker/public/index.php",
  "/Smart_Shared_Goals_Tracker/public/manifest.json",
  "/Smart_Shared_Goals_Tracker/public/assets/js/app.js",
];

self.addEventListener("install", (e) => {
  self.skipWaiting();
  e.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
});

self.addEventListener("activate", (e) => {
  e.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", (e) => {
  const req = e.request;
  // Cache-first for GET assets
  if (req.method === "GET") {
    e.respondWith(
      caches
        .match(req)
        .then(
          (res) =>
            res ||
            fetch(req).catch(() =>
              caches.match("/Smart_Shared_Goals_Tracker/public/")
            )
        )
    );
    return;
  }
  // For POST requests (e.g., check-ins) we just attempt network and let client handle retries via IndexedDB
  e.respondWith(
    fetch(req).catch(
      (err) =>
        new Response(JSON.stringify({ error: "offline" }), {
          headers: { "Content-Type": "application/json" },
        })
    )
  );
});

// TODO: add background sync handling for outbox when supported
self.addEventListener("sync", (event) => {
  if (event.tag === "sync-outbox") {
    event.waitUntil(
      self.clients.matchAll().then((clients) => {
        // notify client to flush outbox
        clients.forEach((c) => c.postMessage({ type: "sync-outbox" }));
      })
    );
  }
});
