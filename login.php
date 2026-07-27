<?php
session_start();

// ඩේටාබේස් සම්බන්ධතාවය (Database Connection)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "attendence"; 

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$error = "";

// ලොගින් ෆෝම් එක සබ්මිට් කළ පසු (When login form is submitted)
if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // SQL Injection වලින් ආරක්ෂා වීමට Prepared Statements භාවිතා කිරීම
    $stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        
        // ඩේටාබේස් එකේ ඇති password එක සමඟ කෙලින්ම සැසඳීම (Plain-text comparison)
        if ($password === $data['password']) {
            
            // Session දත්ත ගබඩා කිරීම
            $_SESSION['user_id']   = $data['id']; 
            $_SESSION['username']  = $data['username']; 
            $_SESSION['full_name'] = $data['name']; 
            $_SESSION['role']      = $data['role']; 

            // පරිශීලකයාගේ භූමිකාව (Role) අනුව අදාළ Dashboard එකට යොමු කිරීම
            if ($data['role'] === 'student') {
                header("Location: student_dashboard.php");
            } else {
                // admin, teacher, හෝ assistant නම් ප්‍රධාන dashboard එකට
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "ඇතුළත් කළ මුරපදය (Password) වැරදියි!";
        }
    } else {
        $error = "එවැනි පරිශීලක නාමයක් (Username) පද්ධතියේ නැත!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Hub | Portal Login</title>

    <!-- Google Fonts, Bootstrap & Animate.css -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-bg: #030712;
            --accent-gold: #fbbf24;
            --card-glass: rgba(11, 15, 26, 0.85);
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--primary-bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(251, 191, 36, 0.08) 0%, transparent 40%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-box {
            background: var(--card-glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border-glass);
            border-radius: 40px;
            padding: 50px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            position: relative;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--accent-gold), #f59e0b);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(251, 191, 36, 0.2);
        }

        .brand-icon i { color: #000; font-size: 35px; }

        .brand-name {
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .portal-label {
            color: var(--accent-gold);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: block;
            margin-top: 5px;
        }

        /* Input Group Custom Styling */
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-custom i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            transition: 0.3s;
            z-index: 10;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 16px 20px 16px 55px;
            color: #fff;
            font-weight: 500;
            transition: 0.3s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.1);
            color: #fff;
        }

        .form-control:focus + i { color: var(--accent-gold); }

        .btn-login {
            background: var(--accent-gold);
            color: #000;
            border: none;
            border-radius: 20px;
            padding: 16px;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 15px;
        }

        .btn-login:hover {
            background: #fff;
            color: #000;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(251, 191, 36, 0.3);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }

        .v-middle {
            vertical-align: middle;
        }

        .footer-text {
            text-align: center;
            margin-top: 35px;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <div class="login-box animate__animated animate__fadeInUp">
        <div class="brand-header">
            <div class="brand-icon">
                <i class="material-icons-round">admin_panel_settings</i>
            </div>
            <div class="brand-name">SIGMA HUB</div>
            <span class="portal-label">Portal Access</span>
        </div>

        <!-- Error Message Container -->
        <?php if($error != ""): ?>
            <div class="error-msg animate__animated animate__shakeX">
                <span class="material-icons-round v-middle fs-6 me-2">report_problem</span>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="">
            <div class="input-group-custom">
                <input type="text" name="username" class="form-control" placeholder="Username" required autocomplete="off">
                <i class="material-icons-round">alternate_email</i>
            </div>

            <div class="input-group-custom">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <i class="material-icons-round">lock_open</i>
            </div>

            <button type="submit" name="login" class="btn btn-login w-100 d-flex align-items-center justify-content-center gap-2">
                <span>Sign In</span>
                <i class="material-icons-round">arrow_forward</i>
            </button>
        </form>

        <div class="footer-text">
            <p>© 2026 Sigma Learning Management System <br> Secure Environments Portal</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>