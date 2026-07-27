<?php
session_start();
include 'db.php';

// සිසුවා Login වී ඇත්දැයි බැලීම
if(!isset($_SESSION['student_data'])) {
    header("Location: st_login.php");
    exit();
}

$s = $_SESSION['student_data'];
$sid = $s['student_id'];

if (!isset($_GET['mid'])) {
    header("Location: lms.php");
    exit();
}

$mid = mysqli_real_escape_string($conn, $_GET['mid']);

// 1. Attempt සීමාව පරීක්ෂා කිරීම (උත්සාහයන් 3කට වඩා කර ඇත්නම් බ්ලොක් කිරීම)
$attempt_sql = "SELECT COUNT(*) AS total_attempts FROM student_attempts WHERE user_id = '$sid' AND material_id = '$mid'";
$attempt_res = mysqli_query($conn, $attempt_sql);
$attempt_data = mysqli_fetch_assoc($attempt_res);
$attempts_count = (int)$attempt_data['total_attempts'];

if ($attempts_count >= 3) {
    echo "<script>alert('❌ ඔබට ලබා දී ඇති උපරිම උත්සාහයන් (Attempts 3) ප්‍රමාණය ඉක්මවා ඇත!'); window.location='lms.php';</script>";
    exit();
}

// 2. ප්‍රශ්න Database එකෙන් ලබා ගැනීම
$questions_sql = "SELECT * FROM quiz_questions WHERE material_id = '$mid'";
$res = mysqli_query($conn, $questions_sql);
$total_questions = mysqli_num_rows($res);

if ($total_questions == 0) {
    echo "<script>alert('No questions found!'); window.location='lms.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Interactive Quiz | LMS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg-secure: #040814;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --card-glass: rgba(22, 28, 45, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-secure);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: var(--text-light);
            /* User selection සහ Copy බ්ලොක් කිරීම සඳහා CSS */
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .quiz-card {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
            overflow: hidden;
            backdrop-filter: blur(15px);
        }

        .quiz-header {
            background: transparent;
            padding: 30px 40px 10px;
            border-bottom: 1px solid var(--border-glass);
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background-color: rgba(255,255,255,0.05);
            margin-bottom: 20px;
        }

        .progress-bar {
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            border-radius: 10px;
            transition: width 0.4s ease;
        }

        .question-box {
            padding: 30px 40px 40px;
        }

        .option-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid var(--border-glass);
            background: rgba(15, 23, 42, 0.4);
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 1.05rem;
            color: #cbd5e1;
        }

        .option-card:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
            color: #fff;
        }

        .option-card.selected {
            background: rgba(99, 102, 241, 0.3) !important;
            border-color: var(--primary) !important;
            color: #fff !important;
        }

        .btn-next {
            background: var(--primary);
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            transition: 0.3s;
        }

        .btn-next:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .hidden { display: none; }
    </style>
</head>

<!-- contextmenu බ්ලොක් කිරීම (Right Click බ්ලොක් කිරීම) -->
<body oncontextmenu="return false;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">

            <!-- Real-time Submit සඳහා සැඟවුණු Form එකක් -->
            <form id="secure-quiz-form" action="submit_quiz.php" method="POST">
                <input type="hidden" name="material_id" value="<?php echo $mid; ?>">
                <input type="hidden" name="auto_submitted" id="auto_submitted_field" value="false">

                <div class="quiz-card animate__animated animate__fadeIn">
                    
                    <div class="quiz-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-danger px-3 py-2 fw-bold"><span class="spinner-grow spinner-grow-sm me-1"></span>SECURE EXAM MODE</span>
                            <span class="text-muted fw-bold" id="q-counter">Question 1 / <?php echo $total_questions; ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" id="p-bar" role="progressbar" style="width: <?php echo (1/$total_questions)*100; ?>%"></div>
                        </div>
                    </div>

                    <div id="quiz-container">
                        <?php 
                        $count = 1;
                        while($q = mysqli_fetch_assoc($res)): 
                        ?>
                        <div class="question-box <?php echo ($count > 1) ? 'hidden' : ''; ?>" id="q-<?php echo $count; ?>">
                            
                            <h3 class="fw-bold mb-4 lh-base text-white"><?php echo $count . ". " . htmlspecialchars($q['question']); ?></h3>

                            <!-- සැඟවුණු Input එකක් මඟින් තෝරන පිළිතුර Form එකට සම්බන්ධ කරයි -->
                            <input type="hidden" name="answers[<?php echo $q['id']; ?>][selected]" id="input-q-<?php echo $count; ?>" value="">

                            <div class="options">
                                <div class="option-card" onclick="selectAnswer(this, 'A', <?php echo $count; ?>)">
                                    <span>A. <?php echo htmlspecialchars($q['option_a']); ?></span>
                                </div>
                                <div class="option-card" onclick="selectAnswer(this, 'B', <?php echo $count; ?>)">
                                    <span>B. <?php echo htmlspecialchars($q['option_b']); ?></span>
                                </div>
                                <div class="option-card" onclick="selectAnswer(this, 'C', <?php echo $count; ?>)">
                                    <span>C. <?php echo htmlspecialchars($q['option_c']); ?></span>
                                </div>
                                <div class="option-card" onclick="selectAnswer(this, 'D', <?php echo $count; ?>)">
                                    <span>D. <?php echo htmlspecialchars($q['option_d']); ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end align-items-center mt-4">
                                <?php if($count == $total_questions): ?>
                                    <button type="button" class="btn btn-success btn-next" onclick="submitExam(false)">
                                        Finish & Submit Quiz
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary btn-next" onclick="nextQuestion(<?php echo $count; ?>)">
                                        Next Question →
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                        <?php $count++; endwhile; ?>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
let total = <?php echo $total_questions; ?>;
let isSubmitted = false;

