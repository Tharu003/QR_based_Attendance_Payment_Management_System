<?php
session_start();
include 'db.php';

if (isset($_POST['login'])) {
    $qr_code = mysqli_real_escape_string($conn, $_POST['qr_code']);
    
    // Database query using qr_token
    $query = "SELECT * FROM students WHERE qr_token = '$qr_code'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['student_data'] = mysqli_fetch_assoc($result);
        header("Location: student_dashboard.php");
        exit();
    } else {
        $error = "Invalid QR Code or Admission Number!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Institute | Student Login</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-blue: #38bdf8;
            --accent-gold: #fbbf24;
            --dark-navy: #030712;
            --glass-bg: rgba(11, 15, 26, 0.8);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(rgba(3, 7, 18, 0.8), rgba(3, 7, 18, 0.8)), 
                        url('https://images.unsplash.com/photo-1523050853061-8c44f3123b74?auto=format&fit=crop&q=80&w=2000'); /* Modern Classroom/Library Image */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 50px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo i {
            font-size: 50px;
            background: linear-gradient(135deg, var(--accent-gold), #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-name {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -1px;
            margin-top: 10px;
        }

        .login-title {
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 30px;
            text-align: center;
            font-size: 0.95rem;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.85rem;
            margin-left: 15px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 15px 20px 15px 50px;
            color: #fff;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.15);
            color: #fff;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-gold);
            z-index: 10;
        }

        .btn-login {
            background: var(--accent-gold);
            border: none;
            border-radius: 20px;
            padding: 15px;
            font-weight: 800;
            color: #000;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(251, 191, 36, 0.3);
        }

        .alert-custom {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .footer-note {
            margin-top: 30px;
            text-align: center;
            color: #64748b;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <div class="login-card animate__animated animate__zoomIn">
        <div class="brand-logo">
            <i class="material-icons-round">rocket_launch</i>
            <div class="brand-name">SIGMA INSTITUTE</div>
        </div>

        <p class="login-title">Student Learning Management System</p>

        <?php if(isset($error)): ?>
            <div class="alert alert-custom animate__animated animate__shakeX mb-4">
                <span class="material-icons-round v-middle fs-6 me-2">error_outline</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-2">
                <label class="form-label">Authentication Key</label>
                <div class="input-group-custom">
                    <span class="material-icons-round input-icon">qr_code_scanner</span>
                    <input type="text" name="qr_code" class="form-control" placeholder="Scan QR or Enter Token" required autofocus>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-login w-100">
                ACCESS DASHBOARD
            </button>
        </form>

        <div class="footer-note">
            <p class="mb-1">Authorized Student Access Only</p>
            <p>© 2026 Sigma Hub Security</p>
        </div>
    </div>

    <!-- Background Decoration -->
    <div style="position: absolute; bottom: 30px; right: 30px; color: rgba(255,255,255,0.2);">
        <span class="material-icons-round" style="font-size: 100px;">school</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>