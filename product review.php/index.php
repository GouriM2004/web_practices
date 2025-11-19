<?php
session_start();

// Simple in-memory product list (static)
$products = [
    ['id' => 1, 'name' => 'Aurora Wireless Headphones', 'category' => 'Electronics', 'price' => 79.99, 'image' => 'https://images.unsplash.com/photo-1518444027188-1b4e420c9f29?w=800&h=600&fit=crop'],
    ['id' => 2, 'name' => 'Cedarwood Scented Candle', 'category' => 'Home', 'price' => 19.50, 'image' => 'https://images.unsplash.com/photo-1514144287417-5f2f3b2f4b47?w=800&h=600&fit=crop'],
    ['id' => 3, 'name' => 'Velvet Throw Blanket', 'category' => 'Home', 'price' => 45.00, 'image' => 'https://images.unsplash.com/photo-1549187774-b4f9b7f3c1e5?w=800&h=600&fit=crop'],
    ['id' => 4, 'name' => 'Nimbus Smartwatch', 'category' => 'Wearables', 'price' => 199.00, 'image' => 'https://images.unsplash.com/photo-1516574187841-cb9cc2ca948b?w=800&h=600&fit=crop']
];

// Initialize reviews in session
if (!isset($_SESSION['reviews'])) {
    $_SESSION['reviews'] = [
        1 => [
            ['name' => 'Priya', 'rating' => 5, 'title' => 'Excellent sound', 'comment' => 'Great bass and battery life.', 'date' => '2025-11-18'],
            ['name' => 'Aman', 'rating' => 4, 'title' => 'Very good', 'comment' => 'Comfortable but a bit tight.', 'date' => '2025-10-05']
        ],
        2 => [
            ['name' => 'Rina', 'rating' => 5, 'title' => 'Lovely aroma', 'comment' => 'Lasts for many hours, highly recommended.', 'date' => '2025-09-12']
        ],
        3 => [],
        4 => [
            ['name' => 'Dev', 'rating' => 3, 'title' => 'Good features', 'comment' => 'Decent but battery drains fast.', 'date' => '2025-08-30']
        ]
    ];
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = intval($_POST['product_id']);
    $name = trim(substr(htmlspecialchars($_POST['name'] ?? 'Anonymous'), 0, 60));
    $title = trim(substr(htmlspecialchars($_POST['title'] ?? ''), 0, 80));
    $comment = trim(substr(htmlspecialchars($_POST['comment'] ?? ''), 0, 1000));
    $rating = max(1, min(5, intval($_POST['rating'] ?? 5)));
    $date = date('Y-m-d');

    // Add to session reviews
    if (!isset($_SESSION['reviews'][$product_id])) {
        $_SESSION['reviews'][$product_id] = [];
    }

    $_SESSION['reviews'][$product_id][] = [
        'name' => $name,
        'rating' => $rating,
        'title' => $title,
        'comment' => $comment,
        'date' => $date
    ];

    $success = "Thank you — your review has been added.";
}

