(function () {
  // Small helper to avoid XSS when inserting server-provided text into the DOM
  function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  // basePath: strip /group.php and anything after so we can build absolute API paths
  const basePath = window.location.pathname.replace(/\/group\.php.*$/i, "");

  // Prefer explicit data attribute set server-side, fall back to query string
  let gid = null;
  const pageEl = document.getElementById("groupPage");
  if (pageEl && pageEl.dataset && pageEl.dataset.groupId)
    gid = pageEl.dataset.groupId;
  if (!gid) gid = new URLSearchParams(window.location.search).get("id");

  // Quick runtime debug: confirm this file executed and show gid/basePath
  try {
    console.info("group.js loaded", { gid: gid || null, basePath: basePath });
  } catch (e) {
    console.info("group.js loaded (partial)");
  }

  // Join group button (visible when user is not a member)
  const joinBtn = document.getElementById("joinBtn");
  if (joinBtn) {
    joinBtn.addEventListener("click", async (e) => {
      try {
        if (!gid) {
          UI.toast("Missing group id.", "danger");
          return;
        }
        const url =
          basePath + "/api.php/groups/" + encodeURIComponent(gid) + "/join";
        console.info("Joining group", { url });
        const res = await fetch(url, {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({}),
        });
        const txt = await res.text();
        let payload = null;
        try {
          payload = JSON.parse(txt);
        } catch (e) {
          payload = null;
        }
        if (!res.ok) {
          const err =
            payload && payload.error
              ? payload.error
              : txt || "Request failed: " + res.status;
          UI.toast(err, "danger");
          return;
        }
        UI.toast("Joined group", "success");
        setTimeout(() => location.reload(), 600);
      } catch (err) {
        console.error("join error", err);
        UI.toast("Join failed", "danger");
      }
    });
  } else {
    console.info("group.js: joinBtn not found on page");
  }

  // Improved Add Member handler (owners/admins only):
  // - Validate email locally
  // - Check whether the email corresponds to an existing user (GET /api.php/users?email=...)
  // - If user exists -> POST /api.php/groups/{gid}/members
  // - If user does not exist -> POST /api.php/groups/{gid}/invites
  // - Update the members list (or show invite confirmation) and surface clear messages
  (function attachAddMember() {
    const addForm = document.getElementById("addMemberForm");
    const addBtn = document.getElementById("addMemberBtn");
    if (!addForm || !addBtn) {
      console.info("group.js: add-member form not present");
      return;
    }

    const msgEl = document.getElementById("addMemberMsg");

    function setMsg(html, isError) {
      if (!msgEl) return;
      msgEl.innerHTML = html;
      msgEl.classList.toggle("text-danger", !!isError);
    }

    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    addBtn.addEventListener("click", async () => {
      setMsg("", false);
      const email = (addForm.email.value || "").trim();
      const role = addForm.role.value || "member";
      if (!email) {
        setMsg('<span class="text-danger">Please enter an email.</span>', true);
        return;
      }
      if (!emailRe.test(email)) {
        setMsg(
          '<span class="text-danger">Please enter a valid email address.</span>',
          true
        );
        return;
      }
      if (!gid) {
        setMsg('<span class="text-danger">Missing group id.</span>', true);
        return;
      }

      addBtn.disabled = true;
      const originalText = addBtn.textContent;
      addBtn.textContent = "Working...";

      try {
        // 1) Try to fetch user by email
        const userUrl =
          basePath + "/api.php/users?email=" + encodeURIComponent(email);
        let userFound = null;
        try {
          const r = await fetch(userUrl, { credentials: "include" });
          const txt = await r.text();
          let payload = null;
          try {
            payload = JSON.parse(txt);
          } catch (e) {
            payload = null;
          }
          if (r.ok && payload) {
            // API returns user object
            userFound = payload.user || payload;
          } else if (r.status === 404) {
            userFound = null;
          } else {
            // Unexpected response: treat as not-found but inform the user
            if (r.status === 401) {
              setMsg(
                '<span class="text-danger">You must be signed in to perform this action.</span>',
                true
              );
              return;
            }
            // If API doesn't support user lookup, fall back to attempting member add which will return meaningful error
            userFound = null;
          }
        } catch (err) {
          // network error while checking user - we'll fall back to trying member-add which will return proper error
          console.warn("user lookup failed, will attempt add/invite", err);
          userFound = null;
        }

        if (userFound) {
          // 2a) Add existing user directly as member
          const url =
            basePath +
            "/api.php/groups/" +
            encodeURIComponent(gid) +
            "/members";
          const r = await fetch(url, {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, role }),
          });
          const txt = await r.text();
          let payload = null;
          try {
            payload = JSON.parse(txt);
          } catch (e) {
            payload = null;
          }
          if (!r.ok) {
            const err =
              payload && (payload.error || payload.message)
                ? payload.error || payload.message
                : txt || "Request failed: " + r.status;
            setMsg(
              '<span class="text-danger">' + escapeHtml(err) + "</span>",
              true
            );
            return;
          }

          UI.toast("Member added", "success");
          addForm.reset();
          // Append to members list if payload included user info
          const membersList = document.getElementById("membersList");
          const member = payload && payload.user ? payload.user : userFound;
          if (membersList && member) {
            const li = document.createElement("li");
            li.className =
              "list-group-item d-flex justify-content-between align-items-center";
            const name = document.createElement("div");
            name.innerHTML =
              escapeHtml(member.name || "") +
              ' <div class="small text-muted">' +
              escapeHtml(member.email || "") +
              "</div>";
            const roleDiv = document.createElement("div");
            roleDiv.className = "small text-muted";
            roleDiv.textContent = role || "member";
            li.appendChild(name);
            li.appendChild(roleDiv);
            membersList.insertBefore(li, membersList.firstChild);
          } else {
            // fallback: refresh to show new member
            setTimeout(() => location.reload(), 400);
          }
        } else {
          // 2b) No user found -> create an invite
          const invUrl =
            basePath +
            "/api.php/groups/" +
            encodeURIComponent(gid) +
            "/invites";
          const r2 = await fetch(invUrl, {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, role }),
          });
          const txt2 = await r2.text();
          let payload2 = null;
          try {
            payload2 = JSON.parse(txt2);
          } catch (e) {
            payload2 = null;
          }
          if (!r2.ok) {
            const err =
              payload2 && (payload2.error || payload2.message)
                ? payload2.error || payload2.message
                : txt2 || "Request failed: " + r2.status;
            setMsg(
              '<span class="text-danger">' + escapeHtml(err) + "</span>",
              true
            );
            return;
          }

          UI.toast("Invite created", "success");
          addForm.reset();
          // Optionally append to an invites list if present
          const invitesList = document.getElementById("invitesList");
          if (invitesList && payload2 && payload2.invite) {
            const inv = payload2.invite || payload2;
            const li = document.createElement("li");
            li.className =
              "list-group-item d-flex justify-content-between align-items-center";
            li.setAttribute("data-invite-id", inv.invite_id || inv.id || "");
            const left = document.createElement("div");
            left.innerHTML =
              escapeHtml(inv.email || email) +
              ' <div class="small text-muted">invited as ' +
              escapeHtml(inv.role || role) +
              "</div>";
            li.appendChild(left);
            const right = document.createElement("div");
            // actions: Resend / Cancel
            right.innerHTML =
              '<button class="btn btn-sm btn-outline-secondary resend-invite" data-invite-id="' +
              (inv.invite_id || inv.id || "") +
              '">Resend</button> <button class="btn btn-sm btn-outline-danger cancel-invite" data-invite-id="' +
              (inv.invite_id || inv.id || "") +
              '">Cancel</button>';
            li.appendChild(right);
            invitesList.insertBefore(li, invitesList.firstChild);
          } else {
            // no invites UI: show message in the small msg area
            setMsg(
              '<span class="text-success">Invite created for ' +
                escapeHtml(email) +
                ".</span>",
              false
            );
          }
        }
      } catch (err) {
        console.error("add member flow failed", err);
        setMsg(
          '<span class="text-danger">Network error. See console.</span>',
          true
        );
      } finally {
        addBtn.disabled = false;
        addBtn.textContent = originalText;
      }
    });
  })();
})();

