<?php
// landing-page.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Landing Page</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      scroll-behavior: smooth;
    }
    /* Navbar */
    .navbar {
      background: rgba(0, 0, 0, 0.6);
    }
    .navbar .nav-link {
      color: #fff !important;
      transition: 0.3s;
    }
    .navbar .nav-link:hover {
      color: #0d6efd !important;
    }

    /* Hero Section */
    .hero-section {
      background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(13,110,253,0.6)),
                  url('https://source.unsplash.com/1600x900/?technology,modern') no-repeat center center/cover;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: white;
      position: relative;
    }
    .hero-section h1 {
      font-size: 3.5rem;
      font-weight: 700;
      animation: fadeInDown 1.5s ease;
    }
    .hero-section p {
      font-size: 1.25rem;
      margin: 20px 0;
      animation: fadeInUp 1.5s ease;
    }
    .hero-btn {
      padding: 12px 30px;
      font-size: 1.1rem;
      border-radius: 50px;
      transition: 0.3s ease;
    }
    .hero-btn:hover {
      transform: scale(1.05);
      background: #0b5ed7;
    }

    /* Card Section */
    .card {
      border: none;
      border-radius: 15px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    /* Footer */
    footer {
      background: #212529;
      color: #aaa;
      padding: 30px 0;
      text-align: center;
    }
    footer a {
      color: #0d6efd;
      margin: 0 10px;
      font-size: 1.2rem;
      transition: 0.3s;
    }
    footer a:hover {
      color: #fff;
    }

    /* Animations */
    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-50px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(50px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand text-white fw-bold" href="#">MyBrand</a>
      <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="home" class="hero-section">
    <div class="container">
      <h1>Build Your Future Website</h1>
      <p>Modern, fast, and responsive landing page with PHP & Bootstrap.</p>
      <a href="#features" class="btn btn-primary hero-btn">Get Started</a>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-5">
    <div class="container text-center">
      <h2 class="fw-bold mb-4">Why Choose Us?</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card shadow-sm p-4">
            <i class="bi bi-lightning-charge-fill text-primary fs-1 mb-3"></i>
            <h5 class="card-title">Fast</h5>
            <p class="card-text">Optimized for performance and blazing speed.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm p-4">
            <i class="bi bi-phone-fill text-success fs-1 mb-3"></i>
            <h5 class="card-title">Responsive</h5>
            <p class="card-text">Looks amazing on desktops, tablets, and mobiles.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm p-4">
            <i class="bi bi-palette-fill text-danger fs-1 mb-3"></i>
            <h5 class="card-title">Modern</h5>
            <p class="card-text">Clean design with the latest web technologies.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section id="contact" class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="fw-bold mb-4">Get in Touch</h2>
      <p>Have a project in mind? Let’s build something great together!</p>
      <a href="mailto:example@email.com" class="btn btn-outline-primary">Contact Us</a>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <p>&copy; <?php echo date("Y"); ?> My Landing Page. All rights reserved.</p>
    <div>
      <a href="#"><i class="bi bi-facebook"></i></a>
      <a href="#"><i class="bi bi-twitter"></i></a>
      <a href="#"><i class="bi bi-instagram"></i></a>
      <a href="#"><i class="bi bi-linkedin"></i></a>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
