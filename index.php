<?php
// You can later replace these with database fetch queries
$starters = [
  ["name" => "Spring Rolls", "desc" => "Crispy rolls stuffed with veggies.", "price" => 120, "img" => "image copy.png"],
  ["name" => "Tomato Soup", "desc" => "Rich and creamy with croutons.", "price" => 90, "img" => "image.png"],
  ["name" => "Paneer Tikka", "desc" => "Grilled paneer with spices.", "price" => 150, "img" => "image copy 2.png"]
];

$maincourse = [
  ["name" => "Butter Chicken", "desc" => "Creamy tomato gravy with chicken.", "price" => 250, "img" => "image copy 3.png"],
  ["name" => "Veg Biryani", "desc" => "Fragrant rice with vegetables.", "price" => 180, "img" => "image copy 4.png"],
  ["name" => "Dal Makhani", "desc" => "Slow-cooked black lentils.", "price" => 160, "img" => "image copy 5.png"]
];

$desserts = [
  ["name" => "Gulab Jamun", "desc" => "Soft milk-solid balls in syrup.", "price" => 90, "img" => "image copy 6.png"],
  ["name" => "Ice Cream Sundae", "desc" => "Scoops topped with nuts & syrup.", "price" => 120, "img" => "image copy 7.png"],
  ["name" => "Cheesecake", "desc" => "Rich and creamy slice of delight.", "price" => 150, "img" => "image copy 8.png"]
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurant Menu</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(135deg, #fff, #f8f9fa);
        font-family: 'Segoe UI', sans-serif;
      }
      header {
        padding: 3rem 1rem;
        text-align: center;
        margin-bottom: 3rem;
        border-radius: 0 0 30px 30px;
        box-shadow: 0 6px 20px rgba(0,0,0,.1);
      }
      header h1 {font-weight: 700;}
      .menu-section {margin-bottom: 4rem;}
      .card {
        border-radius: 16px;
        overflow: hidden;
        transition: transform .25s, box-shadow .25s;
      }
      .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 24px rgba(0,0,0,.12);
      }
      .card-img-top {
        height: 200px;
        object-fit: cover;
      }
      .card-body h5 {font-weight: 600;}
      .price {
        font-weight: bold;
        font-size: 1.1rem;
        color: #ff6f61;
      }
      h3 {
        font-weight: 700;
        color: #ff6f61;
      }
      footer {
        background: #212529;
        color: #adb5bd;
        padding: 1.5rem;
        margin-top: 4rem;
        border-radius: 20px 20px 0 0;
      }
      .navbar {
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,.08);
      }
      .navbar-brand {
        font-weight: 700;
        color: #ff6f61 !important;
      }
      .nav-link {
        color: #495057 !important;
        font-weight: 500;
      }
      .nav-link:hover {
        color: #ff6f61 !important;
      }
    </style>
  </head>
  <body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
      <div class="container">
        <a class="navbar-brand" href="#">🍴 Foodies</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="#starters">Starters</a></li>
            <li class="nav-item"><a class="nav-link" href="#maincourse">Main Course</a></li>
            <li class="nav-item"><a class="nav-link" href="#desserts">Desserts</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Hero Header -->
    <header>
      <h1 class="display-5 fw-bold">Our Restaurant Menu</h1>
      <p class="lead">Fresh • Delicious • Made with ❤️</p>
    </header>

    <div class="container">
      <!-- Starters Section -->
      <div id="starters" class="menu-section">
        <h3 class="mb-3 border-bottom pb-2">Starters</h3>
        <div class="row g-4">
          <?php foreach($starters as $dish): ?>
            <div class="col-12 col-md-4">
              <div class="card h-100">
                <img src="<?= $dish['img'] ?>" class="card-img-top" alt="<?= $dish['name'] ?>">
                <div class="card-body">
                  <h5 class="card-title"><?= $dish['name'] ?></h5>
                  <p class="card-text text-muted"><?= $dish['desc'] ?></p>
                  <p class="price">₹<?= $dish['price'] ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Main Course Section -->
      <div id="maincourse" class="menu-section">
        <h3 class="mb-3 border-bottom pb-2">Main Course</h3>
        <div class="row g-4">
          <?php foreach($maincourse as $dish): ?>
            <div class="col-12 col-md-4">
              <div class="card h-100">
                <img src="<?= $dish['img'] ?>" class="card-img-top" alt="<?= $dish['name'] ?>">
                <div class="card-body">
                  <h5 class="card-title"><?= $dish['name'] ?></h5>
                  <p class="card-text text-muted"><?= $dish['desc'] ?></p>
                  <p class="price">₹<?= $dish['price'] ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Desserts Section -->
      <div id="desserts" class="menu-section">
        <h3 class="mb-3 border-bottom pb-2">Desserts</h3>
        <div class="row g-4">
          <?php foreach($desserts as $dish): ?>
            <div class="col-12 col-md-4">
              <div class="card h-100">
                <img src="<?= $dish['img'] ?>" class="card-img-top" alt="<?= $dish['name'] ?>">
                <div class="card-body">
                  <h5 class="card-title"><?= $dish['name'] ?></h5>
                  <p class="card-text text-muted"><?= $dish['desc'] ?></p>
                  <p class="price">₹<?= $dish['price'] ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <footer class="text-center small">© <?= date("Y") ?> Foodies Restaurant</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
