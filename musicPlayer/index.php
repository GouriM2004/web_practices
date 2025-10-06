<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Music Player – Light Theme</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #f9fafb, #e0f2fe);
      font-family: 'Poppins', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      color: #111827;
    }

    .player-container {
      background: white;
      border-radius: 25px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 30px;
      max-width: 420px;
      width: 100%;
      text-align: center;
    }

    .album-cover {
      width: 200px;
      height: 200px;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      margin-bottom: 20px;
      object-fit: cover;
      transition: transform 0.5s linear;
    }

    .rotate {
      animation: spin 7s linear infinite;
    }

 

    .song-title {
      font-size: 1.4rem;
      font-weight: 600;
      color: #1e3a8a;
    }

    .artist-name {
      font-size: 0.95rem;
      color: #475569;
      margin-bottom: 20px;
    }

    .progress-container {
      background: #e2e8f0;
      height: 6px;
      border-radius: 5px;
      margin: 20px 0;
      cursor: pointer;
    }

    .progress {
      background: linear-gradient(90deg, #2563eb, #7c3aed);
      height: 6px;
      width: 0%;
      border-radius: 5px;
      transition: width 0.3s ease;
    }

    .controls i {
      font-size: 1.8rem;
      color: #1e3a8a;
      margin: 0 15px;
      cursor: pointer;
      transition: transform 0.2s, color 0.3s;
    }

    .controls i:hover {
      transform: scale(1.2);
      color: #4338ca;
    }

    .playlist {
      margin-top: 25px;
      border-top: 1px solid #e2e8f0;
      padding-top: 15px;
      text-align: left;
    }

    .playlist-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f1f5f9;
      padding: 10px 15px;
      border-radius: 12px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .playlist-item:hover {
      background: #e0f2fe;
    }
  </style>
</head>
<body>

<div class="player-container">
  <img src="https://images.unsplash.com/photo-1511379938547-c1f69419868d" id="albumCover" class="album-cover" alt="Album Cover">
  <h5 id="songTitle" class="song-title">Sunset Breeze</h5>
  <p id="artistName" class="artist-name">by Cloud Nine</p>

  <audio id="audioPlayer" src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3"></audio>

  <div class="progress-container" onclick="setProgress(event)">
    <div class="progress" id="progress"></div>
  </div>

  <div class="controls">
    <i class="bi bi-skip-start-fill" onclick="prevSong()"></i>
    <i class="bi bi-play-fill" id="playPauseBtn" onclick="togglePlay()"></i>
    <i class="bi bi-skip-end-fill" onclick="nextSong()"></i>
  </div>

  <div class="playlist">
    <div class="playlist-item" onclick="playSong(0)">🌤 Morning Shine <span>3:25</span></div>
    <div class="playlist-item" onclick="playSong(1)">🌊 Ocean Drift <span>4:02</span></div>
    <div class="playlist-item" onclick="playSong(2)">🌇 Golden Hour <span>3:50</span></div>
  </div>
</div>

<script>
  const audio = document.getElementById('audioPlayer');
  const playPauseBtn = document.getElementById('playPauseBtn');
  const progress = document.getElementById('progress');
  const albumCover = document.getElementById('albumCover');
  const songTitle = document.getElementById('songTitle');
  const artistName = document.getElementById('artistName');

  const songs = [
    { title: "Morning Shine", artist: "Skyline Tunes", src: "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" },
    { title: "Ocean Drift", artist: "Wave Patterns", src: "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3" },
    { title: "Golden Hour", artist: "Sunset Vibes", src: "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3" }
  ];

  let songIndex = 0;

  function togglePlay() {
    if (audio.paused) {
      audio.play();
      playPauseBtn.classList.replace("bi-play-fill", "bi-pause-fill");
      albumCover.classList.add("rotate");
    } else {
      audio.pause();
      playPauseBtn.classList.replace("bi-pause-fill", "bi-play-fill");
      albumCover.classList.remove("rotate");
    }
  }

  function playSong(index) {
    songIndex = index;
    audio.src = songs[index].src;
    songTitle.textContent = songs[index].title;
    artistName.textContent = `by ${songs[index].artist}`;
    togglePlay();
  }

  function prevSong() {
    songIndex = (songIndex - 1 + songs.length) % songs.length;
    playSong(songIndex);
  }

  function nextSong() {
    songIndex = (songIndex + 1) % songs.length;
    playSong(songIndex);
  }

  audio.addEventListener("timeupdate", () => {
    const progressPercent = (audio.currentTime / audio.duration) * 100;
    progress.style.width = `${progressPercent}%`;
  });

  function setProgress(e) {
    const width = e.currentTarget.clientWidth;
    const clickX = e.offsetX;
    const duration = audio.duration;
    audio.currentTime = (clickX / width) * duration;
  }
</script>

</body>
</html>
