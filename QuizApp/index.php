<?php
session_start();

if (!isset($_SESSION['score'])) {
  $_SESSION['score'] = 0;
}
if (!isset($_SESSION['answers'])) {
  $_SESSION['answers'] = [];
}

$questions = [
  [
    'question' => 'What does HTML stand for?',
    'options' => ['Hyper Trainer Marking Language', 'Hyper Text Markup Language', 'Hyper Text Marketing Language', 'Hyperlinking Text Marking Language'],
    'answer' => 1
  ],
  [
    'question' => 'Which language is used for styling web pages?',
    'options' => ['HTML', 'JQuery', 'CSS', 'XML'],
    'answer' => 2
  ],
  [
    'question' => 'Which is not a JavaScript framework?',
    'options' => ['React', 'Angular', 'Vue', 'Django'],
    'answer' => 3
  ],
  [
    'question' => 'Which symbol is used for comments in PHP?',
    'options' => ['//', '<!-- -->', '#', '/* */'],
    'answer' => 0
  ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['questionIndex'])) {
  $qIndex = (int)$_POST['questionIndex'];
  $selected = (int)$_POST['selected'];
  $_SESSION['answers'][$qIndex] = $selected;
  if ($selected === $questions[$qIndex]['answer']) {
    $_SESSION['score']++;
  }
}

if (isset($_GET['reset']) && $_GET['reset'] == 1) {
  $_SESSION['score'] = 0;
  $_SESSION['answers'] = [];
  header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quiz App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #a5b4fc 0%, #f0f9ff 100%);
      font-family: 'Poppins', sans-serif;
      padding: 2rem;
    }
    .quiz-card {
      max-width: 850px;
      margin: auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      overflow: hidden;
      animation: fadeIn 0.6s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .quiz-header {
      background: linear-gradient(90deg,#4f46e5,#3b82f6);
      color: #fff;
      padding: 1.5rem;
    }
    .quiz-question {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: #1e293b;
    }
    .option-btn {
      border-radius: 12px;
      padding: 0.8rem 1rem;
      text-align: left;
      width: 100%;
      margin-bottom: 0.6rem;
      border: 1px solid #e2e8f0;
      transition: all 0.25s ease;
      font-weight: 500;
    }
    .option-btn:hover {
      background: #e0e7ff;
      transform: scale(1.02);
    }
    .btn-success.text-white {
      background: #16a34a !important;
      border: none;
    }
    .btn-danger.text-white {
      background: #dc2626 !important;
      border: none;
    }
    .score-box {
      background: linear-gradient(135deg,#f9fafb,#eef2ff);
      border-radius: 14px;
      padding: 1.2rem;
      margin-top: 1.5rem;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .score-box h5 {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .restart-btn {
      border-radius: 10px;
      padding: 0.6rem 1.2rem;
      font-weight: 500;
      transition: 0.2s;
    }
    .restart-btn:hover {
      background: #4338ca;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="quiz-card">
    <div class="quiz-header text-center">
      <h2 class="mb-0">✨ Quiz App ✨</h2>
      <small>Test your knowledge in style</small>
    </div>
    <div class="p-4">
      <?php foreach ($questions as $index => $q): ?>
        <div class="mb-4">
          <div class="quiz-question"><?php echo ($index+1).'. '.$q['question']; ?></div>
          <?php foreach ($q['options'] as $optIndex => $option): ?>
            <form method="post" class="mb-1">
              <input type="hidden" name="questionIndex" value="<?php echo $index; ?>">
              <input type="hidden" name="selected" value="<?php echo $optIndex; ?>">
              <button type="submit" class="btn option-btn <?php
                if (isset($_SESSION['answers'][$index])) {
                  if ($optIndex == $q['answer']) echo 'btn-success text-white';
                  elseif ($optIndex == $_SESSION['answers'][$index]) echo 'btn-danger text-white';
                }
              ?>">
                <?php echo htmlspecialchars($option); ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="score-box">
        <h5>Your Score: <?php echo $_SESSION['score']; ?> / <?php echo count($questions); ?></h5>
        <a href="?reset=1" class="btn btn-outline-primary restart-btn mt-2">Restart Quiz</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>