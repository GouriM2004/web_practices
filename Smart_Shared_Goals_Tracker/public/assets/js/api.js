// Simple API helper for fetch with JSON and credentials
const Api = {
  async post(path, body) {
    const res = await fetch(path, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
      credentials: "include",
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw { status: res.status, body: json };
    return json;
  },
  async get(path) {
    const res = await fetch(path, { credentials: "include" });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw { status: res.status, body: json };
    return json;
  },
};
window.Api = Api;
