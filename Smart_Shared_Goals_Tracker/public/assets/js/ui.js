// Small UI helpers
window.UI = {
  toast(msg, type = "info") {
    const el = document.createElement("div");
    el.className =
      "alert alert-" +
      (type === "error" ? "danger" : type) +
      " position-fixed top-0 end-0 m-3";
    el.style.zIndex = 9999;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
  },
};
