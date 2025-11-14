(function () {
  // Daily motivational quote widget
  const quoteText = document.getElementById("quoteText");
  const quoteAuthor = document.getElementById("quoteAuthor");
  const newBtn = document.getElementById("newQuoteBtn");
  if (!quoteText) return;

  // local fallback quotes (used if API fails)
  const fallback = [
    { text: "Small progress is still progress.", author: "Unknown" },
    { text: "Consistency builds momentum.", author: "Coach" },
    {
      text: "Do something today that your future self will thank you for.",
      author: "Unknown",
    },
    {
      text: "You don't have to be great to start, but you have to start to be great.",
      author: "Zig Ziglar",
    },
    {
      text: "The secret of getting ahead is getting started.",
      author: "Mark Twain",
    },
  ];

  function setQuote(q) {
    quoteText.textContent = q.text;
    quoteAuthor.textContent = q.author ? "— " + q.author : "";
  }

  // Use a per-day cache in localStorage so the quote is stable for the day
  function getDayKey() {
    const d = new Date();
    return (
      "quote_" + d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate()
    );
  }

  async function fetchRemote() {
    try {
      // Using quotable.io (CORS-enabled). If you'd prefer another API, change this URL.
      const r = await fetch("https://api.quotable.io/random");
      if (!r.ok) throw new Error("HTTP " + r.status);
      const j = await r.json();
      return { text: j.content, author: j.author };
    } catch (e) {
      console.warn("quote fetch failed", e);
      return null;
    }
  }

  async function loadQuote(force = false) {
    const key = getDayKey();
    if (!force) {
      const cached = localStorage.getItem(key);
      if (cached) {
        try {
          setQuote(JSON.parse(cached));
          return;
        } catch (e) {}
      }
    }

    // try remote API first
    const remote = await fetchRemote();
    if (remote) {
      setQuote(remote);
      try {
        localStorage.setItem(key, JSON.stringify(remote));
      } catch (e) {}
      return;
    }

    // fallback: pick pseudo-random entry from local list (stable per day)
    const seed = new Date().getDate();
    const idx = seed % fallback.length;
    const q = fallback[idx];
    setQuote(q);
    try {
      localStorage.setItem(key, JSON.stringify(q));
    } catch (e) {}
  }

  newBtn?.addEventListener("click", function () {
    // force a new quote (bypass daily cache)
    const key = getDayKey();
    try {
      localStorage.removeItem(key);
    } catch (e) {}
    loadQuote(true);
  });

  // initial load
  loadQuote();
})();
