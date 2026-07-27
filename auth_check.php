<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ලොගින් වෙලා නැත්නම් කෙලින්ම ලොගින් පිටුවට හරවා යැවීම
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// 2. දැනට ලොගින් වෙලා ඉන්න කෙනාගේ Role එක ගෝලීයව (Globally) ලබා ගැනීම
$user_role = $_SESSION['role']; // admin, teacher, assistant
$user_name = $_SESSION['full_name'] ?? 'User';

// 3. (අත්‍යවශ්‍ය නම්) යම් පිටුවකට විශේෂිත Roles පමණක් ඉඩ දීමට Helper Function එකක්
function restrictTo($allowed_roles) {
    // $allowed_roles කියන්නේ array එකක් (උදා: ['admin', 'teacher'])
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // අවසර නැති කෙනෙක් ආවොත් Access Denied කියලා පෙන්වනවා හෝ Dashboard එකට හරවනවා
        echo "<div style='color:red; font-family:sans-serif; text-align:center; margin-top:50px;'>
                <h2>Access Denied!</h2>
                <p>ඔබට මෙම පිටුව නැරඹීමට අවසර නැත.</p>
                <a href='dashboard.php'>Back to Dashboard</a>
              </div>";
        exit();
    }
}
?>