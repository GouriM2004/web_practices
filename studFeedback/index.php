<?php
session_start();

/*
  student_feedback.php
  Single-file Student Feedback Management System (Tabs layout, no database)
  - Frontend: HTML, Bootstrap, CSS, JS
  - PHP: session-based storage, edit/delete, export CSV
*/

// Initialize storage
if (!isset($_SESSION['feedbacks'])) $_SESSION['feedbacks'] = [];

/* Helper to find index by id */
function find_index($id) {
    foreach ($_SESSION['feedbacks'] as $i => $f) {
        if ($f['id'] === $id) return $i;
    }
    return null;
}

/* Handle create/update/delete/export actions */
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new feedback
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $comments = trim($_POST['comments'] ?? '');

        // Server-side validation
        if ($name === '') $errors[] = "Name is required.";
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
        if ($course === '') $errors[] = "Please select a course.";
        if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1 and 5.";

        if (empty($errors)) {
            $entry = [
                'id' => uniqid('fb_', true),
                'name' => htmlspecialchars($name),
                'email' => htmlspecialchars($email),
                'course' => htmlspecialchars($course),
                'rating' => $rating,
                'comments' => htmlspecialchars($comments),
                'time' => date('Y-m-d H:i:s')
            ];
            array_unshift($_SESSION['feedbacks'], $entry); // newest first
            $messages[] = "Feedback submitted — thank you, {$entry['name']}!";
            // After successful post, redirect to avoid resubmission
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?tab=view&msg=1");
            exit;
        }
    }

    // Update existing feedback
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = $_POST['id'] ?? '';
        $idx = find_index($id);
        if ($idx === null) $errors[] = "Feedback entry not found.";
        else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $course = trim($_POST['course'] ?? '');
            $rating = intval($_POST['rating'] ?? 0);
            $comments = trim($_POST['comments'] ?? '');

            if ($name === '') $errors[] = "Name is required.";
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
            if ($course === '') $errors[] = "Please select a course.";
            if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1 and 5.";

            if (empty($errors)) {
                $_SESSION['feedbacks'][$idx]['name'] = htmlspecialchars($name);
                $_SESSION['feedbacks'][$idx]['email'] = htmlspecialchars($email);
                $_SESSION['feedbacks'][$idx]['course'] = htmlspecialchars($course);
                $_SESSION['feedbacks'][$idx]['rating'] = $rating;
                $_SESSION['feedbacks'][$idx]['comments'] = htmlspecialchars($comments);
                $_SESSION['feedbacks'][$idx]['time'] = date('Y-m-d H:i:s');
                $messages[] = "Feedback updated successfully.";
                header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?tab=view&msg=2");
                exit;
            }
        }
    }

    // Delete feedback
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        $idx = find_index($id);
        if ($idx === null) $errors[] = "Entry not found or already deleted.";
        else {
            array_splice($_SESSION['feedbacks'], $idx, 1);
            $messages[] = "Feedback deleted.";
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?tab=view&msg=3");
            exit;
        }
    }
}

// Export to CSV (GET)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "feedbacks_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Email','Course','Rating','Comments','Submitted At']);
    foreach ($_SESSION['feedbacks'] as $f) {
        fputcsv($out, [$f['id'],$f['name'],$f['email'],$f['course'],$f['rating'],$f['comments'],$f['time']]);
    }
    fclose($out);
    exit;
}

/* For pre-filling edit form, if requested via GET */
$edit_entry = null;
if (isset($_GET['edit'])) {
    $eid = $_GET['edit'];
    $idx = find_index($eid);
    if ($idx !== null) $edit_entry = $_SESSION['feedbacks'][$idx];
}