// Helper: compute average rating
function avg_rating($reviews) {
    if (empty($reviews)) return 0;
    $sum = 0; foreach ($reviews as $r) $sum += $r['rating'];
    return round($sum / count($reviews), 1);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Product Review & Rating — Simple Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{
            --bg:#0f1724;
            --card:#0b1220;
            --glass: rgba(255,255,255,0.03);
            --muted:#94a3b8;
            --accent:#7c3aed; /* violet */
            --accent-2:#06b6d4; /* teal */
            --star:#f59e0b;
            --surface:#0b1220;
            --soft-white: rgba(255,255,255,0.96)
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap');
        *{box-sizing:border-box}
        body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial;margin:0;background:linear-gradient(180deg,#071022 0%, #0f1724 100%);color:var(--soft-white);-webkit-font-smoothing:antialiased}

        .navbar{background:transparent;border-bottom:1px solid rgba(255,255,255,0.04)}
        .navbar-brand{color:var(--accent);font-weight:800}

        main.container{padding-top:44px}

        /* Product grid */
        .product-card{border-radius:12px;overflow:hidden;background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));box-shadow:0 10px 30px rgba(2,6,23,0.65);border:1px solid rgba(255,255,255,0.03)}
        .product-image{height:220px;object-fit:cover;width:100%;display:block;filter:contrast(1.02) saturate(1.05)}
        .product-item{transition:transform .28s ease, box-shadow .28s ease}
        .product-item:hover{transform:translateY(-8px)}

        .rating-pill{background:linear-gradient(90deg, rgba(124,58,237,0.12), rgba(6,182,212,0.06));padding:6px 12px;border-radius:999px;border:1px solid rgba(124,58,237,0.12);color:var(--soft-white);font-weight:700}

        .star{color:var(--star)}
        .small-muted{color:var(--muted);font-size:.92rem}

        .review-box{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.03);padding:14px;border-radius:10px}

        /* Form card */
        .product-card .card-body{background:transparent}
        form .form-control{background:transparent;border:1px solid rgba(255,255,255,0.06);color:var(--soft-white)}
        .form-label{color:var(--soft-white)}
        .btn-primary{background:linear-gradient(90deg,var(--accent),#4f46e5);border:none}
        .btn-outline-secondary{border-color:rgba(255,255,255,0.04);color:var(--soft-white);background:transparent}

        hr{border-color:rgba(255,255,255,0.03)}

        .footer{padding:30px 0;text-align:center;color:var(--muted)}

        .filter-btn{background:transparent;color:var(--soft-white);border:1px solid rgba(255,255,255,0.03)}
        .filter-btn.active{background:linear-gradient(90deg,var(--accent),var(--accent-2));box-shadow:0 6px 20px rgba(99,102,241,0.12);border:none}

        .search-input{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);color:var(--soft-white)}

        @media(max-width:576px){.product-image{height:160px}}
    </style>
</head>
<body>
<nav class="navbar navbar-expand bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">ReviewBox</a>
        <div class="ms-auto small-muted">Static single-file demo • Bootstrap 5</div>
    </div>
</nav>

<main class="container my-5">
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success:</strong> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Products</h3>
                <div class="input-group w-50">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="searchInput" class="form-control" placeholder="Search products..." aria-label="search">
                </div>
            </div>

            <div class="row g-4" id="productsGrid">
                <?php foreach ($products as $p):
                    $rid = $p['id'];
                    $reviews = $_SESSION['reviews'][$rid] ?? [];
                    $avg = avg_rating($reviews);
                ?>
                <div class="col-md-6 product-item" data-name="<?php echo strtolower($p['name']); ?>" data-category="<?php echo strtolower($p['category']); ?>">
                    <div class="product-card p-0 overflow-hidden">
                        <img class="product-image" src="<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($p['name']); ?></h5>
                                    <div class="small-muted"><?php echo htmlspecialchars($p['category']); ?> • $<?php echo number_format($p['price'],2); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="rating-pill mb-1">
                                        <strong><?php echo $avg > 0 ? $avg : '-'; ?></strong>
                                        <small class="ms-2"><i class="bi bi-star-fill star"></i></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="openReview(<?php echo $p['id']; ?>)">Write review</button>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6 class="mb-2 small-muted">Recent reviews</h6>
                                <?php if (empty($reviews)): ?>
                                    <div class="small-muted">No reviews yet — be the first to review.</div>
                                <?php else: ?>
                                    <?php foreach (array_slice(array_reverse($reviews),0,2) as $r): ?>
                                        <div class="review-box my-2">
                                            <div class="d-flex justify-content-between">
                                                <div><strong><?php echo htmlspecialchars($r['name']); ?></strong> <span class="small-muted">• <?php echo htmlspecialchars($r['title']); ?></span></div>
                                                <div class="small-muted"><?php echo $r['date']; ?></div>
                                            </div>
                                            <div class="mt-1">
                                                <?php for($i=0;$i<5;$i++): ?>
                                                    <?php if ($i < $r['rating']): ?>
                                                        <i class="bi bi-star-fill star"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="mt-2 mb-0 small-muted"><?php echo htmlspecialchars($r['comment']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card product-card p-4">
                <h5 class="mb-3">Write a Review</h5>
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="product_id" id="formProductId" value="1">
                    <div class="mb-2">
                        <label class="form-label small mb-1">Your name</label>
                        <input class="form-control" name="name" id="nameInput" placeholder="Your name (or Anonymous)">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Review title</label>
                        <input class="form-control" name="title" id="titleInput" placeholder="Short title">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Rating</label>
                        <div id="starPicker" class="mb-2">
                            <i class="bi bi-star rating-star" data-value="1" style="font-size:1.6rem;cursor:pointer;color:#e5e7eb"></i>
                            <i class="bi bi-star rating-star" data-value="2" style="font-size:1.6rem;cursor:pointer;color:#e5e7eb"></i>
                            <i class="bi bi-star rating-star" data-value="3" style="font-size:1.6rem;cursor:pointer;color:#e5e7eb"></i>
                            <i class="bi bi-star rating-star" data-value="4" style="font-size:1.6rem;cursor:pointer;color:#e5e7eb"></i>
                            <i class="bi bi-star rating-star" data-value="5" style="font-size:1.6rem;cursor:pointer;color:#e5e7eb"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small mb-1">Comment</label>
                        <textarea class="form-control" name="comment" id="commentInput" rows="5" placeholder="Share your experience..." required></textarea>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary" name="submit_review" type="submit">Submit review</button>
                    </div>
                </form>

                <hr class="my-4">
                <h6 class="mb-2">Filter by category</h6>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <button class="btn btn-sm btn-outline-secondary filter-btn" data-cat="all">All</button>
                    <?php foreach(array_unique(array_map(fn($x)=>$x['category'],$products)) as $cat): ?>
                        <button class="btn btn-sm btn-outline-secondary filter-btn" data-cat="<?php echo strtolower($cat); ?>"><?php echo $cat; ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="small-muted">This is a static demo. Reviews are stored in your session and will reset when the session ends.</div>
            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <small>Product Review & Rating System — simple single-file demo • copy this file into your PHP server</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Basic interactions: star picker, open review, search, filter
    (function(){
        const stars = document.querySelectorAll('.rating-star');
        const ratingInput = document.getElementById('ratingInput');
        let currentRating = 5;

        function setStars(n){
            stars.forEach(s=>{
                const v = Number(s.dataset.value);
                s.classList.toggle('bi-star-fill', v<=n);
                s.classList.toggle('bi-star', v>n);
                s.style.color = v<=n ? '#f59e0b' : '#e5e7eb';
            });
            ratingInput.value = n;
            currentRating = n;
        }

        stars.forEach(s=>{
            s.addEventListener('click', ()=> setStars(Number(s.dataset.value)));
            s.addEventListener('mouseenter', ()=> setStars(Number(s.dataset.value)));
        });
        document.querySelector('#starPicker').addEventListener('mouseleave', ()=> setStars(currentRating));
        setStars(5);

        window.openReview = function(productId){
            document.getElementById('formProductId').value = productId;
            const prod = document.querySelector('.product-item[data-name]');
            // scroll to form on mobile
            document.getElementById('reviewForm').scrollIntoView({behavior:'smooth'});
        }

        // Search
        document.getElementById('searchInput').addEventListener('input', function(){
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.product-item').forEach(card=>{
                const name = card.dataset.name;
                card.style.display = name.includes(q) ? 'block' : 'none';
            });
        });

        // Filters
        document.querySelectorAll('.filter-btn').forEach(btn=>{
            btn.addEventListener('click', function(){
                const cat = this.dataset.cat;
                document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.product-item').forEach(card=>{
                    if (cat==='all') return card.style.display='block';
                    card.style.display = (card.dataset.category === cat) ? 'block' : 'none';
                });
            });
        });
    })();
</script>
</body>
</html>
