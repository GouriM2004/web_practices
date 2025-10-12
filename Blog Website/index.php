<?php
// Sample comments array (You can later connect to a database)
$comments = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars($_POST["name"]);
    $comment = htmlspecialchars($_POST["comment"]);
    $comments[] = ["name" => $name, "comment" => $comment];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Blog Website</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .navbar {
      background: linear-gradient(45deg, #007bff, #6610f2);
    }
    .navbar-brand {
      font-weight: 600;
      color: white !important;
    }
    .blog-card {
      transition: 0.3s;
      border: none;
    }
    .blog-card:hover {
      transform: translateY(-5px);
      box-shadow: 0px 6px 15px rgba(0,0,0,0.1);
    }
    .comment-box {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0px 3px 10px rgba(0,0,0,0.1);
      padding: 20px;
      margin-top: 30px;
    }
    footer {
      background: #343a40;
      color: #ddd;
      text-align: center;
      padding: 20px 0;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#">My Blog</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="text-center py-5 bg-light border-bottom">
  <div class="container">
    <h1 class="fw-bold">Welcome to My Blog ✍️</h1>
    <p class="text-muted">Explore articles, stories, and ideas that inspire growth and creativity.</p>
  </div>
</section>

<!-- Blog Posts -->
<div class="container my-5">
  <div class="row g-4">
    <!-- Blog 1 -->
    <div class="col-md-4">
      <div class="card blog-card">
        <img src="https://source.unsplash.com/400x250/?technology" class="card-img-top" alt="Post 1">
        <div class="card-body">
          <h5 class="card-title">The Future of Technology</h5>
          <p class="card-text text-muted">Discover how AI and innovation are reshaping the modern world.</p>
          <a href="#" class="btn btn-primary btn-sm">Read More</a>
        </div>
      </div>
    </div>

    <!-- Blog 2 -->
    <div class="col-md-4">
      <div class="card blog-card">
        <img src="https://source.unsplash.com/400x250/?travel" class="card-img-top" alt="Post 2">
        <div class="card-body">
          <h5 class="card-title">Journey to the Mountains</h5>
          <p class="card-text text-muted">A travel diary from the serene Himalayas filled with peace and adventure.</p>
          <a href="#" class="btn btn-primary btn-sm">Read More</a>
        </div>
      </div>
    </div>

    <!-- Blog 3 -->
    <div class="col-md-4">
      <div class="card blog-card">
        <img src="https://source.unsplash.com/400x250/?motivation" class="card-img-top" alt="Post 3">
        <div class="card-body">
          <h5 class="card-title">Stay Motivated Everyday</h5>
          <p class="card-text text-muted">Simple habits that can transform your mindset and boost productivity.</p>
          <a href="#" class="btn btn-primary btn-sm">Read More</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Comment Section -->
<div class="container">
  <div class="comment-box">
    <h4 class="fw-bold mb-3">💬 Leave a Comment</h4>
    <form method="POST" action="">
      <div class="mb-3">
        <label class="form-label">Your Name</label>
        <input type="text" class="form-control" name="name" placeholder="Enter your name" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Comment</label>
        <textarea class="form-control" name="comment" rows="3" placeholder="Write your comment..." required></textarea>
      </div>
      <button type="submit" class="btn btn-success">Submit</button>
    </form>

    <!-- Display Comments -->
    <div class="mt-4">
      <h5>Recent Comments:</h5>
      <div id="commentList">
        <?php if (!empty($comments)): ?>
          <?php foreach ($comments as $c): ?>
            <div class="border-bottom py-2">
              <strong><?= $c["name"] ?>:</strong> <?= $c["comment"] ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted">No comments yet. Be the first to share your thoughts!</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  <p>© 2025 My Blog | Designed with ❤️ using Bootstrap</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
