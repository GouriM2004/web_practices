<?php
// shopping_cart.php
session_start();

// Initialize cart session
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Handle Add / Remove actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  $id = $_POST['id'];
  $name = $_POST['name'];
  $price = (float)$_POST['price'];

  if ($action === 'add') {
    if (!isset($_SESSION['cart'][$id])) {
      $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => 1];
    } else {
      $_SESSION['cart'][$id]['qty']++;
    }
  }

  if ($action === 'remove') {
    unset($_SESSION['cart'][$id]);
  }
}

// Calculate total
$total = array_sum(array_map(fn($p) => $p['price'] * $p['qty'], $_SESSION['cart']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .product-card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease-in-out;
    }
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
    }
    .btn-accent {
      background-color: #007bff;
      color: #fff;
      border-radius: 30px;
      transition: all 0.2s;
    }
    .btn-accent:hover {
      background-color: #0056b3;
    }
    .cart-box {
      border-radius: 15px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
      background-color: #fff;
      padding: 20px;
    }
    footer {
      background: #fff;
      border-top: 1px solid #ddd;
      padding: 15px 0;
      text-align: center;
      color: #6c757d;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="#">🛍️ MyCart</a>
    <div class="ms-auto">
      <span class="badge bg-primary p-2">Cart: <?= count($_SESSION['cart']); ?> items</span>
    </div>
  </div>
</nav>

<div class="container my-5">
  <div class="row g-4">
    <!-- Product Grid -->
    <div class="col-lg-8">
      <div class="row g-4">
        <?php
        $products = [
          ['id' => 1, 'name' => 'Bluetooth Headphones', 'price' => 1499, 'img' => 'https://m.media-amazon.com/images/I/61K4azdo8BL._AC_SL1500_.jpg'],
          ['id' => 2, 'name' => 'Smart Watch', 'price' => 2599, 'img' => 'https://m.media-amazon.com/images/I/71eeeyUk2eL._AC_SL1500_.jpg'],
          ['id' => 3, 'name' => 'Gaming Mouse', 'price' => 899, 'img' => 'https://m.media-amazon.com/images/I/61Mk3YqYHpL.jpg'],
          ['id' => 4, 'name' => 'Keyboard', 'price' => 999, 'img' => 'https://cdn.mos.cms.futurecdn.net/wmSqYrfFt3AfSGZjnbYmvm.jpg'],
        ];

        foreach ($products as $p) {
          echo "
          <div class='col-md-6 col-lg-4'>
            <div class='card product-card'>
              <img src='{$p['img']}' class='card-img-top' alt='{$p['name']}'>
              <div class='card-body text-center'>
                <h6 class='fw-semibold'>{$p['name']}</h6>
                <p class='text-muted'>₹{$p['price']}</p>
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

    <!-- Cart Section -->
    <div class="col-lg-4">
      <div class="cart-box">
        <h5 class="fw-bold mb-3 text-primary">🛒 Shopping Cart</h5>

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
                    <input type="hidden" name="name" value="<?= $item['name'] ?>">
                    <input type="hidden" name="price" value="<?= $item['price'] ?>">
                    <button class="btn btn-sm btn-outline-danger" name="action" value="remove">Remove</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="d-flex justify-content-between">
            <strong>Total:</strong>
            <span class="fw-bold text-success">₹<?= number_format($total, 2) ?></span>
          </div>
        <?php else: ?>
          <p class="text-muted text-center my-3">Your cart is empty.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer>
  © <?= date('Y') ?> MyCart | Designed with ❤️ using Bootstrap
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
