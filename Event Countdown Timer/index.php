<?php
// Example event date (customize this as needed)
$eventDate = "2025-12-31 23:59:59";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Event Countdown Timer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #dfe9f3, #ffffff);
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .countdown-card {
      background: #fff;
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      text-align: center;
      max-width: 500px;
      width: 100%;
    }
    .countdown-title {
      font-weight: 600;
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      color: #2c3e50;
    }
    .timer {
      display: flex;
      justify-content: space-around;
      gap: 1rem;
    }
    .time-box {
      background: #f8f9fa;
      border-radius: 15px;
      padding: 1rem;
      flex: 1;
      box-shadow: inset 0 4px 10px rgba(0,0,0,0.05);
    }
    .time-box h2 {
      font-size: 2rem;
      font-weight: 700;
      margin: 0;
      color: #2c3e50;
    }
    .time-box p {
      font-size: 0.9rem;
      color: #555;
      margin: 0;
    }
  </style>
</head>
<body>
  <div class="countdown-card">
    <div class="countdown-title">⏳ Countdown to Event</div>
    <div class="timer">
      <div class="time-box">
        <h2 id="days">0</h2>
        <p>Days</p>
      </div>
      <div class="time-box">
        <h2 id="hours">0</h2>
        <p>Hours</p>
      </div>
      <div class="time-box">
        <h2 id="minutes">0</h2>
        <p>Minutes</p>
      </div>
      <div class="time-box">
        <h2 id="seconds">0</h2>
        <p>Seconds</p>
      </div>
    </div>
  </div>

  <script>
    const eventDate = new Date("<?php echo $eventDate; ?>").getTime();

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = eventDate - now;

      if (distance < 0) {
        document.querySelector('.countdown-title').textContent = "🎉 Event Started!";
        document.querySelector('.timer').style.display = "none";
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      document.getElementById('days').textContent = days;
      document.getElementById('hours').textContent = hours;
      document.getElementById('minutes').textContent = minutes;
      document.getElementById('seconds').textContent = seconds;
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>