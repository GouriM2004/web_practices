<?php
$page_title = 'Profile';
$page_scripts = ['assets/js/profile.js'];
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid = (int)$_SESSION['user_id'];
// fetch user profile
$stmt = $pdo->prepare('SELECT id, name, email, avatar, bio, cover_photo, motivational_quote, show_streaks_public, created_at FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <?php if (!empty($user['cover_photo'])): ?>
                <img src="<?= htmlspecialchars($user['cover_photo']) ?>" class="card-img-top" alt="Cover photo" style="max-height:240px; object-fit:cover;">
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <img src="<?= htmlspecialchars($user['avatar'] ?: 'assets/img/default-avatar.png') ?>" class="rounded-circle me-3" width="64" height="64" alt="avatar">
                    <div>
                        <h4 class="mb-0"><?= htmlspecialchars($user['name']) ?></h4>
                        <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
                <?php if (!empty($user['motivational_quote'])): ?>
                    <blockquote class="blockquote">
                        <p class="mb-0"><?= htmlspecialchars($user['motivational_quote']) ?></p>
                    </blockquote>
                <?php endif; ?>
                <p class="mt-3"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                <a href="#editProfile" class="btn btn-outline-primary" id="editProfileBtn">Edit profile</a>
            </div>
        </div>
    </div>
</div>

<!-- Edit modal / form -->
<div id="editProfile" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="mb-3">
                        <label class="form-label">Display name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($user['bio']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover photo URL</label>
                        <input type="text" name="cover_photo" class="form-control" value="<?= htmlspecialchars($user['cover_photo']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivational quote</label>
                        <input type="text" name="motivational_quote" class="form-control" value="<?= htmlspecialchars($user['motivational_quote']) ?>">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="showStreaks" name="show_streaks_public" <?= (!empty($user['show_streaks_public']) ? 'checked' : '') ?>>
                        <label class="form-check-label" for="showStreaks">Show streak stats publicly</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProfileBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// Minimal inline script to initialize modal (profile.js will attach handlers)
?>