<?php
// gallery.php (demo data, later you can load from DB)
$images = [
  ["src" => "https://picsum.photos/id/1015/800/600", "title" => "Mountain View", "category" => "Nature"],
  ["src" => "https://picsum.photos/id/1016/800/600", "title" => "Forest Path", "category" => "Nature"],
  ["src" => "https://picsum.photos/id/1018/800/600", "title" => "Sunny Beach", "category" => "Travel"],
  ["src" => "https://picsum.photos/id/1020/800/600", "title" => "City Skyline", "category" => "Architecture"],
  ["src" => "https://picsum.photos/id/1024/800/600", "title" => "Cute Doggo", "category" => "Animals"],
  ["src" => "https://picsum.photos/id/1031/800/600", "title" => "Smiling Faces", "category" => "People"],
  ["src" => "https://picsum.photos/id/1035/800/600", "title" => "Old Castle", "category" => "Architecture"],
  ["src" => "https://picsum.photos/id/1043/800/600", "title" => "Wild Tiger", "category" => "Animals"],
];
$categories = ["All", "Nature", "Travel", "People", "Architecture", "Animals"];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Beautiful Photo Gallery</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
      background-size: 400% 400%;
      animation: gradientBG 12s ease infinite;
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
      overflow-x: hidden;
    }
    @keyframes gradientBG {
      0%{background-position:0% 50%}
      50%{background-position:100% 50%}
      100%{background-position:0% 50%}
    }
    .gallery {
      padding: 70px 0;
    }
    h2 {
      font-size: 2.5rem;
      font-weight: bold;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
    }
    /* Filter Buttons */
    .filter-btns {
      text-align: center;
      margin-bottom: 50px;
    }
    .filter-btns button {
      margin: 6px;
      border-radius: 30px;
      padding: 10px 25px;
      font-weight: bold;
      border: none;
      background: rgba(255,255,255,0.1);
      color: #fff;
      transition: 0.4s;
      backdrop-filter: blur(8px);
    }
    .filter-btns button:hover,
    .filter-btns button.active {
      background: linear-gradient(45deg,#ff512f,#dd2476);
      box-shadow: 0 0 12px rgba(255,100,150,0.8);
    }
    /* Gallery Items */
    .gallery-item {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      transform: scale(1);
      transition: transform 0.4s ease, box-shadow 0.4s ease;
      animation: fadeInUp 0.8s ease forwards;
      opacity: 0;
    }
    @keyframes fadeInUp {
      from {transform: translateY(50px); opacity:0;}
      to {transform: translateY(0); opacity:1;}
    }
    .gallery-item img {
      width: 100%;
      border-radius: 16px;
      transition: transform 0.4s ease;
    }
    .gallery-item:hover img {
      transform: scale(1.1);
    }
    /* Overlay */
    .overlay {
      position: absolute;
      top:0; left:0;
      width:100%; height:100%;
      background: rgba(0,0,0,0.55);
      display: flex;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: opacity 0.4s ease;
      flex-direction: column;
      text-align: center;
    }
    .gallery-item:hover .overlay {
      opacity: 1;
    }
    .overlay h5 {
      font-size: 1.2rem;
      margin-bottom: 8px;
    }
    .overlay i {
      font-size: 28px;
      color: #ff4081;
      background: #fff;
      border-radius: 50%;
      padding: 10px;
      transition: transform 0.3s;
    }
    .overlay i:hover {
      transform: scale(1.2);
    }
    /* Modal */
    .modal-content {
      background: transparent;
      border: none;
      text-align: center;
    }
    .modal-img {
      max-width: 100%;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.6);
    }
    .modal-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      font-size: 40px;
      color: white;
      cursor: pointer;
      padding: 10px;
    }
    .modal-nav.left { left: 15px; }
    .modal-nav.right { right: 15px; }
    .modal-nav:hover { color: #ff4081; }
  </style>
</head>
<body>
  <div class="container gallery">
    <h2 class="text-center mb-5">✨ Beautiful & Modern Photo Gallery ✨</h2>

    <!-- Filter Buttons -->
    <div class="filter-btns">
      <?php foreach($categories as $cat): ?>
        <button class="filter-btn <?= $cat === 'All' ? 'active' : '' ?>" data-category="<?= $cat ?>"><?= $cat ?></button>
      <?php endforeach; ?>
    </div>

    <!-- Gallery Grid -->
    <div class="row g-4" id="galleryGrid">
      <?php foreach($images as $index => $img): ?>
        <div class="col-sm-6 col-md-4 col-lg-3 gallery-box" data-category="<?= $img['category'] ?>">
          <div class="gallery-item" style="animation-delay: <?= $index*0.15 ?>s">
            <img src="<?= $img['src'] ?>" alt="<?= $img['title'] ?>" data-index="<?= $index ?>" data-bs-toggle="modal" data-bs-target="#imageModal">
            <div class="overlay">
              <h5><?= $img['title'] ?></h5>
              <i class="fas fa-search-plus"></i>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Modal with Slideshow -->
  <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg position-relative">
      <div class="modal-content">
        <span class="modal-nav left" id="prev"><i class="fas fa-chevron-left"></i></span>
        <img src="" class="modal-img" id="modalImage" alt="Full Image">
        <span class="modal-nav right" id="next"><i class="fas fa-chevron-right"></i></span>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const images = <?php echo json_encode(array_column($images, 'src')); ?>;
    let currentIndex = 0;

    // Open clicked image
    document.querySelectorAll(".gallery-item img").forEach(item => {
      item.addEventListener("click", function() {
        currentIndex = parseInt(this.dataset.index);
        showImage();
      });
    });

    // Show image in modal
    function showImage() {
      document.getElementById("modalImage").src = images[currentIndex];
    }

    // Next / Prev
    document.getElementById("next").addEventListener("click", () => {
      currentIndex = (currentIndex + 1) % images.length;
      showImage();
    });
    document.getElementById("prev").addEventListener("click", () => {
      currentIndex = (currentIndex - 1 + images.length) % images.length;
      showImage();
    });

    // Filter
    const filterBtns = document.querySelectorAll(".filter-btn");
    const galleryBoxes = document.querySelectorAll(".gallery-box");
    filterBtns.forEach(btn => {
      btn.addEventListener("click", () => {
        filterBtns.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        const category = btn.dataset.category;
        galleryBoxes.forEach(box => {
          if (category === "All" || box.dataset.category === category) {
            box.style.display = "block";
          } else {
            box.style.display = "none";
          }
        });
      });
    });
  </script>
</body>
</html>
