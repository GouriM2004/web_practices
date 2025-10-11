<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gouri More | Portfolio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6C63FF;
      --secondary: #00C9A7;
      --bg-light: #f9f9f9;
      --bg-dark: #0e0e0e;
      --text-light: #f1f1f1;
      --text-dark: #222;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-dark);
      transition: all 0.4s ease-in-out;
    }

    /* Navbar */
    .navbar {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
    }

    .dark-mode .navbar {
      background: rgba(20, 20, 20, 0.9);
    }

    .navbar-brand {
      font-weight: 700;
      color: var(--primary) !important;
      font-size: 1.4rem;
    }

    /* Hero Section */
    .hero {
      height: 90vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .hero h1 {
      font-size: 3rem;
      font-weight: 700;
    }

    .hero p {
      font-size: 1.1rem;
      margin-bottom: 20px;
    }

    .hero .btn-primary {
      background: white;
      color: var(--primary);
      border: none;
      padding: 10px 30px;
      font-weight: 600;
      border-radius: 50px;
      transition: all 0.3s ease;
    }

    .hero .btn-primary:hover {
      background: var(--secondary);
      color: white;
      transform: scale(1.05);
    }

    /* About Section */
    #about {
      padding: 80px 0;
    }

    .about-text {
      max-width: 700px;
      margin: 0 auto;
      text-align: center;
    }

    /* Projects */
    #projects {
      padding: 80px 0;
    }

    .project-card {
      border: none;
      border-radius: 16px;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .project-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .project-card img {
      height: 220px;
      object-fit: cover;
    }

    .card-body {
      background: white;
    }

    /* Contact */
    #contact {
      padding: 80px 0;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
    }

    #contact form {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 30px;
      max-width: 600px;
      margin: auto;
    }

    #contact input, #contact textarea {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
    }

    #contact input::placeholder, #contact textarea::placeholder {
      color: #eee;
    }

    .btn-glow {
      background: white;
      color: var(--primary);
      border-radius: 30px;
      font-weight: 600;
      transition: all 0.3s;
    }

    .btn-glow:hover {
      background: var(--secondary);
      color: white;
      transform: scale(1.05);
    }

    /* Footer */
    footer {
      background: #111;
      color: #bbb;
      padding: 20px 0;
      text-align: center;
    }

    /* Dark Mode */
    .dark-mode {
      background-color: var(--bg-dark);
      color: var(--text-light);
    }

    .dark-mode .card-body {
      background: #1e1e1e;
    }

    .dark-mode footer {
      background: #000;
      color: #aaa;
    }

    .dark-mode input, 
    .dark-mode textarea {
      background: rgba(255, 255, 255, 0.1);
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg px-4 sticky-top shadow-sm">
    <a class="navbar-brand" href="#">Gouri<span style="color:#00C9A7">.Portfolio</span></a>
    <div class="ms-auto">
      <button id="toggleDarkMode" class="btn btn-outline-primary rounded-pill">🌙 Dark Mode</button>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero">
    <h1>Hello, I'm <span class="fw-bold">Gouri More</span></h1>
    <p>Front-End Developer • UI Designer • Problem Solver</p>
    <a href="#projects" class="btn btn-primary">Explore Projects</a>
  </section>

  <!-- About -->
  <section id="about" class="text-center">
    <h2 class="fw-bold mb-4 text-primary">About Me</h2>
    <p class="about-text">
      I’m a creative front-end developer passionate about building beautiful, responsive, and intuitive websites.
      I love combining design and technology to craft seamless digital experiences.
    </p>
  </section>

  <!-- Projects -->
  <section id="projects">
    <div class="container">
      <h2 class="fw-bold text-center text-primary mb-5">My Projects</h2>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card project-card shadow">
            <img src="https://via.placeholder.com/400x250" class="card-img-top" alt="Project 1">
            <div class="card-body">
              <h5 class="fw-bold">Portfolio Website</h5>
              <p>A personal portfolio built using Bootstrap and JavaScript with modern dark mode support.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card project-card shadow">
            <img src="https://via.placeholder.com/400x250" class="card-img-top" alt="Project 2">
            <div class="card-body">
              <h5 class="fw-bold">E-Commerce Store</h5>
              <p>Fully responsive store with add-to-cart system using PHP and Bootstrap.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card project-card shadow">
            <img src="https://via.placeholder.com/400x250" class="card-img-top" alt="Project 3">
            <div class="card-body">
              <h5 class="fw-bold">Notes App</h5>
              <p>Simple and elegant note-taking web app using localStorage and JavaScript.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact -->
  <section id="contact">
    <h2 class="fw-bold text-center mb-4">Get in Touch</h2>
    <form>
      <div class="mb-3">
        <input type="text" class="form-control" placeholder="Your Name">
      </div>
      <div class="mb-3">
        <input type="email" class="form-control" placeholder="Your Email">
      </div>
      <div class="mb-3">
        <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
      </div>
      <button type="submit" class="btn btn-glow w-100">Send Message</button>
    </form>
  </section>

  <!-- Footer -->
  <footer>
    <p>© 2025 Gouri More | Designed with ❤️ using Bootstrap</p>
  </footer>

  <script>
    const body = document.body;
    const toggleBtn = document.getElementById('toggleDarkMode');
    let dark = localStorage.getItem('darkMode') === 'true';

    const updateTheme = () => {
      if (dark) {
        body.classList.add('dark-mode');
        toggleBtn.textContent = '☀️ Light Mode';
      } else {
        body.classList.remove('dark-mode');
        toggleBtn.textContent = '🌙 Dark Mode';
      }
    };

    updateTheme();

    toggleBtn.addEventListener('click', () => {
      dark = !dark;
      localStorage.setItem('darkMode', dark);
      updateTheme();
    });
  </script>
</body>
</html>
