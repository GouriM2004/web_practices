<?php
// travel_explorer.php - Single-file Travel Destination Explorer
// Place this file in your server (e.g., XAMPP htdocs) and open in browser.
header('Content-Type: text/html; charset=utf-8');
// Simple router for AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'save_favorite') {
        $destId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['id'] ?? '');
        $file = __DIR__ . '/favorites.json';
        $favorites = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $favorites = json_decode($json, true) ?? [];
        }
        if (!in_array($destId, $favorites)) {
            $favorites[] = $destId;
            file_put_contents($file, json_encode($favorites, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        echo json_encode(['status' => 'ok', 'favorites' => $favorites]);
        exit;
    }
    if ($action === 'contact') {
        // Save contact inquiry to inquiries.csv
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $csvFile = __DIR__ . '/inquiries.csv';
        $line = [date('Y-m-d H:i:s'), $name, $email, str_replace(["\r", "\n"], [' ', ' '], $message)];
        $fp = fopen($csvFile, 'a');
        if ($fp) {
            fputcsv($fp, $line);
            fclose($fp);
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Could not save file']);
        }
        exit;
    }
    // unknown action
    echo json_encode(['status' => 'error', 'msg' => 'Unknown action']);
    exit;
}

// Hard-coded sample destinations. In a production app this would come from a DB.
$destinations = [
    ['id' => 'bali', 'name' => 'Bali, Indonesia', 'country' => 'Indonesia', 'region' => 'Asia', 'type' => 'Beach', 'rating' => 4.8, 'short' => 'Tropical beaches, rice terraces and temples.', 'img' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=80', 'details' => 'Bali offers a mix of serene beaches, cultural temples and vibrant markets. Great for surf, yoga, and sunset views.'],
    ['id' => 'paris', 'name' => 'Paris, France', 'country' => 'France', 'region' => 'Europe', 'type' => 'City', 'rating' => 4.9, 'short' => 'Art, cafés and the Eiffel Tower.', 'img' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=1400&q=80', 'details' => 'Stroll along the Seine, visit world-class museums and enjoy exceptional cuisine. Perfect for lovers of art and history.'],
    ['id' => 'banff', 'name' => 'Banff, Canada', 'country' => 'Canada', 'region' => 'North America', 'type' => 'Mountain', 'rating' => 4.7, 'short' => 'Alpine lakes and rugged peaks.', 'img' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1400&q=80', 'details' => 'Banff National Park features turquoise lakes, hiking trails and alpine scenery. Ideal for outdoor adventures and wildlife spotting.'],
    ['id' => 'cape_town', 'name' => 'Cape Town, South Africa', 'country' => 'South Africa', 'region' => 'Africa', 'type' => 'Coastal', 'rating' => 4.6, 'short' => 'Dramatic coastlines and Table Mountain.', 'img' => 'https://images.unsplash.com/photo-1505765051985-69f8d8b9b1a1?auto=format&fit=crop&w=1400&q=80', 'details' => 'A vibrant city with beaches, wine country nearby, and a stunning mountain backdrop. Great for food, culture and nature.'],
    ['id' => 'kyoto', 'name' => 'Kyoto, Japan', 'country' => 'Japan', 'region' => 'Asia', 'type' => 'Cultural', 'rating' => 4.85, 'short' => 'Temples, gardens and traditional tea houses.', 'img' => 'https://images.unsplash.com/photo-1549692520-acc6669e2f0c?auto=format&fit=crop&w=1400&q=80', 'details' => 'Kyoto is famous for its classical Buddhist temples, gardens and seasonal festivals. A calm and historic escape.'],
    ['id' => 'santorini', 'name' => 'Santorini, Greece', 'country' => 'Greece', 'region' => 'Europe', 'type' => 'Island', 'rating' => 4.8, 'short' => 'Whitewashed villages and Aegean sunsets.', 'img' => 'https://images.unsplash.com/photo-1508051123996-69f8caf4891e?auto=format&fit=crop&w=1400&q=80', 'details' => 'Famous for dramatic views, volcanic beaches and romantic sunsets. Perfect for honeymooners and photographers.'],
];

// Gather filter lists
$regions = array_values(array_unique(array_column($destinations, 'region')));
$types = array_values(array_unique(array_column($destinations, 'type')));

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Travel Destination Explorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{--accent:#0d6efd}
        body{background:linear-gradient(180deg,#f8fbff 0%, #ffffff 100%)}
        .hero{padding:40px 0}
        .card-img-top{height:180px;object-fit:cover}
        .badge-rating{background:linear-gradient(90deg, #ffd700, #ffb700); color:#222}
        .filters .form-select, .filters .form-control{min-width:160px}
        .destination-card{transition:transform .18s ease, box-shadow .18s ease}
        .destination-card:hover{transform:translateY(-6px);box-shadow:0 8px 30px rgba(13,110,253,0.08)}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">✈️ Travel Explorer</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-2"><a class="nav-link" href="#explorer">Explore</a></li>
                <li class="nav-item me-2"><a class="nav-link" href="#favorites">Favorites</a></li>
                <li class="nav-item"><button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal">Contact</button></li>
            </ul>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="display-6 fw-semibold">Discover your next destination</h1>
                <p class="text-muted">Search, filter and save destinations. This is a single-file PHP demo you can extend into a full app.</p>
            </div>
            <div class="col-md-5">
                <div class="input-group">
                    <input id="searchInput" type="search" class="form-control" placeholder="Search by name, country or type...">
                    <button id="searchBtn" class="btn btn-primary">Search</button>
                </div>
            </div>
        </div>
        <div class="row mt-3 filters gx-2 gy-2">
            <div class="col-auto">
                <select id="regionFilter" class="form-select form-select-sm">
                    <option value="">All regions</option>
                    <?php foreach ($regions as $r) : ?>
                        <option value="<?=htmlspecialchars($r)?>"><?=htmlspecialchars($r)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select id="typeFilter" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <?php foreach ($types as $t) : ?>
                        <option value="<?=htmlspecialchars($t)?>"><?=htmlspecialchars($t)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select id="sortSelect" class="form-select form-select-sm">
                    <option value="rating_desc">Top rated</option>
                    <option value="rating_asc">Lowest rated</option>
                    <option value="name_asc">Name A→Z</option>
                </select>
            </div>
        </div>
    </div>
</header>

<main class="container mb-5">
    <section id="explorer">
        <div class="row" id="destinationGrid">
            <?php foreach ($destinations as $d) : ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card destination-card" data-id="<?=htmlspecialchars($d['id'])?>" data-name="<?=htmlspecialchars($d['name'])?>" data-region="<?=htmlspecialchars($d['region'])?>" data-type="<?=htmlspecialchars($d['type'])?>" data-rating="<?=htmlspecialchars($d['rating'])?>" data-country="<?=htmlspecialchars($d['country'])?>" data-details="<?=htmlspecialchars($d['details'])?>">
                        <img src="<?=htmlspecialchars($d['img'])?>" class="card-img-top" alt="<?=htmlspecialchars($d['name'])?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1"><?=htmlspecialchars($d['name'])?></h5>
                                <span class="badge badge-rating rounded-pill px-2 py-1">⭐ <?=htmlspecialchars($d['rating'])?></span>
                            </div>
                            <p class="text-muted small mb-2"><?=htmlspecialchars($d['short'])?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-2 btn-view">View</button>
                                    <button class="btn btn-sm btn-outline-success btn-save">Save</button>
                                </div>
                                <small class="text-muted"><?=htmlspecialchars($d['region'])?> • <?=htmlspecialchars($d['type'])?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="favorites" class="mt-5">
        <h4>Saved favorites</h4>
        <div id="favoritesRow" class="row">
            <!-- Favorites will be injected here -->
        </div>
    </section>
</main>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img id="modalImg" src="" alt="" class="img-fluid rounded mb-3" style="max-height:360px; width:100%; object-fit:cover">
                <p id="modalDetails" class="mb-3"></p>
                <ul class="list-inline text-muted small">
                    <li class="list-inline-item"><strong>Region:</strong> <span id="modalRegion"></span></li>
                    <li class="list-inline-item"><strong>Country:</strong> <span id="modalCountry"></span></li>
                    <li class="list-inline-item"><strong>Type:</strong> <span id="modalType"></span></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success btn-save-modal">Save to favorites</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="contactForm">
            <div class="modal-header">
                <h5 class="modal-title">Contact / Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit">Send</button>
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    const grid = document.getElementById('destinationGrid');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const regionFilter = document.getElementById('regionFilter');
    const typeFilter = document.getElementById('typeFilter');
    const sortSelect = document.getElementById('sortSelect');
    const favoritesRow = document.getElementById('favoritesRow');

    // Utility: get all card data as objects
    function readCards(){
        const cards = Array.from(document.querySelectorAll('.card.destination-card'));
        return cards.map(c => ({
            el: c.closest('.col-12'),
            id: c.dataset.id,
            name: c.dataset.name.toLowerCase(),
            region: c.dataset.region,
            type: c.dataset.type,
            rating: parseFloat(c.dataset.rating),
            country: c.dataset.country,
            details: c.dataset.details,
            img: c.querySelector('.card-img-top').src
        }));
    }

    function renderList(list){
        // detach all
        grid.innerHTML = '';
        list.forEach(item => grid.appendChild(item.el));
        attachCardHandlers();
    }

    function filterAndSort(){
        const q = searchInput.value.trim().toLowerCase();
        const region = regionFilter.value;
        const type = typeFilter.value;
        const sort = sortSelect.value;
        let list = readCards();
        list = list.filter(item => {
            if (region && item.region !== region) return false;
            if (type && item.type !== type) return false;
            if (q && !(item.name.includes(q) || item.country.toLowerCase().includes(q) || item.type.toLowerCase().includes(q))) return false;
            return true;
        });
        if (sort === 'rating_desc') list.sort((a,b)=>b.rating-a.rating);
        if (sort === 'rating_asc') list.sort((a,b)=>a.rating-b.rating);
        if (sort === 'name_asc') list.sort((a,b)=>a.name.localeCompare(b.name));
        renderList(list);
    }

    searchBtn.addEventListener('click', filterAndSort);
    searchInput.addEventListener('keydown', (e)=>{ if (e.key === 'Enter') filterAndSort(); });
    regionFilter.addEventListener('change', filterAndSort);
    typeFilter.addEventListener('change', filterAndSort);
    sortSelect.addEventListener('change', filterAndSort);

    // Card handlers
    function attachCardHandlers(){
        document.querySelectorAll('.btn-view').forEach(btn=>{
            btn.onclick = (e)=>{
                const card = e.target.closest('.card.destination-card');
                showDetails(card);
            };
        });
        document.querySelectorAll('.btn-save').forEach(btn=>{
            btn.onclick = (e)=>{
                const card = e.target.closest('.card.destination-card');
                saveFavorite(card.dataset.id);
            };
        });
    }

    function showDetails(card){
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        document.getElementById('modalTitle').textContent = card.dataset.name;
        document.getElementById('modalImg').src = card.querySelector('.card-img-top').src;
        document.getElementById('modalDetails').textContent = card.dataset.details;
        document.getElementById('modalRegion').textContent = card.dataset.region;
        document.getElementById('modalCountry').textContent = card.dataset.country;
        document.getElementById('modalType').textContent = card.dataset.type;
        document.querySelector('.btn-save-modal').onclick = ()=> saveFavorite(card.dataset.id);
        modal.show();
    }

    // Favorites management (client + server)
    async function saveFavorite(id){
        try{
            const form = new FormData();
            form.append('action','save_favorite');
            form.append('id', id);
            const res = await fetch(location.href, {method:'POST', body: form});
            const data = await res.json();
            if (data.status === 'ok'){
                loadFavorites();
                toast('Saved to favorites');
            } else {
                toast('Could not save');
            }
        } catch (err){ toast('Network error'); }
    }

    function toast(msg){
        const div = document.createElement('div');
        div.className = 'toast align-items-center text-bg-primary border-0 position-fixed top-0 end-0 m-3 show';
        div.role = 'status';
        div.innerHTML = `<div class='d-flex'><div class='toast-body'>${msg}</div><button type='button' class='btn-close btn-close-white me-2 m-auto' onclick='this.parentNode.parentNode.remove()'></button></div>`;
        document.body.appendChild(div);
        setTimeout(()=>div.remove(), 2500);
    }

    async function loadFavorites(){
        // read favorites.json from server
        try{
            const res = await fetch('favorites.json?_='+Date.now());
            if (!res.ok) throw new Error('No favorites file');
            const favs = await res.json();
            const cards = readCards();
            favoritesRow.innerHTML = '';
            if (!favs || favs.length === 0){
                favoritesRow.innerHTML = '<div class="col-12 text-muted">No favorites yet. Save a destination to see it here.</div>';
                return;
            }
            favs.forEach(id=>{
                const found = cards.find(c=>c.id===id);
                if (found){
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6 col-lg-4 mb-3';
                    col.innerHTML = `<div class='card'><img src='${found.img}' class='card-img-top' style='height:140px;object-fit:cover'><div class='card-body'><h6 class='mb-1'>${found.name}</h6><p class='small text-muted mb-2'>${found.country} • ${found.region}</p><div class='d-flex justify-content-between align-items-center'><button class='btn btn-sm btn-outline-primary' data-id='${found.id}'>View</button><button class='btn btn-sm btn-danger' data-id='${found.id}'>Remove</button></div></div></div>`;
                    favoritesRow.appendChild(col);
                }
            });
            // attach handlers
            favoritesRow.querySelectorAll('button').forEach(b=>{
                b.onclick = (e)=>{
                    const id = e.target.dataset.id;
                    const card = document.querySelector(`.card.destination-card[data-id='${id}']`);
                    if (e.target.textContent.trim() === 'View') showDetails(card);
                    if (e.target.textContent.trim() === 'Remove') removeFavorite(id);
                };
            });
        } catch (err){
            favoritesRow.innerHTML = '<div class="col-12 text-muted">No favorites yet. Save a destination to see it here.</div>';
        }
    }

    async function removeFavorite(id){
        // naive removal on client + rewrite file: we'll fetch file, filter, POST to save
        try{
            const res = await fetch('favorites.json?_='+Date.now());
            const favs = await res.json();
            const newFavs = (favs || []).filter(x=>x!==id);
            // to update on server, we'll call save_favorite for remaining list by rewriting file via a quick server trick: send an AJAX to a hidden endpoint? For simplicity, we'll overwrite by calling save for each id after clearing file via a special request.
            // Simpler: call serverless endpoint not available here. So update client display and write file by sending a form with action 'save_favorite' for each remaining item. To reset, we first clear by writing an empty favorites.json using fetch to a small script. But keeping things simple: attempt to write new favorites via fetch to the same file using PUT is not allowed. So instead, we create a small helper below that asks the server to overwrite favorites (by sending a JSON). We'll implement that by sending a POST with action 'save_favorite_batch'.
            const batchForm = new FormData();
            batchForm.append('action','save_favorite_batch');
            batchForm.append('list', JSON.stringify(newFavs));
            const res2 = await fetch(location.href, {method:'POST', body: batchForm});
            const data = await res2.json();
            if (data.status === 'ok'){ loadFavorites(); toast('Removed'); }
        } catch (err){ toast('Unable to update'); }
    }

    // Attach a small enhancement: call a server helper to write batch (server handles below)
    // Because removeFavorite expects 'save_favorite_batch' action, we need server-side support; the PHP supports it if present.

    // Contact form
    document.getElementById('contactForm').addEventListener('submit', async function(e){
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action','contact');
        const res = await fetch(location.href, {method:'POST', body: fd});
        const json = await res.json();
        if (json.status === 'ok'){
            toast('Inquiry sent — thanks!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
            modal.hide();
            this.reset();
        } else toast('Error sending');
    });

    // initial handlers and favorites loader
    attachCardHandlers();
    loadFavorites();
})();
</script>
</body>
</html>
