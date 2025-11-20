<?php
session_start();

// Simple in-memory product list (static)
$products = [
    ['id' => 1, 'name' => 'Aurora Wireless Headphones', 'category' => 'Electronics', 'price' => 79.99, 'image' => 'https://images.unsplash.com/photo-1518444027188-1b4e420c9f29?w=1200&h=800&fit=crop'],
    ['id' => 2, 'name' => 'Cedarwood Scented Candle', 'category' => 'Home', 'price' => 19.50, 'image' => 'https://images.unsplash.com/photo-1514144287417-5f2f3b2f4b47?w=1200&h=800&fit=crop'],
    ['id' => 3, 'name' => 'Velvet Throw Blanket', 'category' => 'Home', 'price' => 45.00, 'image' => 'https://images.unsplash.com/photo-1549187774-b4f9b7f3c1e5?w=1200&h=800&fit=crop'],
    ['id' => 4, 'name' => 'Nimbus Smartwatch', 'category' => 'Wearables', 'price' => 199.00, 'image' => 'https://images.unsplash.com/photo-1516574187841-cb9cc2ca948b?w=1200&h=800&fit=crop']
];

// Initialize reviews in session (demo)
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

function rating_distribution($reviews) {
    $dist = [5=>0,4=>0,3=>0,2=>0,1=>0];
    $total = count($reviews);
    if ($total === 0) return array_merge($dist, ['total'=>0]);
    foreach ($reviews as $r) $dist[$r['rating']]++;
    foreach ($dist as $k=>&$v) { $v = round($v / $total * 100); }
    return array_merge($dist, ['total'=>$total]);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ReviewBox — Product Reviews & Ratings</title>

    <!-- Bootstrap & icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --bg: #0b1020;
            --card: #0f1724;
            --muted: #9aa8c3;
            --accent: linear-gradient(90deg,#7c3aed,#06b6d4);
            --star: #f59e0b;
        }
        *{box-sizing:border-box}
        body{font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; margin:0; background: radial-gradient(1200px 600px at 10% 10%, rgba(124,58,237,0.08), transparent), linear-gradient(180deg,#071022 0%, #0b1020 100%); color:#edf2f7}
        .navbar{backdrop-filter:blur(6px); background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.04)}
        .brand { font-weight:800; letter-spacing:0.2px; color:transparent; background:linear-gradient(90deg,#a78bfa,#06b6d4); -webkit-background-clip:text; background-clip:text }

        /* Cards */
        .product-card{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border:1px solid rgba(255,255,255,0.03); border-radius:14px; overflow:hidden}
        .product-image{height:220px; width:100%; object-fit:cover; transition:transform .45s ease}
        .product-item:hover .product-image{transform:scale(1.03)}
        .rating-badge{background:linear-gradient(90deg,#1f2937, rgba(255,255,255,0.02)); padding:6px 10px; border-radius:999px; font-weight:700}

        .review-box{background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.005)); border:1px solid rgba(255,255,255,0.03); padding:12px; border-radius:10px}
        .small-muted{color:var(--muted)}

        /* Star picker */
        .rating-star{font-size:1.6rem; cursor:pointer; transition:transform .12s ease}
        .rating-star:hover{transform:scale(1.12)}

        /* Filters */
        .chip{background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.04); color:inherit; padding:.45rem .7rem; border-radius:999px}
        .chip.active{background:linear-gradient(90deg,#7c3aed,#06b6d4); color:#071022}

        /* Modal product hero */
        .modal-hero{height:220px; object-fit:cover; border-radius:8px}

        footer{color:var(--muted); padding:32px 0; text-align:center}

        @media(max-width:576px){.product-image{height:160px}}    
    </style>
</head>
<body>
<nav class="navbar navbar-expand">
    <div class="container">
        <a class="navbar-brand brand" href="#">ReviewBox</a>
        <div class="ms-auto small-muted">Demo • Reviews stored in PHP session</div>
    </div>
</nav>

<main class="container my-5">
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><strong>Success:</strong> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex gap-3 align-items-center mb-3">
                <h3 class="mb-0">Products</h3>
                <div class="ms-auto d-flex gap-2 w-100 align-items-center">
                    <div class="input-group me-2">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input id="searchInput" class="form-control" placeholder="Search products..." aria-label="search">
                    </div>
                    <div class="d-none d-md-block small-muted">Filter:</div>
                    <div class="d-flex gap-2">
                        <button class="chip" data-cat="all">All</button>
                        <?php foreach(array_unique(array_map(fn($x)=>$x['category'],$products)) as $cat): ?>
                            <button class="chip" data-cat="<?php echo strtolower($cat); ?>"><?php echo $cat; ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4" id="productsGrid">
                <?php foreach ($products as $p):
                    $rid = $p['id'];
                    $reviews = $_SESSION['reviews'][$rid] ?? [];
                    $avg = avg_rating($reviews);
                    $dist = rating_distribution($reviews);
                ?>
                <div class="col-md-6 product-item" data-name="<?php echo strtolower($p['name']); ?>" data-category="<?php echo strtolower($p['category']); ?>">
                    <div class="product-card p-0 overflow-hidden shadow-sm">
                        <img class="product-image" src="<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($p['name']); ?></h5>
                                    <div class="small-muted"><?php echo htmlspecialchars($p['category']); ?> • $<?php echo number_format($p['price'],2); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="rating-badge mb-2">
                                        <strong><?php echo $avg > 0 ? $avg : '-'; ?></strong>
                                        <small class="ms-2"><i class="bi bi-star-fill" style="color:var(--star)"></i></small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-light" onclick="openReview(<?php echo $p['id']; ?>)"><i class="bi bi-pencil-fill me-1"></i> Write review</button>
                                        <button class="btn btn-sm btn-primary" onclick="openProduct(<?php echo $p['id']; ?>)"><i class="bi bi-eye-fill me-1"></i> View</button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6 class="mb-2 small-muted">Top reviews</h6>
                                <?php if (empty($reviews)): ?>
                                    <div class="small-muted">No reviews yet — be the first to review.</div>
                                <?php else: ?>
                                    <?php foreach (array_slice(array_reverse($reviews),0,2) as $r): ?>
                                        <div class="review-box my-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($r['name']); ?></strong> <div class="small-muted">&middot; <?php echo htmlspecialchars($r['title']); ?></div>
                                                </div>
                                                <div class="small-muted text-end"><?php echo $r['date']; ?><br>
                                                    <?php for($i=0;$i<5;$i++): ?>
                                                        <?php if ($i < $r['rating']): ?>
                                                            <i class="bi bi-star-fill" style="color:var(--star)"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
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
            <div class="card product-card p-4 shadow-sm">
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
                            <i class="bi bi-star rating-star" data-value="1"></i>
                            <i class="bi bi-star rating-star" data-value="2"></i>
                            <i class="bi bi-star rating-star" data-value="3"></i>
                            <i class="bi bi-star rating-star" data-value="4"></i>
                            <i class="bi bi-star rating-star" data-value="5"></i>
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
                <h6 class="mb-2">Quick stats</h6>
                <div class="mb-3 small-muted">This is a static demo — reviews are stored in your session and will reset when the session ends.</div>

                <div class="mt-2">
                    <?php
                    // aggregate overall stats for demo (all products combined)
                    $all = array_reduce($_SESSION['reviews'], fn($carry, $item) => array_merge($carry, $item), []);
                    $overall_avg = avg_rating($all);
                    $overall_total = count($all);
                    ?>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div>
                            <div class="h2 mb-0"><?php echo $overall_avg > 0 ? $overall_avg : '-'; ?></div>
                            <div class="small-muted">Average rating</div>
                        </div>
                        <div class="vr"></div>
                        <div>
                            <div class="h4 mb-0"><?php echo $overall_total; ?></div>
                            <div class="small-muted">Total reviews</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- Product modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0">
                <div class="product-card p-4">
                    <div id="modalContent">
                        <!-- Filled by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <small>Product Review & Rating System — modern demo • copy this file into your PHP server</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
            document.getElementById('reviewForm').scrollIntoView({behavior:'smooth'});
        }

        // Build product modal
        const products = <?php echo json_encode($products, JSON_HEX_TAG); ?>;
        const reviews = <?php echo json_encode($_SESSION['reviews'], JSON_HEX_TAG); ?>;

        window.openProduct = function(id){
            const p = products.find(x=>x.id===id);
            const rlist = reviews[id] || [];
            const avg = rlist.length ? (rlist.reduce((a,b)=>a+b.rating,0)/rlist.length).toFixed(1) : '-';

            const html = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <img src="${p.image}" class="modal-hero w-100" alt="${p.name}">
                    </div>
                    <div class="col-md-6">
                        <h4>${p.name}</h4>
                        <div class="small-muted mb-2">${p.category} • $${p.price.toFixed(2)}</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rating-badge"><strong>${avg}</strong> <i class="bi bi-star-fill ms-2" style="color:var(--star)"></i></div>
                            <button class="btn btn-sm btn-primary" onclick="openReview(${p.id})">Write review</button>
                        </div>
                        <hr>
                        <h6 class="mb-2">All reviews</h6>
                        ${rlist.length===0 ? '<div class="small-muted">No reviews yet.</div>' : ''}
                        ${rlist.map(r=>`
                            <div class="review-box my-2">
                                <div class="d-flex justify-content-between">
                                    <div><strong>${r.name}</strong> <div class="small-muted">• ${r.title}</div></div>
                                    <div class="small-muted">${r.date}</div>
                                </div>
                                <div class="mt-1">${Array.from({length:5}).map((_,i)=> i<r.rating ? '<i class="bi bi-star-fill" style="color:var(--star)"></i>' : '<i class="bi bi-star"></i>').join('')}</div>
                                <p class="mt-2 mb-0 small-muted">${r.comment}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            document.getElementById('modalContent').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }

        // Search
        document.getElementById('searchInput').addEventListener('input', function(){
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.product-item').forEach(card=>{
                const name = card.dataset.name;
                card.style.display = name.includes(q) ? 'block' : 'none';
            });
        });

        // Filters (chips)
        document.querySelectorAll('.chip').forEach(btn=>{
            btn.addEventListener('click', function(){
                const cat = this.dataset.cat;
                document.querySelectorAll('.chip').forEach(b=>b.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.product-item').forEach(card=>{
                    if (cat==='all') return card.style.display='block';
                    card.style.display = (card.dataset.category === cat) ? 'block' : 'none';
                });
            });
        });
        // activate first chip
        document.querySelector('.chip[data-cat="all"]').classList.add('active');

    })();
</script>
</body>
</html>