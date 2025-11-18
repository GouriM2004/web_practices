<?php
/*
Online Complaint Management System
Single-file demo: index.php

Features:
- Submit complaint (name, email, subject, message)
- View complaints list (admin)
- Update status (Open, In Progress, Resolved)
- Search & filter
- Simple admin login (demo, change in production)
- Uses MySQL via PDO

Setup:
1. Create a MySQL database (e.g., `complaints_db`).
2. Create table using the SQL below (or let this script create it on first run if it doesn't exist).

SQL:

CREATE TABLE `complaints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Open','In Progress','Resolved') NOT NULL DEFAULT 'Open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

3. Put this file in your web server (e.g., XAMPP htdocs) and edit DB credentials below.
4. Open in browser: http://localhost/index.php

Important: This is a demo single-file app. For production, split logic, add CSRF protection, stronger auth, HTTPS, and input sanitization rules.
*/

// ---------- CONFIG ----------
$db_host = '127.0.0.1';
$db_name = 'complaints_db';
$db_user = 'root';
$db_pass = '';
$admin_user = 'admin';
$admin_pass = 'password123'; // change for production

// ---------- END CONFIG ----------

session_start();

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Try to connect without db and create DB if possible
    try {
        $pdoTemp = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e2) {
        die("Database connection failed: " . htmlspecialchars($e2->getMessage()));
    }
}

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Open','In Progress','Resolved') NOT NULL DEFAULT 'Open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Simple router
$action = $_REQUEST['action'] ?? '';

// AJAX endpoints
if ($action === 'submit_complaint' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];
    if ($name === '') $errors[] = 'Name is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if ($subject === '') $errors[] = 'Subject is required';
    if ($message === '') $errors[] = 'Message is required';

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO complaints (name,email,subject,message) VALUES (:name,:email,:subject,:message)');
    $stmt->execute([':name'=>$name,':email'=>$email,':subject'=>$subject,':message'=>$message]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Complaint submitted successfully']);
    exit;
}

