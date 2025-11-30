<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Controllers/ResumeController.php';
include __DIR__ . '/includes/header.php';

// handle upload
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    // ensure a user id exists (scaffold behavior)
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $db = get_db();
        $row = $db->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetch();
        if ($row && isset($row['id'])) {
            $user_id = $row['id'];
            $_SESSION['user_id'] = $user_id;
        } else {
            $stmt = $db->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
            $stmt->execute(['Default User', 'default@example.com', '']);
            $user_id = $db->lastInsertId();
            $_SESSION['user_id'] = $user_id;
        }
    }

    try {
        $id = ResumeController::upload($user_id, $_FILES['resume']);
        $message = ['type' => 'success', 'text' => "Uploaded. Resume ID: " . $id];
    } catch (Exception $e) {
        $message = ['type' => 'danger', 'text' => 'Error: ' . $e->getMessage()];
    }
}
?>

<div class="card">
    <div class="card-body">
        <h3 class="card-title">Upload Resume</h3>
        <p class="text-muted">Accepts PDF, DOCX, or plain text files.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>"><?php echo htmlspecialchars($message['text']); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <input class="form-control" type="file" name="resume" required />
            </div>
            <button class="btn btn-primary">Upload</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>