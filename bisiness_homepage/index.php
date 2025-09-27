<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CompanyName</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- AOS Animation Library -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">CompanyName</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section id="home" class="hero d-flex align-items-center">
  <div class="container text-center text-white" data-aos="fade-up">
    <h1 class="display-4 fw-bold">Empowering Your Business</h1>
    <p class="lead mb-4">We deliver innovative solutions to help your business thrive in the digital world.</p>
    <a href="#contact" class="btn btn-primary btn-lg shadow">Get in Touch <i class="bi bi-arrow-right"></i></a>
  </div>
</section>

<!-- Services Section -->
<section id="services" class="py-5">
  <div class="container">
    <h2 class="text-center mb-5" data-aos="fade-up">Our Services</h2>
    <div class="row g-4">
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="card shadow-sm border-0 h-100 text-center p-3 hover-card">
          <i class="bi bi-code-slash display-4 text-primary mb-3"></i>
          <h5 class="card-title">Web Development</h5>
          <p class="card-text">Creating modern, responsive websites tailored to your business needs.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card shadow-sm border-0 h-100 text-center p-3 hover-card">
          <i class="bi bi-megaphone display-4 text-primary mb-3"></i>
          <h5 class="card-title">Digital Marketing</h5>
          <p class="card-text">Boost your brand with effective online marketing strategies.</p>
        </div>
      </div>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="card shadow-sm border-0 h-100 text-center p-3 hover-card">
          <i class="bi bi-lightbulb display-4 text-primary mb-3"></i>
          <h5 class="card-title">Consulting</h5>
          <p class="card-text">Expert advice to help your business achieve its full potential.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- About Section -->
<section id="about" class="bg-light py-5">
  <div class="container">
    <h2 class="text-center mb-4" data-aos="fade-up">About Us</h2>
    <p class="text-center mx-auto lead" style="max-width:700px;" data-aos="fade-up" data-aos-delay="100">
      CompanyName is a leading provider of innovative business solutions. We focus on creativity, technology, and customer satisfaction to help businesses grow efficiently.
    </p>
  </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5">
  <div class="container">
    <h2 class="text-center mb-5" data-aos="fade-up">Contact Us</h2>
    <div class="row justify-content-center">
      <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
        <form id="contactForm" class="p-4 shadow rounded bg-white">
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" required>
          </div>
          <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" rows="4" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100 shadow-sm">Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">
  <div class="container">
    <p class="mb-2">&copy; <?php echo date("Y"); ?> CompanyName. All Rights Reserved.</p>
    <div>
      <a href="#" class="text-white mx-2"><i class="bi bi-facebook"></i></a>
      <a href="#" class="text-white mx-2"><i class="bi bi-twitter"></i></a>
      <a href="#" class="text-white mx-2"><i class="bi bi-linkedin"></i></a>
    </div>
  </div>
</footer>

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="script.js"></script>
<script>
  AOS.init({ duration: 1000, once: true });
</script>
</body>
</html>
