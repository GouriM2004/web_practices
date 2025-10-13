<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🎥 Movie Search App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
     body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .main-content {
      flex: 1;
    }
    .navbar {
      background: linear-gradient(45deg, #007bff, #6610f2);
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: 600;
    }
    .search-section {
      margin-top: 60px;
      text-align: center;
    }
    .search-box input {
      width: 60%;
      border-radius: 25px;
      padding: 10px 20px;
      border: 2px solid #007bff;
      transition: 0.3s;
    }
    .search-box input:focus {
      outline: none;
      border-color: #6610f2;
      box-shadow: 0 0 10px rgba(102, 16, 242, 0.3);
    }
    .card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: none;
      border-radius: 15px;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    footer {
      background: #343a40;
      color: #ddd;
      text-align: center;
      padding: 20px 0;
      margin-top: auto;
    }
    .loading {
      text-align: center;
      color: #777;
      font-size: 18px;
      margin-top: 20px;
    }
  </style>
</head>
<body>

<div class="main-content">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#">🎬 Movie Search</a>
  </div>
</nav>

<!-- Search Section -->
<section class="search-section">
  <div class="container">
    <h1 class="fw-bold mb-3">Find Your Favorite Movies</h1>
    <p class="text-muted">Search any movie title and explore details instantly</p>

    <div class="search-box my-4">
      <input type="text" id="searchInput" class="form-control d-inline" placeholder="Enter movie name...">
      <button id="searchBtn" class="btn btn-primary ms-2">Search</button>
    </div>

    <div id="loading" class="loading d-none">Loading movies...</div>
  </div>
</section>

<!-- Movie Results -->
<div class="container my-5">
  <div class="row g-4" id="movieList"></div>
</div>
</div>

<!-- Footer -->
<footer>
  <p>© 2025 Movie Search App | Built with ❤️ using PHP, JS & Bootstrap</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const API_KEY = "7e4f7f3"; // You can get your own key from http://www.omdbapi.com/apikey.aspx
  const searchBtn = document.getElementById("searchBtn");
  const searchInput = document.getElementById("searchInput");
  const movieList = document.getElementById("movieList");
  const loading = document.getElementById("loading");

  searchBtn.addEventListener("click", fetchMovies);
  searchInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter") fetchMovies();
  });

  async function fetchMovies() {
    const query = searchInput.value.trim();
    if (!query) {
      alert("Please enter a movie name!");
      return;
    }

    movieList.innerHTML = "";
    loading.classList.remove("d-none");

    try {
      const response = await fetch(`https://www.omdbapi.com/?apikey=${API_KEY}&s=${query}`);
      const data = await response.json();

      loading.classList.add("d-none");

      if (data.Response === "True") {
        displayMovies(data.Search);
      } else {
        movieList.innerHTML = `<p class='text-center text-muted fs-5'>No movies found for "${query}" 😢</p>`;
      }
    } catch (error) {
      console.error(error);
      loading.classList.add("d-none");
      movieList.innerHTML = `<p class='text-center text-danger'>Error fetching data. Please try again later.</p>`;
    }
  }

  function displayMovies(movies) {
    movieList.innerHTML = movies.map(movie => `
      <div class="col-md-3 col-sm-6">
        <div class="card h-100">
          <img src="${movie.Poster !== "N/A" ? movie.Poster : 'https://via.placeholder.com/400x500?text=No+Image'}" 
               class="card-img-top" alt="${movie.Title}">
          <div class="card-body text-center">
            <h5 class="card-title">${movie.Title}</h5>
            <p class="text-muted">${movie.Year}</p>
            <a href="https://www.imdb.com/title/${movie.imdbID}" target="_blank" class="btn btn-outline-primary btn-sm">View on IMDb</a>
          </div>
        </div>
      </div>
    `).join("");
  }
</script>
</body>
</html>
