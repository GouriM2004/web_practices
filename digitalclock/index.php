<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Digital Clock with Date</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #e0eafc, #cfdef3);
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .clock-card {
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      background: #fff;
      padding: 2rem;
      text-align: center;
      max-width: 400px;
      width: 100%;
    }
    .time {
      font-size: 3rem;
      font-weight: 700;
      color: #2c3e50;
    }
    .date {
      font-size: 1.2rem;
      font-weight: 500;
      color: #555;
      margin-top: .5rem;
    }
  </style>
</head>
<body>
  <div class="clock-card">
    <h4 class="mb-3">🕒 Digital Clock</h4>
    <div id="time" class="time"></div>
    <div id="date" class="date"></div>
  </div>

  <script>
    function updateClock() {
      const now = new Date();
      let hours = now.getHours();
      let minutes = now.getMinutes();
      let seconds = now.getSeconds();
      let ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12;
      hours = hours < 10 ? '0' + hours : hours;
      minutes = minutes < 10 ? '0' + minutes : minutes;
      seconds = seconds < 10 ? '0' + seconds : seconds;

      const timeStr = `${hours}:${minutes}:${seconds} ${ampm}`;
      document.getElementById('time').textContent = timeStr;

      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      const dateStr = now.toLocaleDateString(undefined, options);
      document.getElementById('date').textContent = dateStr;
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>