// Event delegation for invite actions: Resend and Cancel
document.addEventListener(
  "click",
  async function (e) {
    const target = e.target;
    // compute gid/basePath here as this listener lives outside the main IIFE
    const pageEl = document.getElementById("groupPage");
    let gid = null;
    if (pageEl && pageEl.dataset && pageEl.dataset.groupId)
      gid = pageEl.dataset.groupId;
    if (!gid) gid = new URLSearchParams(window.location.search).get("id");
    const basePath = window.location.pathname.replace(/\/group\.php.*$/i, "");
    if (target.matches(".resend-invite")) {
      const inviteId = target.dataset.inviteId;
      if (!inviteId || !gid) return;
      target.disabled = true;
      const old = target.textContent;
      target.textContent = "Resending...";
      try {
        const url =
          basePath +
          "/api.php/groups/" +
          encodeURIComponent(gid) +
          "/invites/" +
          encodeURIComponent(inviteId) +
          "/resend";
        const r = await fetch(url, { method: "POST", credentials: "include" });
        const txt = await r.text();
        let payload = null;
        try {
          payload = JSON.parse(txt);
        } catch (e) {
          payload = null;
        }
        if (!r.ok) {
          const err =
            payload && (payload.error || payload.message)
              ? payload.error || payload.message
              : txt || "Request failed: " + r.status;
          UI.toast(err, "danger");
          return;
        }
        UI.toast("Invite resent", "success");
        // Optionally update the time display; here we simply update parent small text if present
        const li = target.closest("li");
        if (li) {
          const small = li.querySelector(".small.text-muted");
          if (small) small.textContent = "Invited · just now";
        }
      } catch (err) {
        console.error("resend invite failed", err);
        UI.toast("Resend failed", "danger");
      } finally {
        target.disabled = false;
        target.textContent = old;
      }
    }

    if (target.matches(".cancel-invite")) {
      const inviteId = target.dataset.inviteId;
      if (!inviteId || !gid) return;
      if (!confirm("Cancel this invite?")) return;
      try {
        const url =
          basePath +
          "/api.php/groups/" +
          encodeURIComponent(gid) +
          "/invites/" +
          encodeURIComponent(inviteId);
        const r = await fetch(url, {
          method: "DELETE",
          credentials: "include",
        });
        const txt = await r.text();
        let payload = null;
        try {
          payload = JSON.parse(txt);
        } catch (e) {
          payload = null;
        }
        if (!r.ok) {
          const err =
            payload && (payload.error || payload.message)
              ? payload.error || payload.message
              : txt || "Request failed: " + r.status;
          UI.toast(err, "danger");
          return;
        }
        UI.toast("Invite cancelled", "success");
        // remove li from DOM
        const li = target.closest("li");
        if (li) li.parentNode.removeChild(li);
      } catch (err) {
        console.error("cancel invite failed", err);
        UI.toast("Cancel failed", "danger");
      }
    }

    // Leave group (self-removal)
    if (target.matches("#leaveGroupBtn")) {
      if (!gid) return;
      if (!confirm("Are you sure you want to leave this group?")) return;
      try {
        target.disabled = true;
        const url =
          basePath + "/api.php/groups/" + encodeURIComponent(gid) + "/leave";
        const r = await fetch(url, { method: "POST", credentials: "include" });
        const txt = await r.text();
        let payload = null;
        try {
          payload = JSON.parse(txt);
        } catch (e) {
          payload = null;
        }
        if (!r.ok) {
          const err =
            payload && (payload.error || payload.message)
              ? payload.error || payload.message
              : txt || "Request failed: " + r.status;
          UI.toast(err, "danger");
          return;
        }
        UI.toast("You left the group", "success");
        // redirect back to groups list
        setTimeout(() => {
          location.href = basePath + "/groups.php";
        }, 400);
      } catch (err) {
        console.error("leave group failed", err);
        UI.toast("Leave failed", "danger");
      } finally {
        try {
          target.disabled = false;
        } catch (e) {}
      }
    }

    // Remove member (owners and admins - permission enforced server-side)
    if (target.matches(".remove-member-btn")) {
      const memberId = target.dataset.memberId;
      if (!memberId || !gid) return;
      if (!confirm("Remove this member from the group?")) return;
      try {
        target.disabled = true;
        const url =
          basePath +
          "/api.php/groups/" +
          encodeURIComponent(gid) +
          "/members/" +
          encodeURIComponent(memberId);
        const r = await fetch(url, {
          method: "DELETE",
          credentials: "include",
        });
        const txt = await r.text();
        let payload = null;
        try {
          payload = JSON.parse(txt);
        } catch (e) {
          payload = null;
        }
        if (!r.ok) {
          const err =
            payload && (payload.error || payload.message)
              ? payload.error || payload.message
              : txt || "Request failed: " + r.status;
          UI.toast(err, "danger");
          return;
        }
        UI.toast("Member removed", "success");
        // remove list item from DOM
        const li = target.closest("li");
        if (li) li.parentNode.removeChild(li);
      } catch (err) {
        console.error("remove member failed", err);
        UI.toast("Remove failed", "danger");
      } finally {
        try {
          target.disabled = false;
        } catch (e) {}
      }
    }
  },
  false
);
