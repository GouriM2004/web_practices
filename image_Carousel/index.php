<?php
$images = [
  'https://picsum.photos/id/1015/1200/500',
  'https://picsum.photos/id/1016/1200/500',
  'https://picsum.photos/id/1018/1200/500',
  'https://picsum.photos/id/1020/1200/500'
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Professional Image Carousel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #eef2f3, #d9e4f5);
      font-family: 'Poppins', sans-serif;
      padding: 3rem;
    }
    h2 {
      font-weight: 700;
      color: #333;
      margin-bottom: 2rem;
    }
    .carousel-item img {
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      object-fit: cover;
      width: 100%;
      height: 500px;
      transition: transform 0.6s ease;
    }
    .carousel-item img:hover {
      transform: scale(1.03);
    }
    .carousel-caption {
      background: rgba(0, 0, 0, 0.6);
      border-radius: 15px;
      padding: 1.2rem;
      animation: fadeInUp 1s ease;
    }
    .carousel-caption h5 {
      font-size: 1.5rem;
      font-weight: 600;
    }
    .carousel-caption p {
      font-size: 1rem;
      margin: 0;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .carousel-indicators button {
      width: 12px;
      height: 12px;
      border-radius: 50%;
    }
  </style>
</head>
<body>
  <div class="container text-center">
    <h2>🌟 Professional Image Carousel 🌟</h2>
    <div id="carouselExample" class="carousel slide carousel-fade shadow-lg" data-bs-ride="carousel" data-bs-interval="3500">
      <div class="carousel-inner">
        <?php foreach ($images as $i => $img): ?>
          <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
            <img src="<?php echo $img; ?>" class="d-block" alt="Slide <?php echo $i+1; ?>">
            <div class="carousel-caption d-none d-md-block">
              <h5>Elegant Slide <?php echo $i+1; ?></h5>
              <p>Beautiful and responsive carousel with modern styling.</p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
      <div class="carousel-indicators">
        <?php foreach ($images as $i => $img): ?>
          <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="<?php echo $i; ?>" <?php if ($i===0) echo 'class="active" aria-current="true"'; ?> aria-label="Slide <?php echo $i+1; ?>"></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>