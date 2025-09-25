<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login & Signup</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-card {
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      padding: 2rem;
      color: #fff;
    }
    .nav-tabs {
      border-bottom: none;
      justify-content: center;
      margin-bottom: 1rem;
    }
    .nav-tabs .nav-link {
      color: #bbb;
      border: none;
      border-radius: 10px;
      margin: 0 5px;
      transition: 0.3s;
    }
    .nav-tabs .nav-link.active {
      background-color: #00c6ff;
      color: #fff !important;
    }
    .form-control {
      border-radius: 12px;
      padding-left: 2.5rem;
    }
    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #aaa;
    }
    .btn-custom {
      border-radius: 12px;
      background: linear-gradient(to right, #00c6ff, #0072ff);
      color: white;
      font-weight: 600;
      transition: 0.3s;
    }
    .btn-custom:hover {
      opacity: 0.85;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="auth-card">
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="authTabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">Login</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" id="signup-tab" data-bs-toggle="tab" data-bs-target="#signup" type="button">Signup</button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- Login -->
          <div class="tab-pane fade show active" id="login">
            <form id="loginForm" onsubmit="return validateLogin()">
              <div class="mb-3 position-relative">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" class="form-control" id="loginEmail" placeholder="Email">
              </div>
              <div class="mb-3 position-relative">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" class="form-control" id="loginPassword" placeholder="Password">
              </div>
              <button type="submit" class="btn btn-custom w-100">Login</button>
            </form>
          </div>

          <!-- Signup -->
          <div class="tab-pane fade" id="signup">
            <form id="signupForm" onsubmit="return validateSignup()">
              <div class="mb-3 position-relative">
                <i class="bi bi-person input-icon"></i>
                <input type="text" class="form-control" id="signupName" placeholder="Full Name">
              </div>
              <div class="mb-3 position-relative">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" class="form-control" id="signupEmail" placeholder="Email">
              </div>
              <div class="mb-3 position-relative">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" class="form-control" id="signupPassword" placeholder="Password">
              </div>
              <div class="mb-3 position-relative">
                <i class="bi bi-shield-lock input-icon"></i>
                <input type="password" class="form-control" id="signupConfirmPassword" placeholder="Confirm Password">
              </div>
              <button type="submit" class="btn btn-custom w-100">Signup</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- JS Validation -->
<script>
  function validateLogin() {
    let email = document.getElementById("loginEmail").value.trim();
    let password = document.getElementById("loginPassword").value.trim();
    if (email === "" || password === "") {
      alert("Please fill in all login fields.");
      return false;
    }
    if (!email.includes("@")) {
      alert("Enter a valid email address.");
      return false;
    }
    alert("Login successful (demo)!");
    return true;
  }

  function validateSignup() {
    let name = document.getElementById("signupName").value.trim();
    let email = document.getElementById("signupEmail").value.trim();
    let password = document.getElementById("signupPassword").value.trim();
    let confirmPassword = document.getElementById("signupConfirmPassword").value.trim();

    if (name === "" || email === "" || password === "" || confirmPassword === "") {
      alert("Please fill in all signup fields.");
      return false;
    }
    if (!email.includes("@")) {
      alert("Enter a valid email address.");
      return false;
    }
    if (password.length < 6) {
      alert("Password must be at least 6 characters long.");
      return false;
    }
    if (password !== confirmPassword) {
      alert("Passwords do not match.");
      return false;
    }
    alert("Signup successful (demo)!");
    return true;
  }
</script>

</body>
</html>
