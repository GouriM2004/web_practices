// Handles opening the edit modal and submitting profile updates
document.addEventListener("DOMContentLoaded", function () {
  const editBtn = document.getElementById("editProfileBtn");
  const modalEl = document.getElementById("editProfile");
  const saveBtn = document.getElementById("saveProfileBtn");
  const profileForm = document.getElementById("profileForm");

  if (!editBtn || !modalEl) return;

  // bootstrap modal
  let modal;
  try {
    modal = new bootstrap.Modal(modalEl);
  } catch (e) {
    // no bootstrap JS loaded yet; ensure it's present via header/footer
  }

  editBtn.addEventListener("click", (e) => {
    e.preventDefault();
    if (modal) modal.show();
    else modalEl.style.display = "block";
  });

  saveBtn.addEventListener("click", async () => {
    const form = new FormData(profileForm);
    const payload = {
      bio: form.get("bio"),
      cover_photo: form.get("cover_photo"),
      motivational_quote: form.get("motivational_quote"),
      show_streaks_public: form.get("show_streaks_public") ? true : false,
    };
    try {
      const res = await fetch("api.php/me/profile", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (!res.ok) {
        alert(json.error || "Failed to update profile");
        return;
      }
      // on success, reload page to show updates
      window.location.reload();
    } catch (e) {
      console.error(e);
      alert("Network error");
    }
  });
});
