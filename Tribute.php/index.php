<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tribute Page</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- AOS (Animate on Scroll) -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
      color: #fff;
      scroll-behavior: smooth;
    }
    /* Hero */
    .hero {
      background: url("https://source.unsplash.com/1600x800/?leader,inspiration") no-repeat center center/cover;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      position: relative;
    }
    .hero::after {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.6);
    }
    .hero-content {
      position: relative;
      z-index: 1;
      animation: fadeIn 2s ease-in-out;
    }
    .hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
    }
    .hero p {
      font-size: 1.3rem;
      opacity: 0.9;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    /* Sections */
    .section {
      padding: 4rem 1rem;
    }
    .card-custom {
      background: rgba(255,255,255,0.08);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
      transition: transform 0.3s;
    }
    .card-custom:hover {
      transform: translateY(-10px);
    }
    .quote-section {
      background: linear-gradient(135deg, #00c6ff, #0072ff);
      padding: 2rem;
      border-radius: 20px;
      color: #fff;
      font-size: 1.2rem;
      text-align: center;
      margin: 3rem auto;
      max-width: 800px;
    }
    footer {
      background: #0d1117;
      padding: 1.2rem;
      text-align: center;
      margin-top: 3rem;
      font-size: 0.9rem;
      color: #bbb;
    }
  </style>
</head>
<body>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-content">
      <h1>🌟 Tribute to A.P.J. Abdul Kalam</h1>
      <p>“The Missile Man of India & People’s President”</p>
      <a href="#bio" class="btn btn-light btn-lg mt-3"><i class="bi bi-arrow-down"></i> Explore</a>
    </div>
  </section>

  <!-- Bio Section -->
  <section id="bio" class="section container">
    <div class="row align-items-center">
      <div class="col-md-5 mb-4 mb-md-0" data-aos="fade-right">
        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b0/A._P._J._Abdul_Kalam.jpg" 
             class="img-fluid rounded-4 shadow-lg" alt="APJ Abdul Kalam">
      </div>
      <div class="col-md-7" data-aos="fade-left">
        <div class="card-custom">
          <h2 class="mb-3">About Him</h2>
          <p>
            Avul Pakir Jainulabdeen Abdul Kalam (1931–2015) was an Indian aerospace scientist and 
            the 11th President of India. Known as the "Missile Man of India," he played a pivotal role 
            in India's space and missile development programs. His humility, vision, and dedication 
            to students made him one of the most beloved leaders in India.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Quote -->
  <section class="quote-section" data-aos="zoom-in">
    ✨ “Dream, dream, dream. Dreams transform into thoughts and thoughts result in action.” ✨
  </section>

  <!-- Timeline -->
  <section id="timeline" class="section container">
    <h2 class="text-center mb-5" data-aos="fade-up">Life Timeline</h2>
    <div class="row g-4">
      <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
        <div class="card-custom">
          <h5>1931</h5>
          <p>Born in Rameswaram, Tamil Nadu</p>
        </div>
      </div>
      <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="card-custom">
          <h5>1969</h5>
          <p>Joined ISRO, led SLV-III project</p>
        </div>
      </div>
      <div class="col-md-6" data-aos="fade-up" data-aos-delay="300">
        <div class="card-custom">
          <h5>1998</h5>
          <p>Key role in Pokhran-II nuclear tests</p>
        </div>
      </div>
      <div class="col-md-6" data-aos="fade-up" data-aos-delay="400">
        <div class="card-custom">
          <h5>2002</h5>
          <p>Became 11th President of India</p>
        </div>
      </div>
      <div class="col-md-6 mx-auto" data-aos="fade-up" data-aos-delay="500">
        <div class="card-custom">
          <h5>2015</h5>
          <p>Passed away delivering a lecture at IIM Shillong</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <p>Created with ❤️ as a tribute | © <?php echo date("Y"); ?> Tribute Page</p>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- AOS Animation JS -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 1000, once: true });
  </script>
</body>
</html>
