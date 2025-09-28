<!-- faq.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FAQ - MyWebsite</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333;
    }
    /* Navbar */
    .navbar {
      background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);

    }
    .navbar-brand, .navbar-nav .nav-link {
      color: black !important;
      font-weight: 500;
    }
    .navbar-nav .nav-link:hover {
      color: #ffd700 !important;
    }
    /* Header */
    .faq-header {
      padding: 70px 20px;
      background: #0d6efd;
      color: #fff;
      text-align: center;
      border-radius: 0 0 40px 40px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .faq-header h1 {
      font-weight: 700;
    }
    .search-box {
      max-width: 500px;
      margin: 25px auto 0;
    }
    .search-box input {
      border-radius: 30px;
      padding: 12px 20px;
      border: none;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    /* Tabs */
    .nav-pills .nav-link {
      border-radius: 25px;
      padding: 10px 20px;
      font-weight: 500;
      color: #0d6efd;
      border: 1px solid #0d6efd;
      margin: 5px;
    }
    .nav-pills .nav-link.active {
      background-color: #0d6efd;
      color: #fff;
    }
    /* Accordion */
    .accordion-item {
      border: none;
      margin-bottom: 15px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .accordion-button {
      font-weight: 600;
      padding: 15px 20px;
      background: #fff;
    }
    .accordion-button::after {
      font-family: "Bootstrap-icons";
      content: "\f4fe"; /* plus-circle */
      font-size: 1.2rem;
      color: #0d6efd;
      transition: transform 0.3s ease;
    }
    .accordion-button:not(.collapsed)::after {
      content: "\f2ea"; /* dash-circle */
      color: #dc3545;
    }
    .accordion-body {
      background: #fff;
      font-size: 0.95rem;
      line-height: 1.6;
    }
    /* Footer */
    footer {
      background: #fff;
      padding: 20px 0;
      margin-top: 60px;
      border-top: 1px solid #e9ecef;
      color: #6c757d;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">MyWebsite</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">FAQ</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Header -->
  <div class="faq-header">
    <h1>Frequently Asked Questions</h1>
    <p class="lead">Find quick answers to your most common questions</p>
    <div class="search-box">
      <input type="text" id="faqSearch" class="form-control" placeholder="Search questions...">
    </div>
  </div>

  <!-- Categories -->
  <div class="container py-4">
    <ul class="nav nav-pills justify-content-center mb-4" id="faqTabs">
      <li class="nav-item"><button class="nav-link active" data-category="all">All</button></li>
      <li class="nav-item"><button class="nav-link" data-category="general">General</button></li>
      <li class="nav-item"><button class="nav-link" data-category="account">Account</button></li>
      <li class="nav-item"><button class="nav-link" data-category="security">Security</button></li>
    </ul>

    <div class="accordion" id="faqAccordion">
      <!-- General -->
      <div class="accordion-item" data-category="general">
        <h2 class="accordion-header" id="faqHeadingOne">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqOne">
            What is the purpose of this website?
          </button>
        </h2>
        <div id="faqOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            This website is designed to connect students with job opportunities, provide resources, and simplify the application process.
          </div>
        </div>
      </div>

      <!-- Account -->
      <div class="accordion-item" data-category="account">
        <h2 class="accordion-header" id="faqHeadingTwo">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwo">
            How do I create an account?
          </button>
        </h2>
        <div id="faqTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Click on the <strong>Sign Up</strong> button in the navigation bar and fill out the required details to create your account.
          </div>
        </div>
      </div>

      <!-- Security -->
      <div class="accordion-item" data-category="security">
        <h2 class="accordion-header" id="faqHeadingThree">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThree">
            Is my personal data safe?
          </button>
        </h2>
        <div id="faqThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Yes! We follow industry-standard encryption and privacy practices to make sure your personal data is secure.
          </div>
        </div>
      </div>

      <!-- Account -->
      <div class="accordion-item" data-category="account">
        <h2 class="accordion-header" id="faqHeadingFour">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqFour">
            Can I save jobs for later?
          </button>
        </h2>
        <div id="faqFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Absolutely! You can save jobs to your <strong>Saved Jobs</strong> tab and apply whenever you’re ready.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-center">
    <p class="mb-0">© <?php echo date("Y"); ?> MyWebsite. All Rights Reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // FAQ Search Filter
    document.getElementById("faqSearch").addEventListener("keyup", function() {
      let value = this.value.toLowerCase();
      document.querySelectorAll(".accordion-item").forEach(function(item) {
        let text = item.innerText.toLowerCase();
        item.style.display = text.includes(value) ? "" : "none";
      });
    });

    // Category Tabs
    document.querySelectorAll("#faqTabs .nav-link").forEach(function(tab) {
      tab.addEventListener("click", function() {
        document.querySelectorAll("#faqTabs .nav-link").forEach(t => t.classList.remove("active"));
        this.classList.add("active");
        let category = this.getAttribute("data-category");
        document.querySelectorAll(".accordion-item").forEach(function(item) {
          item.style.display = (category === "all" || item.dataset.category === category) ? "" : "none";
        });
      });
    });
  </script>
</body>
</html>
