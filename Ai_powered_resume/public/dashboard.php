<?php
require_once __DIR__ . '/../src/bootstrap.php';
session_start();
include __DIR__ . '/includes/header.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $db = get_db();
    $row = $db->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetch();
    $user_id = $row['id'] ?? null;
}

$db = get_db();
$res = [];
if ($user_id) {
    $stmt = $db->prepare('SELECT id, filename, created_at FROM resumes WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    $res = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Dashboard</h1>
    <div>
        <a href="upload_resume.php" class="btn btn-primary">Upload Resume</a>
        <a href="analyze.php" class="btn btn-secondary">Analyze / Parse</a>
    </div>
</div>

<h4>Your Resumes</h4>
<?php if (empty($res)): ?>
    <div class="alert alert-info">No resumes found. Upload one to get started.</div>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Filename</th>
                <th>Uploaded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($res as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['filename']); ?></td>
                    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    <td>
                        <a href="view_resume.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="tailor.php?resume_id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-success">Tailor</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>