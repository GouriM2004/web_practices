<?php
$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-body">
        <h2>Login</h2>
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input name="email" class="form-control" type="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" class="form-control" type="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Login</button>
        </form>
        <div id="msg" class="mt-3 text-danger"></div>
        <p class="mt-3">No account yet? <a href="register.php">Register</a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
    const form = document.getElementById('loginForm');
    const msg = document.getElementById('msg');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        msg.textContent = '';
        try {
            const json = await Api.post('api.php/login', {
                email: form.email.value.trim(),
                password: form.password.value
            });
            UI.toast('Login successful', 'success');
            setTimeout(() => location.href = 'dashboard.php', 400);
        } catch (err) {
            msg.textContent = (err.body && err.body.error) ? err.body.error : 'Login failed';
        }
    });
</script>