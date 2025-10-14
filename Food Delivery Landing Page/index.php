<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🍕 Food Delivery Landing Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
    }
    .navbar {
      background: linear-gradient(45deg, #ff6f00, #ff8f00);
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: 700;
    }
    .hero {
      background: url('https://source.unsplash.com/1600x600/?food,restaurant') center/cover no-repeat;
      color: white;
      text-shadow: 2px 2px 10px rgba(0,0,0,0.4);
      text-align: center;
      padding: 120px 0;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: 700;
    }
    .hero p {
      font-size: 1.2rem;
    }
    .menu-section .card {
      transition: transform 0.3s, box-shadow 0.3s;
      border: none;
      border-radius: 15px;
    }
    .menu-section .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .order-form {
      background-color: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    footer {
      background: #212529;
      color: #ddd;
      text-align: center;
      padding: 20px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#">🍴 FoodieExpress</a>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <h1>Delicious Meals Delivered to Your Doorstep</h1>
    <p>Fresh • Fast • Flavorful</p>
    <a href="#menu" class="btn btn-warning mt-3 fw-bold">Explore Menu</a>
  </div>
</section>

<!-- Menu Section -->
<section id="menu" class="menu-section py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Our Popular Dishes 🍝</h2>
      <p class="text-muted">Handpicked dishes our customers love most</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card">
          <img src="https://source.unsplash.com/400x300/?pizza" class="card-img-top" alt="Pizza">
          <div class="card-body text-center">
            <h5 class="card-title">Cheesy Pizza</h5>
            <p class="card-text text-muted">Loaded with mozzarella, tomatoes, and herbs.</p>
            <p class="fw-bold">₹299</p>
            <button class="btn btn-outline-warning">Order Now</button>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <img src="https://source.unsplash.com/400x300/?burger" class="card-img-top" alt="Burger">
          <div class="card-body text-center">
            <h5 class="card-title">Crispy Burger</h5>
            <p class="card-text text-muted">Juicy patty, soft bun, and fresh veggies.</p>
            <p class="fw-bold">₹199</p>
            <button class="btn btn-outline-warning">Order Now</button>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <img src="https://source.unsplash.com/400x300/?pasta" class="card-img-top" alt="Pasta">
          <div class="card-body text-center">
            <h5 class="card-title">Creamy Pasta</h5>
            <p class="card-text text-muted">Rich Alfredo sauce with Italian seasoning.</p>
            <p class="fw-bold">₹249</p>
            <button class="btn btn-outline-warning">Order Now</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">What Our Customers Say ❤️</h2>
    </div>
    <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active text-center">
          <blockquote class="blockquote">
            <p class="mb-4 fs-5">"The pizza was absolutely delicious! Super fast delivery too."</p>
          </blockquote>
          <footer class="blockquote-footer">Sneha Patil</footer>
        </div>
        <div class="carousel-item text-center">
          <blockquote class="blockquote">
            <p class="mb-4 fs-5">"Loved the burgers! Soft, juicy, and perfectly packed."</p>
          </blockquote>
          <footer class="blockquote-footer">Rohit Sharma</footer>
        </div>
        <div class="carousel-item text-center">
          <blockquote class="blockquote">
            <p class="mb-4 fs-5">"Best food delivery service I’ve used. The pasta is a must-try!"</p>
          </blockquote>
          <footer class="blockquote-footer">Aarti More</footer>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </div>
</section>

<!-- Order Form -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Place Your Order 🍲</h2>
      <p class="text-muted">Fill in your details and we’ll deliver happiness to your doorstep!</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-md-6">
        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
          $name = htmlspecialchars($_POST["name"]);
          $food = htmlspecialchars($_POST["food"]);
          $address = htmlspecialchars($_POST["address"]);
          echo "<div class='alert alert-success text-center'>Thank you, <strong>$name</strong>! Your order for <strong>$food</strong> will be delivered to <strong>$address</strong> soon. 🍴</div>";
        }
        ?>
        <form method="POST" action="" class="order-form">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required placeholder="Enter your name">
          </div>
          <div class="mb-3">
            <label class="form-label">Food Item</label>
            <input type="text" name="food" class="form-control" required placeholder="e.g. Pizza, Burger, Pasta">
          </div>
          <div class="mb-3">
            <label class="form-label">Delivery Address</label>
            <textarea name="address" class="form-control" rows="3" required placeholder="Enter your address"></textarea>
          </div>
          <div class="text-center">
            <button type="submit" class="btn btn-warning fw-bold">Submit Order</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <p>© 2025 FoodieExpress | Made with ❤️ using PHP, JS & Bootstrap</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