if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // admin check
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Unauthorized']);
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['Open','In Progress','Resolved'];
    if ($id <= 0 || !in_array($status, $allowed)) {
        echo json_encode(['success'=>false,'error'=>'Invalid data']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE complaints SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute([':status'=>$status,':id'=>$id]);
    echo json_encode(['success'=>true]);
    exit;
}

// Admin login
if ($action === 'admin_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === $admin_user && $pass === $admin_pass) {
        $_SESSION['is_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $login_error = 'Invalid credentials';
    }
}

if ($action === 'admin_logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Fetch complaints for admin listing with basic search/filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page -1) * $perPage;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE :q OR email LIKE :q OR subject LIKE :q OR message LIKE :q)';
    $params[':q'] = "%{$search}%";
}
if ($status_filter !== '' && in_array($status_filter, ['Open','In Progress','Resolved'])) {
    $where[] = 'status = :status';
    $params[':status'] = $status_filter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints {$whereSql}");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$stmt = $pdo->prepare("SELECT * FROM complaints {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$complaints = $stmt->fetchAll();

$totalPages = (int)ceil($total / $perPage);

// ---------- HTML OUTPUT ----------
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Online Complaint Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{--brand:#0d6efd}
    body{background:#f5f7fb}
    .hero{background:linear-gradient(135deg, rgba(13,110,253,0.08), rgba(13,110,253,0.02));border-radius:12px;padding:28px}
    .card {border-radius:12px}
    .status-badge.Open{background:#e7f1ff;color:#0d6efd}
    .status-badge.\"In Progress\"{background:#fff4e5;color:#ff8c00}
    .status-badge.Resolved{background:#e9f7ef;color:#108043}
  </style>
</head>
<body>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Online Complaint Management System</h1>
    <div>
      <?php if (!empty($_SESSION['is_admin'])): ?>
        <a href="?action=admin_logout" class="btn btn-outline-secondary">Logout Admin</a>
      <?php else: ?>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#adminModal">Admin Login</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="hero card p-3">
        <h4 class="mb-3">Lodge a Complaint</h4>
        <form id="complaintForm">
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Subject</label>
            <input name="subject" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required></textarea>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary">Submit Complaint</button>
          </div>
          <div id="formMsg" class="mt-3"></div>
        </form>
      </div>

      <div class="card mt-4 p-3">
        <h5>How it works</h5>
        <ul>
          <li>Users submit complaints from the form.</li>
          <li>Admins can view, search and update status.</li>
          <li>Demo single-file app — secure and modularize for production.</li>
        </ul>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Complaints</h5>
          <form class="d-flex" method="get">
            <input type="hidden" name="page" value="1">
            <input name="search" value="<?=htmlspecialchars($search)?>" class="form-control form-control-sm me-2" placeholder="Search...">
            <select name="status_filter" class="form-select form-select-sm me-2">
              <option value="">All</option>
              <option <?= $status_filter==='Open' ? 'selected' : '' ?>>Open</option>
              <option <?= $status_filter==='In Progress' ? 'selected' : '' ?>>In Progress</option>
              <option <?= $status_filter==='Resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Subject</th>
                <th>Submitted</th>
                <th>Status</th>
                <?php if (!empty($_SESSION['is_admin'])): ?><th>Actions</th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (!$complaints): ?>
                <tr><td colspan="6" class="text-center">No complaints found.</td></tr>
              <?php endif; ?>
              <?php foreach ($complaints as $c): ?>
                <tr>
                  <td><?=htmlspecialchars($c['id'])?></td>
                  <td>
                    <strong><?=htmlspecialchars($c['name'])?></strong><br>
                    <small><?=htmlspecialchars($c['email'])?></small>
                  </td>
                  <td><?=htmlspecialchars($c['subject'])?><br><small><?=nl2br(htmlspecialchars(substr($c['message'],0,120)))?><?php if (strlen($c['message'])>120) echo '...'; ?></small></td>
                  <td><?=htmlspecialchars($c['created_at'])?></td>
                  <td><span class="badge status-badge <?=htmlspecialchars($c['status'])?>"><?=htmlspecialchars($c['status'])?></span></td>
                  <?php if (!empty($_SESSION['is_admin'])): ?>
                    <td>
                      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal" data-id="<?= $c['id'] ?>">View</button>
                      <div class="btn-group ms-2">
                        <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">Update Status</button>
                        <ul class="dropdown-menu">
                          <li><a class="dropdown-item update-status" href="#" data-id="<?= $c['id'] ?>" data-status="Open">Open</a></li>
                          <li><a class="dropdown-item update-status" href="#" data-id="<?= $c['id'] ?>" data-status="In Progress">In Progress</a></li>
                          <li><a class="dropdown-item update-status" href="#" data-id="<?= $c['id'] ?>" data-status="Resolved">Resolved</a></li>
                        </ul>
                      </div>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="pagination">
          <ul class="pagination">
            <?php for ($p=1;$p<=$totalPages;$p++): ?>
              <li class="page-item <?= $p===$page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status_filter=<?= urlencode($status_filter) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
          </ul>
        </nav>

      </div>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Complaint Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modalBodyContent">Loading...</div>
      </div>
    </div>
  </div>
</div>

<!-- Admin Login Modal -->
<div class="modal fade" id="adminModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="?action=admin_login">
        <div class="modal-header"><h5 class="modal-title">Admin Login</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <?php if (!empty($login_error)): ?><div class="alert alert-danger"><?=htmlspecialchars($login_error)?></div><?php endif; ?>
          <div class="mb-2"><label class="form-label">Username</label><input name="username" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Password</label><input name="password" type="password" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Login</button></div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('complaintForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);
  data.append('action','submit_complaint');
  const res = await fetch('index.php?action=submit_complaint',{method:'POST',body:data});
  const json = await res.json();
  const msg = document.getElementById('formMsg');
  if (json.success) {
    msg.innerHTML = '<div class="alert alert-success">'+json.message+'</div>';
    form.reset();
  } else {
    msg.innerHTML = '<div class="alert alert-danger">'+ (json.errors ? json.errors.join('<br>') : 'Error') +'</div>';
  }
});

// View modal load details
var viewModal = document.getElementById('viewModal');
viewModal.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var id = button.getAttribute('data-id');
  var content = document.getElementById('modalBodyContent');
  content.innerHTML = 'Loading...';
  fetch('index.php?action=get_complaint&id='+encodeURIComponent(id)).then(r=>r.text()).then(html=>{ content.innerHTML = html; });
});

// update status
document.querySelectorAll('.update-status').forEach(function(el){
  el.addEventListener('click', async function(e){
    e.preventDefault();
    const id = this.getAttribute('data-id');
    const status = this.getAttribute('data-status');
    if (!confirm('Change status to "'+status+'"?')) return;
    const data = new FormData();
    data.append('id', id);
    data.append('status', status);
    const resp = await fetch('index.php?action=update_status',{method:'POST',body:data});
    const res = await resp.json();
    if (res.success) location.reload(); else alert('Failed');
  });
});
</script>

</body>
</html>

<?php
// Serve complaint details for modal (simple, not ajax-protected)
if ($action === 'get_complaint' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM complaints WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    $c = $stmt->fetch();
    if (!$c) {
        echo '<div class="alert alert-warning">Complaint not found.</div>';
        exit;
    }
    echo '<h5>' . htmlspecialchars($c['subject']) . '</h5>';
    echo '<p><strong>From:</strong> ' . htmlspecialchars($c['name']) . ' &lt;' . htmlspecialchars($c['email']) . '&gt;</p>';
    echo '<p><strong>Submitted:</strong> ' . htmlspecialchars($c['created_at']) . '</p>';
    echo '<p><strong>Status:</strong> ' . htmlspecialchars($c['status']) . '</p>';
    echo '<hr>';
    echo '<p>' . nl2br(htmlspecialchars($c['message'])) . '</p>';
    exit;
}

?>
