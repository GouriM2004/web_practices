<?php
$page_title = 'Groups';
$page_scripts = ['assets/js/groups.js'];
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="card">
    <div class="card-body">
        <h2 class="card-title">Groups</h2>
        <div class="row">
            <div class="col-md-6">
                <h4>Your groups</h4>
                <ul id="memberGroups" class="list-group"></ul>
            </div>
            <div class="col-md-6">
                <h4>Public groups</h4>
                <ul id="publicGroups" class="list-group"></ul>
            </div>
        </div>

        <hr>
        <h4>Create a group</h4>
        <form id="createGroupForm">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Privacy</label>
                <select name="privacy" class="form-select">
                    <option value="private">Private</option>
                    <option value="public">Public</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Create Group</button>
        </form>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>