// small helper to display message based on query param
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === '1') $messages[] = "Feedback submitted successfully.";
    if ($_GET['msg'] === '2') $messages[] = "Feedback updated successfully.";
    if ($_GET['msg'] === '3') $messages[] = "Feedback deleted.";
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Student Feedback Management — Professional</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary: #0d6efd;
      --bg:#f6f9ff;
      --muted:#6c757d;
    }
    body { font-family: 'Poppins', sans-serif; background: var(--bg); color:#222; }
    .nav-tabs .nav-link.active { background: linear-gradient(90deg,var(--primary), #0b5ed7); color: #fff; border: none; border-radius: 8px; }
    .card { border-radius: 12px; }
    .hero { padding: 3.5rem 0; }
    .form-card { background: linear-gradient(180deg,#fff,#fbfdff); padding:1.4rem; border-radius:12px; box-shadow:0 12px 30px rgba(13,110,253,0.06); }
    .small-muted { color: var(--muted); }
    .rating-badge { font-weight:700; padding:6px 10px; border-radius:8px; background: rgba(13,110,253,0.08); color:var(--primary); }
    footer { margin-top:2.5rem; padding:18px 0; background:var(--primary); color:#fff; border-radius:8px; }
    .fade-up { transform: translateY(8px); opacity:0; transition: all 420ms ease; }
    .in-view { transform: translateY(0); opacity:1; }
    @media (min-width:992px) { .hero { padding:4.5rem 0; } }
  </style>
</head>
<body>

<!-- HEADER / NAV -->
<nav class="navbar navbar-expand-lg" style="background:linear-gradient(90deg,#0d6efd,#0b5ed7);">
  <div class="container">
    <a class="navbar-brand text-white fw-bold" href="#">Feedback<span class="text-white-50">Hub</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link text-white" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#submit">Submit Feedback</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#view">View Feedback</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="card p-4 mb-4">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <h2 class="fw-bold">Student Feedback Management</h2>
          <p class="small-muted mb-0">Collect and review student feedback easily — demo mode (no database). Use tabs to navigate.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="?export=csv" class="btn btn-outline-light">Export CSV</a>
        </div>
      </div>
    </div>

    <!-- Messages / Errors -->
    <div class="mb-3">
      <?php foreach ($messages as $m): ?>
        <div class="alert alert-success"><?php echo $m; ?></div>
      <?php endforeach; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- TABS -->
<div class="container mb-5">
  <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-home" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">Home</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-submit" data-bs-toggle="tab" data-bs-target="#submit" type="button" role="tab">Submit Feedback</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-view" data-bs-toggle="tab" data-bs-target="#view" type="button" role="tab">View Feedback <span class="badge bg-white text-primary ms-2"><?php echo count($_SESSION['feedbacks']); ?></span></button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- HOME TAB -->
    <div class="tab-pane fade show active" id="home" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="card p-4 form-card fade-up">
            <h4>Welcome</h4>
            <p class="small-muted">This is a lightweight Student Feedback Management demo. Use the "Submit Feedback" tab to add entries. Feedbacks are stored in PHP session (temporary).</p>
            <ul class="mb-0">
              <li>✔ Modern tabs layout</li>
              <li>✔ Form validation (client + server)</li>
              <li>✔ Edit / Delete / Export</li>
            </ul>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="card p-4 form-card fade-up">
            <h5 class="mb-2">Quick Stats</h5>
            <p class="small-muted mb-2">Total feedbacks collected</p>
            <div class="display-6 fw-bold"><?php echo count($_SESSION['feedbacks']); ?></div>
            <hr>
            <p class="small-muted mb-0">Tip: Use Export CSV to download entries for offline review.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- SUBMIT TAB -->
    <div class="tab-pane fade" id="submit" role="tabpanel">
      <div class="row">
        <div class="col-lg-8">
          <div class="card p-4 form-card">
            <h4 class="mb-3"><?php echo $edit_entry ? "Edit Feedback" : "Submit Feedback"; ?></h4>

            <form id="feedbackForm" method="POST" novalidate>
              <!-- include hidden action -->
              <input type="hidden" name="action" value="<?php echo $edit_entry ? 'update' : 'create'; ?>">
              <?php if ($edit_entry): ?>
                <input type="hidden" name="id" value="<?php echo $edit_entry['id']; ?>">
              <?php endif; ?>

              <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo $edit_entry['name'] ?? ''; ?>">
                <div class="invalid-feedback">Please enter a name.</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?php echo $edit_entry['email'] ?? ''; ?>">
                <div class="invalid-feedback">Please enter a valid email.</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Course / Subject</label>
                <select name="course" id="course" class="form-select" required>
                  <option value="">-- select course --</option>
                  <option <?php echo (isset($edit_entry) && $edit_entry['course']=='Web Dev') ? 'selected':''; ?>>Web Dev</option>
                  <option <?php echo (isset($edit_entry) && $edit_entry['course']=='Data Science') ? 'selected':''; ?>>Data Science</option>
                  <option <?php echo (isset($edit_entry) && $edit_entry['course']=='Design') ? 'selected':''; ?>>Design</option>
                  <option <?php echo (isset($edit_entry) && $edit_entry['course']=='Mathematics') ? 'selected':''; ?>>Mathematics</option>
                </select>
                <div class="invalid-feedback">Choose a course.</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Rating</label>
                <div>
                  <select name="rating" id="rating" class="form-select w-auto" required>
                    <option value="">-- rating --</option>
                    <option value="5" <?php echo (isset($edit_entry) && $edit_entry['rating']==5)?'selected':''; ?>>5 - Excellent</option>
                    <option value="4" <?php echo (isset($edit_entry) && $edit_entry['rating']==4)?'selected':''; ?>>4 - Very Good</option>
                    <option value="3" <?php echo (isset($edit_entry) && $edit_entry['rating']==3)?'selected':''; ?>>3 - Good</option>
                    <option value="2" <?php echo (isset($edit_entry) && $edit_entry['rating']==2)?'selected':''; ?>>2 - Fair</option>
                    <option value="1" <?php echo (isset($edit_entry) && $edit_entry['rating']==1)?'selected':''; ?>>1 - Poor</option>
                  </select>
                </div>
                <div class="invalid-feedback">Please provide a rating.</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Comments</label>
                <textarea name="comments" class="form-control" rows="4" placeholder="Your feedback here..."><?php echo $edit_entry['comments'] ?? ''; ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?php echo $edit_entry ? 'Update Feedback' : 'Submit Feedback'; ?></button>
                <?php if ($edit_entry): ?>
                  <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-outline-secondary">Cancel Edit</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card p-4 form-card">
            <h6>Feedback Tips</h6>
            <p class="small-muted mb-0">Ask for specific examples, be constructive, and highlight what worked well. Short & focused feedback helps instructors improve faster.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- VIEW TAB -->
    <div class="tab-pane fade" id="view" role="tabpanel">
      <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Submitted Feedback</h5>
          <div>
            <a href="?export=csv" class="btn btn-outline-primary btn-sm">Export CSV</a>
            <button id="clearAllBtn" class="btn btn-outline-danger btn-sm ms-2">Clear All</button>
          </div>
        </div>

        <?php if (empty($_SESSION['feedbacks'])): ?>
          <div class="small-muted">No feedback submitted yet. Use the "Submit Feedback" tab to add one.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Course</th>
                  <th>Rating</th>
                  <th>Comments</th>
                  <th>Time</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($_SESSION['feedbacks'] as $i => $f): ?>
                  <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo $f['name']; ?></td>
                    <td><?php echo $f['email']; ?></td>
                    <td><?php echo $f['course']; ?></td>
                    <td><span class="rating-badge"><?php echo $f['rating']; ?></span></td>
                    <td style="max-width:260px;"><?php echo nl2br($f['comments']); ?></td>
                    <td><?php echo $f['time']; ?></td>
                    <td>
                      <a class="btn btn-sm btn-outline-secondary" href="?edit=<?php echo $f['id']; ?>&tab=submit">Edit</a>
                      <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this feedback?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer class="container">
  <div class="d-flex justify-content-between align-items-center">
    <div>© <?php echo date('Y'); ?> FeedbackHub — Demo (Session storage)</div>
    <div class="small-muted">Built with PHP, Bootstrap & Vanilla JS</div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Activate tab from query param if present
  (function(){
    const params = new URLSearchParams(location.search);
    const tab = params.get('tab');
    if (tab) {
      const trigger = document.querySelector(`#tab-${tab}`);
      if (trigger) {
        const bsTab = new bootstrap.Tab(trigger);
        bsTab.show();
      }
    }
  })();

  // Client-side validation (Bootstrap)
  (function(){
    'use strict';
    const form = document.getElementById('feedbackForm');
    if (!form) return;
    form.addEventListener('submit', function(e){
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  })();

  // Simple fade-in animation
  window.addEventListener('load', function(){
    document.querySelectorAll('.fade-up').forEach(el => el.classList.add('in-view'));
  });

  // Clear all feedbacks (client triggers a POST via fetch)
  document.getElementById('clearAllBtn')?.addEventListener('click', function(){
    if (!confirm('This will permanently delete ALL feedbacks in this demo (session). Continue?')) return;
    // Send POST with delete action for each entry (session-only)
    fetch(location.href, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ action: 'delete_all', token: '1' })
    }).then(()=> location.href = location.pathname + '?tab=view');
  });

  // Handle delete_all on server via simple endpoint (not present by default)
  // We'll add client behavior only; server-side handling below:
</script>

<?php
// Extra: handle clear-all via simple check for POST 'delete_all'
// (placed here so fetch above triggers deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all') {
    $_SESSION['feedbacks'] = [];
    // respond then exit (fetch will reload)
    header('HTTP/1.1 204 No Content');
    exit;
}
?>

</body>
</html>
