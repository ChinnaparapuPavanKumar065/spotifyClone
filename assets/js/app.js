(function () {
  const STORAGE_KEY = "melodix_state";
  const path = (window.location.pathname || "").replace(/\\/g, "/");
  const currentFile = path.split("/").pop().toLowerCase();
  const currentSection = path.includes("/admin/") ? "admin" : path.includes("/user/") ? "user" : "root";
  const publicUserPages = new Set(["landing.html", "signup.html", "user_login.html", "forgot_password.html"]);
  const publicAdminPages = new Set(["admin_login.html"]);

  const demoState = {
    users: [
      {
        fullName: "Alex Rivera",
        username: "alexrivera",
        email: "alex@melodix.io",
        password: "Demo@12345",
        profileImage: "",
      },
    ],
    currentUser: null,
    admins: [
      {
        username: "admin",
        password: "Admin@12345",
      },
    ],
    currentAdmin: null,
    settings: {
      displayName: "Alex Rivera",
      email: "alex@melodix.io",
    },
  };

  const routes = {
    home: "user/dashboard.html",
    search: "user/search.html",
    library: "user/your_library.html",
    "liked songs": "user/your_library.html",
    playlists: "user/playlist_details.html",
    podcasts: "user/podcasts.html",
    settings: "user/settings.html",
    logout: "user/user_login.html",
    explore: "user/search.html",
    upgrade: "user/landing.html",
    downloads: "user/downloads.html",
    "log in": "user/user_login.html",
    signup: "user/signup.html",
    "sign up": "user/signup.html",
    "forgot password?": "user/forgot_password.html",
    "back to login": "user/user_login.html",
    dashboard: "admin/admin_dashboard.html",
    "manage songs": "admin/admin_manage_songs.html",
    "manage users": "admin/admin_manage_users.html",
    "manage playlists": "admin/Admin_Manage_Playlists.html",
    "manage podcasts": "admin/admin_manage_podcasts.html",
    analytics: "admin/Admin_Analytics.html",
    "admin dashboard": "admin/admin_login.html",
    "add new song": "admin/admin_add_new_song.html",
    "add new show": "admin/admin_add_new_podcast.html",
    "last 30 days": "admin/Admin_Analytics.html",
  };

  function getState() {
    try {
      const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
      if (!saved) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(demoState));
        return structuredClone(demoState);
      }
      return Object.assign(structuredClone(demoState), saved);
    } catch (error) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(demoState));
      return structuredClone(demoState);
    }
  }

  function setState(nextState) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(nextState));
  }

  function normalize(text) {
    return (text || "")
      .replace(/\s+/g, " ")
      .replace(/[^\w\s?]/g, "")
      .trim()
      .toLowerCase();
  }

  function routeLabel(node) {
    const clone = node.cloneNode(true);
    clone.querySelectorAll(".material-symbols-outlined").forEach((icon) => icon.remove());
    return normalize(clone.textContent);
  }

  function resolveRoute(target) {
    if (!target) return "#";
    if (currentSection === "user") {
      return target.startsWith("user/") ? target.slice(5) : `../${target}`;
    }
    if (currentSection === "admin") {
      return target.startsWith("admin/") ? target.slice(6) : `../${target}`;
    }
    return target;
  }

  function goTo(target) {
    window.location.href = resolveRoute(target);
  }

  function ensureToastContainer() {
    let container = document.querySelector(".melodix-toast-container");
    if (!container) {
      container = document.createElement("div");
      container.className = "toast-container melodix-toast-container";
      document.body.appendChild(container);
    }
    return container;
  }

  function showToast(title, message) {
    const container = ensureToastContainer();
    const toast = document.createElement("div");
    toast.className = "toast melodix-toast align-items-center border-0";
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "assertive");
    toast.setAttribute("aria-atomic", "true");
    toast.innerHTML = `
      <div class="toast-header">
        <strong class="me-auto">${title}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">${message}</div>
    `;
    container.appendChild(toast);
    const instance = window.bootstrap ? new window.bootstrap.Toast(toast, { delay: 2200 }) : null;
    if (instance) {
      toast.addEventListener("hidden.bs.toast", () => toast.remove());
      instance.show();
    } else {
      setTimeout(() => toast.remove(), 2200);
    }
  }

  function attachRoute(element, target, handler) {
    if (!element) return;
    const resolved = resolveRoute(target);
    if (element.tagName === "A") {
      element.href = resolved;
    } else {
      element.dataset.route = resolved;
      element.addEventListener("click", function (event) {
        if (handler) {
          handler(event);
          return;
        }
        if (element.type === "submit") return;
        event.preventDefault();
        window.location.href = resolved;
      });
    }
  }

  function wireNavigation() {
    document.querySelectorAll("a").forEach((anchor) => {
      const key = routeLabel(anchor);
      if (routes[key]) {
        anchor.href = resolveRoute(routes[key]);
      }
    });

    document.querySelectorAll("button").forEach((button) => {
      const key = routeLabel(button);
      if (!routes[key] || button.type === "submit") return;
      if (button.dataset.routeBound === "true") return;
      button.dataset.routeBound = "true";
      attachRoute(button, routes[key]);
    });

    document.querySelectorAll("[data-click-route]").forEach((node) => {
      if (node.dataset.routeBound === "true") return;
      node.dataset.routeBound = "true";
      node.classList.add("clickable-card");
      node.addEventListener("click", () => goTo(node.dataset.clickRoute));
    });

    document.querySelectorAll("a, button").forEach((node) => {
      if (routeLabel(node) !== "logout") return;
      node.addEventListener("click", function (event) {
        event.preventDefault();
        const state = getState();
        state.currentUser = null;
        setState(state);
        showToast("Logged out", "Your session has been cleared.");
        setTimeout(() => goTo("user/user_login.html"), 350);
      });
    });
  }

  function applyBootstrapClasses(field) {
    if (field.matches("input[type='checkbox']")) {
      field.classList.add("form-check-input");
      return;
    }
    if (field.tagName === "SELECT") {
      field.classList.add("form-select");
      return;
    }
    field.classList.add("form-control");
  }

  function ensureFeedback(field) {
    if (field.type === "hidden" || field.type === "button" || field.type === "submit") return;
    if (field.parentElement && field.parentElement.querySelector(".invalid-feedback")) return;
    const feedback = document.createElement("div");
    feedback.className = "invalid-feedback";
    feedback.textContent = field.dataset.invalidMessage || "Please complete this field correctly.";
    field.insertAdjacentElement("afterend", feedback);
  }

  function prepareForms() {
    document.body.classList.add("bootstrap-validation-enabled");
    document.querySelectorAll("form").forEach((form) => {
      form.classList.add("needs-validation");
      form.setAttribute("novalidate", "novalidate");
      form.querySelectorAll("input, select, textarea").forEach((field) => {
        applyBootstrapClasses(field);
        ensureFeedback(field);
        field.addEventListener("input", () => field.setCustomValidity(""));
        field.addEventListener("change", () => field.setCustomValidity(""));
      });
    });
  }

  function bindValidation(form, onSuccess) {
    if (!form) return;
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      event.stopPropagation();

      const password = form.querySelector("#password");
      const confirm = form.querySelector("#confirmPassword");
      if (password && confirm) {
        confirm.setCustomValidity(password.value === confirm.value ? "" : "Passwords do not match.");
      }

      if (!form.checkValidity()) {
        form.classList.add("was-validated");
        showToast("Validation", "Please correct the highlighted fields and try again.");
        return;
      }

      form.classList.add("was-validated");
      onSuccess(new FormData(form));
    });
  }

  function handleSignup() {
    const form = document.querySelector("form");
    if (!form) return;
    bindValidation(form, function (formData) {
      const state = getState();
      const email = String(formData.get("email") || "").trim().toLowerCase();
      const username = String(formData.get("username") || "").trim().toLowerCase();
      const existing = state.users.find(
        (user) => user.email.toLowerCase() === email || user.username.toLowerCase() === username
      );

      if (existing) {
        const emailField = form.querySelector("#email");
        emailField.setCustomValidity("That email or username already exists.");
        form.classList.add("was-validated");
        emailField.reportValidity();
        showToast("Account exists", "Use a different email or username.");
        return;
      }

      const fileField = form.querySelector("#profileImage");
      state.users.push({
        fullName: String(formData.get("fullName") || "").trim(),
        username: String(formData.get("username") || "").trim(),
        email,
        password: String(formData.get("password") || ""),
        profileImage: fileField && fileField.files[0] ? fileField.files[0].name : "",
      });
      setState(state);
      showToast("Account created", "Registration is complete. Redirecting to login.");
      setTimeout(() => goTo("user/user_login.html"), 450);
    });
  }

  function handleUserLogin() {
    const form = document.querySelector("form");
    if (!form) return;
    bindValidation(form, function (formData) {
      const state = getState();
      const identifier = String(formData.get("identifier") || "").trim().toLowerCase();
      const password = String(formData.get("password") || "");
      const user = state.users.find(
        (item) => item.email.toLowerCase() === identifier || item.username.toLowerCase() === identifier
      );

      if (!user || user.password !== password) {
        const passwordField = form.querySelector("#password");
        passwordField.setCustomValidity("Invalid credentials.");
        form.classList.add("was-validated");
        passwordField.reportValidity();
        showToast("Login failed", "The username/email or password did not match.");
        return;
      }

      passwordFieldReset(form);
      state.currentUser = user;
      state.settings.displayName = user.fullName;
      state.settings.email = user.email;
      setState(state);
      showToast("Welcome back", `Signed in as ${user.fullName}.`);
      setTimeout(() => goTo("user/dashboard.html"), 350);
    });
  }

  function passwordFieldReset(form) {
    const passwordField = form.querySelector("#password");
    const emailField = form.querySelector("#email");
    if (passwordField) passwordField.setCustomValidity("");
    if (emailField) emailField.setCustomValidity("");
  }

  function handleForgotPassword() {
    const form = document.querySelector("form");
    if (!form) return;
    bindValidation(form, function (formData) {
      const email = String(formData.get("email") || "").trim();
      showToast("Reset link sent", `Password reset instructions were prepared for ${email}.`);
      setTimeout(() => goTo("user/user_login.html"), 450);
    });
  }

  function handleAdminLogin() {
    const form = document.querySelector("form");
    if (!form) return;
    bindValidation(form, function (formData) {
      const state = getState();
      const username = String(formData.get("username") || "").trim().toLowerCase();
      const password = String(formData.get("password") || "");
      const admin = state.admins.find(
        (item) => item.username.toLowerCase() === username && item.password === password
      );

      if (!admin) {
        const passwordField = form.querySelector("#password");
        passwordField.setCustomValidity("Invalid admin credentials.");
        form.classList.add("was-validated");
        passwordField.reportValidity();
        showToast("Access denied", "Admin credentials are not valid.");
        return;
      }

      state.currentAdmin = admin;
      setState(state);
      showToast("Admin access granted", "Redirecting to the dashboard.");
      setTimeout(() => goTo("admin/admin_dashboard.html"), 350);
    });
  }

  function handleSongForm() {
    const form = document.querySelector("#song-upload-form");
    if (!form) return;
    bindValidation(form, function () {
      showToast("Track published", "The song metadata passed validation and was queued.");
      setTimeout(() => goTo("admin/admin_manage_songs.html"), 450);
    });
  }

  function handlePodcastForm() {
    const form = document.querySelector("form");
    if (!form || currentFile !== "admin_add_new_podcast.html") return;
    bindValidation(form, function () {
      showToast("Podcast saved", "Step 1 is complete and ready for categorization.");
      setTimeout(() => goTo("admin/admin_manage_podcasts.html"), 450);
    });
  }

  function handleSettings() {
    const button = Array.from(document.querySelectorAll("button")).find(
      (item) => routeLabel(item) === "update profile"
    );
    if (!button) return;
    button.addEventListener("click", function () {
      const fields = document.querySelectorAll("#profile input");
      const displayName = fields[0] ? fields[0].value.trim() : "";
      const email = fields[1] ? fields[1].value.trim() : "";
      if (!displayName || !email) {
        showToast("Profile update", "Display name and email are required.");
        return;
      }
      const state = getState();
      state.settings.displayName = displayName;
      state.settings.email = email;
      if (state.currentUser) {
        state.currentUser.fullName = displayName;
        state.currentUser.email = email;
      }
      setState(state);
      showToast("Profile updated", "Your account settings were saved locally.");
    });
  }

  function enforceAccess() {
    const state = getState();
    if (currentSection === "user" && !publicUserPages.has(currentFile) && !state.currentUser) {
      goTo("user/user_login.html");
      return;
    }
    if (currentSection === "admin" && !publicAdminPages.has(currentFile) && !state.currentAdmin) {
      goTo("admin/admin_login.html");
    }
  }

  function hydrateSettings() {
    if (currentFile !== "settings.html") return;
    const state = getState();
    const profileSection = document.querySelector("#profile");
    if (!profileSection) return;
    const fields = profileSection.querySelectorAll("input");
    if (fields[0] && state.settings.displayName) fields[0].value = state.settings.displayName;
    if (fields[1] && state.settings.email) fields[1].value = state.settings.email;
  }

  function addDemoHints() {
    const demoMap = {
      "user_login.html": "Demo login: alex@melodix.io / Demo@12345",
      "admin_login.html": "Admin login: admin / Admin@12345",
    };
    const hint = demoMap[currentFile];
    const form = document.querySelector("form");
    if (!hint || !form) return;
    const note = document.createElement("p");
    note.className = "mt-3 text-center";
    note.style.color = "rgba(199, 198, 198, 0.8)";
    note.style.fontSize = "0.85rem";
    note.textContent = hint;
    form.insertAdjacentElement("afterend", note);
  }

  document.addEventListener("DOMContentLoaded", function () {
    enforceAccess();
    prepareForms();
    wireNavigation();
    hydrateSettings();
    addDemoHints();
    handleSettings();

    if (currentFile === "signup.html") handleSignup();
    if (currentFile === "user_login.html") handleUserLogin();
    if (currentFile === "forgot_password.html") handleForgotPassword();
    if (currentFile === "admin_login.html") handleAdminLogin();
    if (currentFile === "admin_add_new_song.html") handleSongForm();
    if (currentFile === "admin_add_new_podcast.html") handlePodcastForm();
  });
})();