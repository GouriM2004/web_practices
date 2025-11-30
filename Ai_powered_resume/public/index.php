<?php
require_once __DIR__ . '/../src/bootstrap.php';
include __DIR__ . '/includes/header.php';
?>

<div class="text-center py-5">
    <h1 class="display-5">Resume Tailor</h1>
    <p class="lead text-muted">Upload a resume, analyze it against a job description, and get tailored improvement tips.</p>
    <div class="mt-4">
        <a href="upload_resume.php" class="btn btn-primary btn-lg me-2">Upload Resume</a>
        <a href="dashboard.php" class="btn btn-outline-primary btn-lg">Go to Dashboard</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>