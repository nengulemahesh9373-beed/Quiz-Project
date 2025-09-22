<?php
// DB connection
$conn = new mysqli("your-rds-endpoint","db_user","db_pass","mahesh_quiz");
if($conn->connect_error){ die("DB Connection failed"); }

// fetch 20 random questions
$questions = $conn->query("SELECT * FROM questions ORDER BY RAND() LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// if submitted
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    $correct=0;
    $total=count($questions);

    foreach($_POST['answer'] as $qid=>$ans){
        $q=$conn->query("SELECT correct_option FROM questions WHERE id=$qid")->fetch_assoc();
        if($q['correct_option']==$ans) $correct++;
    }
    $incorrect = $total - $correct;
    $percentage = round(($correct/$total)*100,2);

    // store result
    $stmt=$conn->prepare("INSERT INTO results(student_id,student_name,correct_answers,total_questions,percentage) VALUES(?,?,?,?,?)");
    $stmt->bind_param("ssiii",$student_id,$student_name,$correct,$total,$percentage);
    $stmt->execute();

    // show results page
    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
      <title>Mahesh Quiz Result</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
      <style>
        body {background: linear-gradient(135deg,#74ebd5,#ACB6E5); font-family:'Poppins',sans-serif;}
        .result-container {background:#fff; padding:30px; border-radius:15px; box-shadow:0 8px 20px rgba(0,0,0,0.3);}
      </style>
    </head>
    <body>
      <div class="container my-5">
        <div class="result-container text-center animate__animated animate__fadeInDown">
          <h1 class="text-success mb-3">🎉 Quiz Completed!</h1>
          <h4>Student ID: $student_id</h4>
          <h4>Name: $student_name</h4>
          <hr>
          <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
              <tr>
                <th>Correct Answers</th>
                <th>Incorrect Answers</th>
                <th>Marks Obtained</th>
                <th>Percentage</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>$correct</td>
                <td>$incorrect</td>
                <td>$correct / $total</td>
                <td>$percentage%</td>
              </tr>
            </tbody>
          </table>
HTML;

    if($percentage>=80){
        echo "<h3 class='mt-4 text-primary animate__animated animate__bounceIn'>Excellent Work! 🌟</h3>";
    } elseif($percentage>=50){
        echo "<h3 class='mt-4 text-warning animate__animated animate__fadeInUp'>Good Effort! 🙂</h3>";
    } else{
        echo "<h3 class='mt-4 text-danger animate__animated animate__shakeX'>Better Luck Next Time! 💪</h3>";
    }

    echo <<<HTML
        <div class="progress mt-4" style="height:25px; border-radius:12px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: $percentage%;" aria-valuenow="$percentage" aria-valuemin="0" aria-valuemax="100">$percentage%</div>
        </div>
        </div>
      </div>
    </body>
    </html>
HTML;
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Mahesh Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    body {background: linear-gradient(135deg,#ffecd2,#fcb69f); font-family:'Poppins',sans-serif;}
    .quiz-title {font-weight: bold; color:#ff5722; font-size:2.5rem; text-align:center; margin-bottom:20px; animation: bounce 2s infinite;}
    .question-box {background:#fff; padding:20px; margin-bottom:15px; border-radius:15px; box-shadow:0 6px 20px rgba(0,0,0,0.2); transition: transform 0.3s, box-shadow 0.3s;}
    .question-box:hover {transform: scale(1.03); box-shadow:0 12px 30px rgba(0,0,0,0.3);}
    .question-number {font-weight:bold; color:#ff5722; margin-bottom:10px;}
    .form-check-input:checked + .form-check-label {color:#007bff; font-weight:bold;}
    .btn-submit {background: linear-gradient(90deg,#ff8a00,#e52e71); border:none; font-size:1.2rem;}
    .btn-submit:hover {opacity:0.9;}
    #timer {font-size:1.3rem; font-weight:bold; color:#ff3d00;}
  </style>
  <script>
    let timeLeft = 20*60; // 20 minutes in seconds
    function startTimer() {
      const timerEl = document.getElementById('timer');
      const interval = setInterval(()=>{
        let minutes = Math.floor(timeLeft/60);
        let seconds = timeLeft % 60;
        timerEl.textContent = `Time Left: ${minutes}m ${seconds}s`;
        timeLeft--;
        if(timeLeft<0){
          clearInterval(interval);
          alert('Time is up! Submitting your quiz.');
          document.getElementById('quizForm').submit();
        }
      },1000);
    }
    document.addEventListener('DOMContentLoaded', startTimer);

    // auto submit on tab change
    document.addEventListener('visibilitychange',()=>{
      if(document.hidden){
        document.getElementById('quizForm').submit();
      }
    });
  </script>
</head>
<body>
<div class="container my-5">
  <h2 class="quiz-title animate__animated animate__rubberBand">Mahesh Quiz</h2>
  <p class="text-center text-muted mb-2 animate__animated animate__fadeIn">Answer all questions. Switching tabs will auto-submit the quiz!</p>
  <p class="text-center mb-4" id="timer"></p>
  <form method="POST" id="quizForm">
    <div class="mb-3">
      <label class="form-label">Student ID (must be correct)</label>
      <input type="text" name="student_id" class="form-control" required>
    </div>
    <div class="mb-4">
      <label class="form-label">Name (optional)</label>
      <input type="text" name="student_name" class="form-control">
    </div>

    <?php foreach($questions as $idx=>$q): ?>
      <div class="question-box animate__animated animate__fadeInUp">
        <p class="question-number">Q<?=($idx+1)?>. <?=$q['question']?></p>
        <?php foreach(['A','B','C','D'] as $opt): ?>
          <div class="form-check mb-1">
            <input class="form-check-input" type="radio" name="answer[<?=$q['id']?>]" value="<?=$opt?>" required>
            <label class="form-check-label"><?=$opt?>. <?=$q['option_'.strtolower($opt)]?></label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>

    <button class="btn btn-submit btn-lg mt-3 w-100 animate__animated animate__pulse" type="submit">Submit Quiz</button>
  </form>
</div>
</body>
</html>
