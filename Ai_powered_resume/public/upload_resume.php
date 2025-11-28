<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Controllers/ResumeController.php';

// very simple form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    // Ensure a valid user_id exists to satisfy the foreign key constraint.
    // If user is not logged in, try to reuse an existing user; otherwise create a default user.
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        $db = get_db();
        // try to find any existing user
        $stmt = $db->query('SELECT id FROM users ORDER BY id LIMIT 1');
        $row = $stmt->fetch();
        if ($row && isset($row['id'])) {
            $user_id = $row['id'];
            $_SESSION['user_id'] = $user_id;
        } else {
            // create a default placeholder user (password left empty for scaffold)
            $stmt = $db->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
            $stmt->execute(['Default User', 'default@example.com', '']);
            $user_id = $db->lastInsertId();
            $_SESSION['user_id'] = $user_id;
        }
    }

    try {
        $id = ResumeController::upload($user_id, $_FILES['resume']);
        echo "Uploaded. Resume ID: " . $id;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    exit;
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Upload Resume</title>
</head>

<body>
    <h2>Upload Resume (PDF/DOCX/TXT)</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="resume" required />
        <button type="submit">Upload</button>
    </form>
</body>

</html>