// 1. පිළිතුරක් තෝරාගැනීම
function selectAnswer(element, option, qNum) {
    let options = element.parentElement.querySelectorAll('.option-card');
    options.forEach(opt => opt.classList.remove('selected'));
    
    element.classList.add('selected');
    document.getElementById('input-q-' + qNum).value = option;
}

// 2. ඊළඟ ප්‍රශ්නයට යාම
function nextQuestion(current) {
    document.getElementById('q-' + current).classList.add('hidden');

    let next = current + 1;
    let nextBox = document.getElementById('q-' + next);

    if(nextBox) {
        nextBox.classList.remove('hidden');
        document.getElementById('q-counter').innerText = `Question ${next} / ${total}`;
        document.getElementById('p-bar').style.width = (next / total) * 100 + "%";
    }
}

// 3. Quiz එක Submit කිරීමේ ප්‍රධාන Function එක
function submitExam(isAuto = false) {
    if (isSubmitted) return; // දෙපාරක් සබ්මිට් වීම වැළැක්වීම
    isSubmitted = true;

    if(isAuto) {
        document.getElementById('auto_submitted_field').value = "true";
    }

    // Form එක සාමාන්‍ය පරිදි submit_quiz.php වෙත යොමු කරයි
    document.getElementById('secure-quiz-form').submit();
}

/* =========================================================================
   🛡️ ANTI-CHEAT & PROCTORING LOGIC (SECURITY)
   ========================================================================= */

// A. පිටුවෙන් පිටතට යාම (Tab Switch / Minimize) හඳුනාගෙන Auto-Submit කිරීම
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden' && !isSubmitted) {
        alert('🚨 Security Warning: ඔබ වෙනත් පිටුවකට යාමට උත්සාහ කළ නිසා Quiz එක ස්වයංක්‍රීයව Submit වේ!');
        submitExam(true);
    }
});

// B. පිටුවෙන් ඉවත් වීමට යාමේදී (Reload/Close) අවවාද කිරීම
window.addEventListener('beforeunload', function (e) {
    if (!isSubmitted) {
        e.preventDefault();
        e.returnValue = 'ඔබ විභාගය අතරතුර පිටුවෙන් ඉවත් වීමට උත්සාහ කරයි. මෙයින් ඔබගේ Attempt එකක් අපතේ යා හැක.';
    }
});

// C. Copy (Ctrl+C), Cut, Paste, PrintScreen සහ Inspect Element බ්ලොක් කිරීම
document.addEventListener('keydown', function(e) {
    // 1. Copy, Paste, Cut බ්ලොක් කිරීම
    if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'p')) {
        e.preventDefault();
        alert('🚫 මෙම පිටුවේ අන්තර්ගතය පිටපත් කිරීම (Copy/Print) තහනම් කර ඇත!');
        return false;
    }
    // 2. Inspect Element (F12, Ctrl+Shift+I, Ctrl+Shift+J) බ්ලۆක් කිරීම
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) || (e.ctrlKey && e.key === 'U')) {
        e.preventDefault();
        alert('🚫 Developer tools භාවිතය තහනම්!');
        return false;
    }
});

// D. Print Screen Button එක ඔබන විට Alert එකක් පෙන්වීම
document.addEventListener('keyup', function(e) {
    if (e.key === 'PrintScreen') {
        navigator.clipboard.writeText(''); // Clipboard එක ක්ලියර් කිරීම
        alert('📸 Screen Shots ලබා ගැනීම සපුරා තහනම්!');
    }
});
</script>

</body>
</html>