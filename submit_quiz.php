<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';

// දත්ත ඇවිත් තියෙනවාද බලන්න (POST හෝ GET)
$is_auto = (isset($_GET['auto']) || isset($_POST['auto_submitted']));

// Attempt එක ලිවීම (මෙය අනිවාර්යයෙන්ම සිදුවිය යුතුයි)
$user_id = $_SESSION['student_data']['student_id'] ?? $_SESSION['user_id'] ?? 1;
$material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;

if($material_id > 0) {
    $stmt = $conn->prepare("INSERT INTO student_attempts (user_id, material_id, attempted_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $user_id, $material_id);
    $stmt->execute();
}

// දත්ත සේව් වුණාද බලන්න debug file එකකින්
file_put_contents('debug.txt', "Attempt recorded for User: $user_id at " . date('H:i:s') . "\n", FILE_APPEND);

// LMS එකේ ඇති Session එකටම ගැළපෙන පරිදි සකස් කළා
if(!isset($_SESSION['student_data']) && !isset($_SESSION['user_id'])) {
    die("❌ Unauthorized Access!");
}

$user_id = isset($_SESSION['student_data']['student_id']) ? $_SESSION['student_data']['student_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1);
$material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;

if($material_id === 0) {
    die("❌ Invalid Quiz Submission.");
}

// -------------------------------------------------------------------------
// 🎯 1. වැදගත්ම කොටස: ශිෂ්‍යයා ආපු සැනින් ATTEMPT එක DATABASE එකට සේව් කිරීම
// -------------------------------------------------------------------------
$insert_attempt = $conn->prepare("INSERT INTO student_attempts (user_id, material_id, attempted_at) VALUES (?, ?, NOW())");
$insert_attempt->bind_param("si", $user_id, $material_id); // 's' භාවිතා කළා VARCHAR සඳහා
$insert_attempt->execute();

// -------------------------------------------------------------------------
// 2. ලකුණු හැදීමේ කොටස
// -------------------------------------------------------------------------
$total_questions = 0;
$correct_answers = 0;

if (isset($_POST['answers']) && is_array($_POST['answers'])) {
    foreach ($_POST['answers'] as $q_id => $data) {
        $question_id = intval($q_id);
        $selected_option = isset($data['selected']) ? mysqli_real_escape_string($conn, $data['selected']) : '';

        $q_query = $conn->prepare("SELECT correct_option FROM quiz_questions WHERE id = ?");
        $q_query->bind_param("i", $question_id);
        $q_query->execute();
        $q_res = $q_query->get_result()->fetch_assoc();

        if ($q_res) {
            $total_questions++;
            if (!empty($selected_option) && $q_res['correct_option'] === $selected_option) {
                $correct_answers++;
            }
        }
    }
}

$is_auto_submitted = isset($_POST['auto_submitted']) && $_POST['auto_submitted'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #0b0f19; color: #e4e4e7; font-family: sans-serif;">
    <div class="container text-center" style="margin-top: 100px; max-width: 600px; background: rgba(22, 28, 45, 0.8); padding: 40px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
        
        <?php if ($is_auto_submitted): ?>
            <div class="alert alert-danger mt-4 fw-bold fs-5">🚨 SYSTEM AUTO-SUBMITTED!</div>
            <p class="text-warning">ඔබ Quiz එක කරන අතරතුර වෙනත් Tab එකකට ගිය නිසා Quiz එක ස්වයංක්‍රීයව සබ්මිට් විය.</p>
        <?php else: ?>
            <div class="alert alert-success mt-4 fw-bold fs-5">✅ QUIZ COMPLETED!</div>
            <p class="text-muted">ඔබ සාර්ථකව ප්‍රශ්න පත්තරය අවසන් කරන ලදී.</p>
        <?php endif; ?>

        <hr style="border-color: rgba(255,255,255,0.1);">
        <h4 class="my-3">ඔබේ ලකුණු මට්ටම: <span class="text-success"><?= $correct_answers ?> / <?= $total_questions ?></span></h4>
        <p class="text-danger small">⚠️ ඔබගේ උත්සාහයන් (Attempts) ගණනින් 1ක් වැය විය.</p>
        <a href="lms.php" class="btn btn-primary w-100 py-2.5 fw-bold mt-3">BACK TO LMS PORTAL</a>
    </div>
</body>
</html>