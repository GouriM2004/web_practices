<?php
// eCommerce Product Page with Add to Cart functionality
session_start();

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Handle add/remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = (float)$_POST['price'];
    if (!isset($_SESSION['cart'][$id])) {
      $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => 1];
    } else {
      $_SESSION['cart'][$id]['qty']++;
    }
  } elseif (isset($_POST['action']) && $_POST['action'] === 'remove') {
    $id = $_POST['id'];
    unset($_SESSION['cart'][$id]);
  }
}

$total = array_sum(array_map(fn($p) => $p['price'] * $p['qty'], $_SESSION['cart']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Commerce Product Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .product-card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    .product-card:hover {
      transform: translateY(-4px);
    }
    .cart-summary {
      position: sticky;
      top: 1rem;
    }
    .btn-accent {
      background-color: #6f42c1;
      color: #fff;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="#">ShopSmart</a>
    <div class="ms-auto">
      <span class="badge bg-primary">Cart: <?php echo count($_SESSION['cart']); ?> items</span>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row g-4">
    <!-- Product Grid -->
    <div class="col-lg-8">
      <div class="row g-4">
        <?php
        $products = [
          ['id' => 1, 'name' => 'Wireless Headphones', 'price' => 2499, 'img' => 'image.png'],
          ['id' => 2, 'name' => 'Smart Watch', 'price' => 3299, 'img' => 'image copy.png'],
          ['id' => 3, 'name' => 'Bluetooth Speaker', 'price' => 1799, 'img' => 'https://dakaan.pk/product_images/dd4714a681ab4e294f73d84536a127f7.jpg'],
          ['id' => 4, 'name' => 'USB-C Charger', 'price' => 999, 'img' => 'https://m.media-amazon.com/images/I/61qmo3MnwiL._AC_.jpg'],
        ];
        foreach ($products as $p) {
          echo "
          <div class='col-md-6 col-xl-4'>
            <div class='card product-card'>
              <img src='{$p['img']}' class='card-img-top' alt='{$p['name']}'>
              <div class='card-body'>
                <h6 class='card-title'>{$p['name']}</h6>
                <p class='card-text text-muted mb-2'>₹{$p['price']}</p>
                <form method='POST'>
                  <input type='hidden' name='id' value='{$p['id']}'>
                  <input type='hidden' name='name' value='{$p['name']}'>
                  <input type='hidden' name='price' value='{$p['price']}'>
                  <button name='action' value='add' class='btn btn-accent w-100'>Add to Cart</button>
                </form>
              </div>
            </div>
          </div>
          ";
        }
        ?>
      </div>
    </div>

    <!-- Cart Summary -->
    <div class="col-lg-4">
      <div class="card cart-summary p-3">
        <h5 class="mb-3">🛍️ Your Cart</h5>
        <?php if (!empty($_SESSION['cart'])): ?>
          <ul class="list-group mb-3">
            <?php foreach ($_SESSION['cart'] as $id => $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= $item['name'] ?></strong><br>
                  <small class="text-muted">Qty: <?= $item['qty'] ?></small>
                </div>
                <div class="text-end">
                  <div>₹<?= number_format($item['price'] * $item['qty'], 2) ?></div>
                  <form method="POST" class="mt-1">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn btn-sm btn-outline-danger" name="action" value="remove">Remove</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
          <h6 class="text-end">Total: <span class="fw-bold text-success">₹<?= number_format($total, 2) ?></span></h6>
        <?php else: ?>
          <p class="text-muted text-center mb-0">Your cart is empty.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer class="text-center text-muted py-4 border-top">
  © <?= date('Y') ?> ShopSmart — All Rights Reserved
</footer>
</body>
</html>
