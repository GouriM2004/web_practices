<?php
// product.php
// Sample product data (you can later connect with database)
$product = [
    "name" => "Wireless Headphones",
    "price" => "₹2,999",
    "image" => "https://m.media-amazon.com/images/I/61q+RT4CybS._AC_SL1500_.jpg",
    "description" => "Experience high-quality sound with our premium wireless headphones. 
                      Designed for comfort, durability, and superior audio performance.",
    "features" => [
        "Bluetooth 5.0 connectivity",
        "Up to 20 hours battery life",
        "Noise cancellation technology",
        "Built-in microphone for calls",
        "Lightweight and foldable design"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Showcase - <?php echo $product['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .product-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-8px);
        }
        .product-img {
            border-bottom: 2px solid #eee;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .feature-list li {
            margin-bottom: 8px;
        }
        .btn-buy {
            border-radius: 30px;
            padding: 10px 25px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="product-card p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6 text-center">
                        <img src="<?php echo $product['image']; ?>" class="img-fluid product-img rounded" alt="Product Image">
                    </div>
                    <div class="col-md-6">
                        <h2 class="fw-bold"><?php echo $product['name']; ?></h2>
                        <p class="price-tag"><?php echo $product['price']; ?></p>
                        <p><?php echo $product['description']; ?></p>
                        <h5>Features:</h5>
                        <ul class="feature-list">
                            <?php foreach ($product['features'] as $feature) { ?>
                                <li>✅ <?php echo $feature; ?></li>
                            <?php } ?>
                        </ul>
                        <button class="btn btn-primary btn-buy mt-3" onclick="buyNow()">Buy Now</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function buyNow() {
    alert("Thank you for showing interest! Redirecting to checkout...");
    window.location.href = "#"; // Replace with checkout page
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
