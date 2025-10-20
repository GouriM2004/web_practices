<?php
session_start();

/*
  online_course.php
  Single-file Online Course Registration System (Design D - Classic Blue)
  - Frontend: HTML, Bootstrap, CSS, JS
  - PHP: simple session-based demo storage (no database)
  Next step: I'll provide MySQL connection & insert logic when you say "give database code".
*/

// Handle form submission (store in session for demo)
$errors = [];
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');

    // Basic server-side validation
    if ($name === '') $errors[] = "Name is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if ($phone === '' || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) $errors[] = "Valid phone number is required.";
    if ($course === '') $errors[] = "Please choose a course.";

    if (empty($errors)) {
        $entry = [
            'name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'phone' => htmlspecialchars($phone),
            'course' => htmlspecialchars($course),
            'time' => date('Y-m-d H:i:s')
        ];
        if (!isset($_SESSION['registrations'])) $_SESSION['registrations'] = [];
        array_unshift($_SESSION['registrations'], $entry); // newest first
        $success_msg = "Registration successful for <strong>{$entry['course']}</strong>. Thank you, {$entry['name']}!";
        // Clear POST values to avoid resubmission
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?success=1");
        exit;
    }
}

// Pull registrations for display
$registrations = $_SESSION['registrations'] ?? [];
$success_flag = isset($_GET['success']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Online Course Registration — Professional</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary:#0d6efd; /* Classic Blue */
      --muted:#6c757d;
      --card-bg: #ffffff;
      --page-bg: #f6f9ff;
    }
    body{
      font-family: 'Poppins', sans-serif;
      background: var(--page-bg);
      color: #222;
    }
    .navbar {
      background: linear-gradient(90deg, rgba(13,110,253,1), rgba(12,88,209,1));
    }
    .navbar .nav-link { color: rgba(255,255,255,0.95) !important; }
    .hero{
      background: linear-gradient(180deg, rgba(13,110,253,0.08), transparent 60%);
      padding: 6rem 0 3rem;
    }
    .hero .lead { color: var(--muted); }
    .course-card { border: 0; border-radius: 12px; transition: transform .25s, box-shadow .25s; }
    .course-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(13,110,253,0.08); }
    .badge-category { background: rgba(13,110,253,0.12); color: var(--primary); border-radius: 8px; padding: 6px 10px; font-weight:600; }
    .section-title { font-weight:700; margin-bottom:1rem; }
    .form-card { background: linear-gradient(180deg,#fff, #fbfdff); border-radius:12px; padding:1.6rem; box-shadow: 0 8px 26px rgba(16,24,40,0.04); }
    footer { background: #0d6efd; color: #fff; padding: 20px 0; margin-top: 3rem; border-radius: 0; }
    .small-muted { color: var(--muted); font-size: .95rem; }
    @media (min-width:992px){
      .hero { padding: 7rem 0 4rem; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <div class="bg-white rounded-circle me-2" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20'%3E%3Ccircle cx='10' cy='10' r='9' fill='%230d6efd'/%3E%3C/svg%3E" alt=""></div>
      <span>Course<span class="fw-bold">Hub</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="#courses">Courses</a></li>
        <li class="nav-item"><a class="nav-link" href="#register">Register</a></li>
        <li class="nav-item"><a class="nav-link" href="#students">Students</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center gy-4">
      <div class="col-lg-7">
        <h1 class="display-5 fw-bold">Upskill with industry-ready courses</h1>
        <p class="lead small-muted">Browse curated courses and register in seconds. Professional curriculum, expert instructors, and flexible schedules.</p>
        <div class="mt-4">
          <a href="#courses" class="btn btn-primary btn-lg me-2">Browse Courses</a>
          <a href="#register" class="btn btn-outline-primary btn-lg">Register Now</a>
        </div>
        <div class="mt-4 small-muted">Trusted by learners worldwide • Certificates available</div>
      </div>
      <div class="col-lg-5 text-center">
        <!-- Simple illustrative card -->
        <div class="card p-3" style="border-radius:16px;">
          <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='420' height='280'%3E%3Crect width='420' height='280' fill='%23e9f0ff' rx='12'/%3E%3Ctext x='50%' y='50%' font-size='20' text-anchor='middle' fill='%230d6efd' dy='.3em'%3ELearn, Build, Grow%3C/text%3E%3C/svg%3E" class="img-fluid" alt="hero">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- COURSES -->
<section id="courses" class="py-5">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <div class="section-title h4">Featured Courses</div>
        <div class="small-muted">Select a course that matches your goals</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <select id="filterCategory" class="form-select form-select-sm" style="width:200px;">
          <option value="all">All categories</option>
          <option value="development">Development</option>
          <option value="design">Design</option>
          <option value="data">Data Science</option>
        </select>
      </div>
    </div>

    <div class="row g-4" id="courseList">
      <!-- Course Card 1 -->
      <div class="col-md-6 col-lg-4 course-item" data-category="development">
        <div class="card course-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <span class="badge-category">Development</span>
            <span class="small-muted">6 weeks</span>
          </div>
          <h5 class="mt-3">Frontend Development Bootcamp</h5>
          <p class="small-muted">HTML, CSS, JavaScript, Bootstrap — build responsive websites and UIs.</p>
          <div class="mt-auto d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold">₹6,999</div>
              <div class="small-muted">Starts: Nov 1, 2025</div>
            </div>
            <button class="btn btn-outline-primary" data-course="Frontend Development Bootcamp">Register</button>
          </div>
        </div>
      </div>

      <!-- Course Card 2 -->
      <div class="col-md-6 col-lg-4 course-item" data-category="data">
        <div class="card course-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <span class="badge-category">Data Science</span>
            <span class="small-muted">8 weeks</span>
          </div>
          <h5 class="mt-3">Data Analysis with Python</h5>
          <p class="small-muted">Pandas, NumPy, visualization, and real-world projects.</p>
          <div class="mt-auto d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold">₹8,499</div>
              <div class="small-muted">Starts: Dec 10, 2025</div>
            </div>
            <button class="btn btn-outline-primary" data-course="Data Analysis with Python">Register</button>
          </div>
        </div>
      </div>

      <!-- Course Card 3 -->
      <div class="col-md-6 col-lg-4 course-item" data-category="design">
        <div class="card course-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <span class="badge-category">Design</span>
            <span class="small-muted">4 weeks</span>
          </div>
          <h5 class="mt-3">UI / UX Design Fundamentals</h5>
          <p class="small-muted">Design thinking, wireframes, Figma basics, and prototyping.</p>
          <div class="mt-auto d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold">₹5,499</div>
              <div class="small-muted">Starts: Nov 20, 2025</div>
            </div>
            <button class="btn btn-outline-primary" data-course="UI / UX Design Fundamentals">Register</button>
          </div>
        </div>
      </div>

      <!-- Add more course cards similarly -->
    </div>
  </div>
</section>

<!-- REGISTER -->
<section id="register" class="py-5 bg-light">
  <div class="container">
    <div class="row g-4 align-items-start">
      <div class="col-lg-6">
        <div class="form-card">
          <h4 class="mb-3">Course Registration</h4>
          <p class="small-muted">Fill the form to reserve your seat. We will contact you with details.</p>

          <!-- show success or errors -->
          <?php if ($success_flag): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              Registration submitted successfully.
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e) echo "<li>{$e}</li>"; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form id="registerForm" method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label">Full name</label>
              <input type="text" name="name" id="name" class="form-control" placeholder="Your full name" required>
              <div class="invalid-feedback">Please enter your name.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" name="email" id="email" class="form-control" placeholder="you@example.com" required>
              <div class="invalid-feedback">Please enter a valid email.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" id="phone" class="form-control" placeholder="+919876543210" required>
              <div class="invalid-feedback">Please enter your phone number.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Select Course</label>
              <select name="course" id="course" class="form-select" required>
                <option value="">-- Choose a course --</option>
                <option>Frontend Development Bootcamp</option>
                <option>Data Analysis with Python</option>
                <option>UI / UX Design Fundamentals</option>
              </select>
              <div class="invalid-feedback">Please choose a course.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Message (optional)</label>
              <textarea name="message" class="form-control" rows="3" placeholder="Any prior experience or queries?"></textarea>
            </div>

            <div class="d-grid">
              <button type="submit" name="register_submit" class="btn btn-primary btn-lg">Submit Registration</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Right column: benefits + quick facts -->
      <div class="col-lg-6">
        <div class="card p-4 h-100" style="border-radius:12px;">
          <h5 class="mb-3">Why learn with CourseHub?</h5>
          <ul class="list-unstyled small-muted">
            <li class="mb-2">✔ Industry-aligned curriculum</li>
            <li class="mb-2">✔ Practical projects & portfolio pieces</li>
            <li class="mb-2">✔ Live sessions & recorded lectures</li>
            <li class="mb-2">✔ Certificate on completion</li>
          </ul>
          <hr>
          <div>
            <h6 class="mb-2">Upcoming batch</h6>
            <p class="small-muted mb-0">Frontend Bootcamp • Nov 1, 2025 • 6 weeks • ₹6,999</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- REGISTERED STUDENTS (demo using session) -->
<section id="students" class="py-5">
  <div class="container">
    <div class="section-title h4">Registered Students (Demo)</div>
    <?php if (empty($registrations)): ?>
      <div class="small-muted">No registrations yet. Submit the form to see demo entries here.</div>
    <?php else: ?>
      <div class="table-responsive mt-3">
        <table class="table table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Course</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($registrations as $i => $r): ?>
              <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo $r['name']; ?></td>
                <td><?php echo $r['email']; ?></td>
                <td><?php echo $r['phone']; ?></td>
                <td><?php echo $r['course']; ?></td>
                <td><?php echo $r['time']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- CONTACT -->
<section id="contact" class="py-5 bg-white">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h5>Contact Us</h5>
        <p class="small-muted">Have questions? Reach out and we'll get back to you within 2 business days.</p>
        <p class="mb-1"><strong>Email:</strong> support@coursehub.example</p>
        <p class="mb-1"><strong>Phone:</strong> +91 98765 43210</p>
      </div>
      <div class="col-md-6 text-md-end">
        <a class="btn btn-outline-primary" href="mailto:support@coursehub.example">Email Support</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="container text-center">
    <div class="row align-items-center">
      <div class="col-md-6 text-md-start mb-2 mb-md-0">© <?php echo date('Y'); ?> CourseHub — All rights reserved.</div>
      <div class="col-md-6 text-md-end small-muted">Built with ❤️ using PHP, Bootstrap & Vanilla JS</div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Client-side form validation (Bootstrap style)
  (function () {
    'use strict';
    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  })();

  // Quick course buttons: fill course on register click
  document.querySelectorAll('[data-course]').forEach(btn => {
    btn.addEventListener('click', function () {
      const course = this.getAttribute('data-course');
      // Scroll to register section and set select value
      document.querySelector('#course').value = course;
      document.querySelector('#course').focus();
      window.location.hash = '#register';
    });
  });

  // Filter courses by category
  document.getElementById('filterCategory').addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('.course-item').forEach(item => {
      if (val === 'all' || item.getAttribute('data-category') === val) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
  });

</script>
</body>
</html>
