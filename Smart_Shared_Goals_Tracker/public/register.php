<?php
require_once __DIR__ . '/../src/bootstrap.php';
$page_title = 'Register';
$page_scripts = ['assets/js/register.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <h2 class="card-title">Create your account</h2>
        <form id="registerForm">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" type="text" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input name="email" class="form-control" type="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" class="form-control" type="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Register</button>
        </form>

        <div id="msg" role="status" class="mt-3 text-danger"></div>
        <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>