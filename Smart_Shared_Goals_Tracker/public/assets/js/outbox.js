// Minimal IndexedDB outbox helper for offline check-ins
(function (global) {
  const DB_NAME = "smart-goals-pwa";
  const DB_VERSION = 1;
  const STORE = "outbox";

  function openDB() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains(STORE)) {
          const s = db.createObjectStore(STORE, {
            keyPath: "id",
            autoIncrement: true,
          });
          s.createIndex("client_key", "client_idempotency_key", {
            unique: false,
          });
        }
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async function add(item) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, "readwrite");
      const store = tx.objectStore(STORE);
      const now = new Date().toISOString();
      const toStore = Object.assign({ created_at: now }, item);
      const rq = store.add(toStore);
      rq.onsuccess = () => resolve(rq.result);
      rq.onerror = () => reject(rq.error);
    });
  }

  async function getAll() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, "readonly");
      const store = tx.objectStore(STORE);
      const req = store.getAll();
      req.onsuccess = () => resolve(req.result || []);
      req.onerror = () => reject(req.error);
    });
  }

  async function remove(id) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, "readwrite");
      const store = tx.objectStore(STORE);
      const rq = store.delete(id);
      rq.onsuccess = () => resolve(true);
      rq.onerror = () => reject(rq.error);
    });
  }

  async function clearAll() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE, "readwrite");
      const store = tx.objectStore(STORE);
      const rq = store.clear();
      rq.onsuccess = () => resolve(true);
      rq.onerror = () => reject(rq.error);
    });
  }

  async function sendOutbox() {
    const items = await getAll();
    if (!items || items.length === 0) return { sent: 0 };
    try {
      const payload = {
        items: items.map((i) => ({
          client_idempotency_key: i.client_idempotency_key,
          goal_id: i.goal_id,
          date: i.date,
          value: i.value,
          note: i.note,
        })),
      };

      const res = await fetch(
        "/Smart_Shared_Goals_Tracker/public/api.php/sync",
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
          credentials: "include",
        }
      );
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || "Sync failed");

      // remove accepted items by client key
      if (Array.isArray(json.accepted)) {
        for (const a of json.accepted) {
          const clientKey = a.client_idempotency_key;
          const found = items.find(
            (it) => it.client_idempotency_key === clientKey
          );
          if (found) await remove(found.id);
        }
      }
      return { sent: json.accepted ? json.accepted.length : 0, result: json };
    } catch (err) {
      console.warn("Outbox sync failed", err);
      throw err;
    }
  }

  // expose
  global.Outbox = { add, getAll, remove, clearAll, sendOutbox };
})(window);

