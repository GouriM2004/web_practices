<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f5f7fa;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .main-content {
      flex: 1;
    }
    .navbar {
      background: #0d6efd;
    }
    .navbar-brand {
      font-weight: 600;
      color: #fff !important;
    }
    .job-card {
      transition: all 0.3s ease;
    }
    .job-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .footer {
      text-align: center;
      padding: 15px 0;
      background: #0d6efd;
      color: #fff;
      margin-top: auto;
    }
  </style>
</head>
<body>

<div class="main-content">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="#">Job Portal</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="text-center py-5 bg-light">
  <div class="container">
    <h1 class="fw-bold">Find Your Dream Job</h1>
    <p class="text-muted">Browse through the latest job openings and apply easily</p>
  </div>
</section>

<!-- Job Listings -->
<div class="container my-4">
  <div class="row g-4">

    <!-- Job 1 -->
    <div class="col-md-4">
      <div class="card job-card p-3">
        <h5 class="fw-bold">Frontend Developer</h5>
        <p class="text-muted">ABC Technologies</p>
        <p><i class="bi bi-geo-alt"></i> Pune, India</p>
        <p>Experience: 1–2 years</p>
        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#applyModal" data-job="Frontend Developer">Apply Now</button>
      </div>
    </div>

    <!-- Job 2 -->
    <div class="col-md-4">
      <div class="card job-card p-3">
        <h5 class="fw-bold">Backend Developer</h5>
        <p class="text-muted">XYZ Solutions</p>
        <p><i class="bi bi-geo-alt"></i> Bengaluru, India</p>
        <p>Experience: 2–3 years</p>
        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#applyModal" data-job="Backend Developer">Apply Now</button>
      </div>
    </div>

    <!-- Job 3 -->
    <div class="col-md-4">
      <div class="card job-card p-3">
        <h5 class="fw-bold">UI/UX Designer</h5>
        <p class="text-muted">Creative Studio</p>
        <p><i class="bi bi-geo-alt"></i> Mumbai, India</p>
        <p>Experience: 1–2 years</p>
        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#applyModal" data-job="UI/UX Designer">Apply Now</button>
      </div>
    </div>

  </div>
</div>

<!-- Apply Modal -->
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="applyForm" method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Apply for Job</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Job Title</label>
            <input type="text" class="form-control" id="jobTitle" name="jobTitle" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Resume (URL or brief intro)</label>
            <textarea class="form-control" name="resume" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="submit" class="btn btn-success w-100">Submit Application</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div>

<!-- Footer -->
<div class="footer">
  <p>© 2025 Job Portal. All rights reserved.</p>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
<script>
  // Set job title dynamically in modal
  const applyModal = document.getElementById('applyModal');
  applyModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const jobTitle = button.getAttribute('data-job');
    document.getElementById('jobTitle').value = jobTitle;
  });

  // Simple form validation
  document.getElementById('applyForm').addEventListener('submit', function(e) {
    alert('Your application has been submitted successfully!');
  });
</script>

<?php
// Handle form submission
if(isset($_POST['submit'])){
  $jobTitle = $_POST['jobTitle'];
  $name = $_POST['name'];
  $email = $_POST['email'];
  $resume = $_POST['resume'];

  // In real project: store in DB
  echo "<script>alert('Application for $jobTitle submitted by $name');</script>";
}
?>
</body>
</html>
