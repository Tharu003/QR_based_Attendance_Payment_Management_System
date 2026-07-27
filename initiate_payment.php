<?php
session_start();
include 'db.php';

if (!isset($_SESSION['student_data']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: payments.php");
    exit();
}

$s = $_SESSION['student_data'];
$sid = $s['student_id'];

// Get values from previous page form post
$class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
$billing_month = mysqli_real_escape_string($conn, $_POST['billing_month']);
$amount = mysqli_real_escape_string($conn, $_POST['amount']);

/* =========================================================================
   1. Fetch Additional Details for PayHere Integration
   ========================================================================= */
// Fetch Class & Subject Name
$class_query = "SELECT subject FROM classes WHERE id = '$class_id'";
$class_res = mysqli_query($conn, $class_query);
$class_row = mysqli_fetch_assoc($class_res);
$subject_name = $class_row['subject'] ?? "Institute Tuition Fee";

// ✅ FIXED: Fetching 'student_name' and 'phone' based on your schema
$stu_query = "SELECT student_name, phone FROM students WHERE student_id = '$sid'";
$stu_res = mysqli_query($conn, $stu_query);
$stu_row = mysqli_fetch_assoc($stu_res);

$full_name = $stu_row['student_name'] ?? "Student Name";
$phone = $stu_row['phone'] ?? "0771234567";
$email = "student_" . $sid . "@sigma.lk"; // Default placeholder since email column is not in schema

// 🔄 Split Student Name into First Name & Last Name for PayHere Compliance
$name_parts = explode(' ', trim($full_name));
$first_name = $name_parts[0];
$last_name = (count($name_parts) > 1) ? implode(' ', array_slice($name_parts, 1)) : $sid;

/* =========================================================================
   2. PayHere Credentials Setup (Sandbox Configurations)
   ========================================================================= */
$merchant_id = "1224444"; // ⚠️ ඔයාගේ ඇත්තම PayHere Merchant ID එක මෙතනට දාන්න
$currency = "LKR";

// Unique Order ID tracker generation
$order_id = "SIGMA_" . $sid . "_" . $class_id . "_" . str_replace(' ', '_', $billing_month) . "_" . time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Institute | Securing Connection...</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg: #030712;
            --card-glass: rgba(17, 24, 39, 0.7);
            --border-glass: rgba(255, 255, 255, 0.06);
            --blue: #3b82f6;
            --mint: #10b981;
            --text: #f9fafb;
            --muted: #9ca3af;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.06) 0%, transparent 50%);
        }

        .gateway-card {
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 32px;
            padding: 45px;
            max-width: 480px;
            width: 100%;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .loader-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 30px auto;
        }

        .loader-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid rgba(59, 130, 246, 0.1);
            border-top: 4px solid var(--blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loader-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--blue);
            font-size: 32px;
            animation: pulse 1.5s infinite ease-in-out;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(0.95); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); }
        }

        .pulse-text {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff, #9ca3af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .receipt-summary {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 20px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .summary-row:last-child { margin-bottom: 0; }

        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--mint);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 25px;
            border: 1px solid rgba(16, 185, 129, 0.15);
        }
    </style>
</head>
<body>

    <div class="gateway-card animate__animated animate__zoomIn">
        
        <div class="loader-container">
            <div class="loader-circle"></div>
            <span class="material-icons-round loader-icon">gpp_good</span>
        </div>

        <h2 class="pulse-text">Connecting Securely</h2>
        <p class="text-white fw-semi">Please wait a moment while we establish an encrypted bridge to PayHere checkout portal. Do not close or refresh this tab.</p>

        <div class="receipt-summary">
            <div class="summary-row">
                <span class="text-white fw-semi">Payment For:</span>
                <span class="fw-bold text-white"><?php echo htmlspecialchars($subject_name); ?></span>
            </div>
            <div class="summary-row">
                <span class="text-white fw-semi">Billing Period:</span>
                <span class="text-white"><?php echo htmlspecialchars($billing_month); ?></span>
            </div>
            <div class="summary-row">
                <span class="text-white fw-semi">Student Name:</span>
                <span class="text-white"><?php echo htmlspecialchars($full_name); ?></span>
            </div>
            <hr style="border-color: rgba(255,255,255,0.08); margin: 15px 0;">
            <div class="summary-row align-items-center">
                <span class="fw-bold text-white">Payable Amount:</span>
                <span class="fw-800 text-info fs-5">Rs. <?php echo number_format($amount, 2); ?></span>
            </div>
        </div>

        <div class="secure-badge">
            <span class="material-icons-round" style="font-size: 14px;">lock</span> PayHere Certified Sandbox Gateway
        </div>

        <form id="payhereForm" method="post" action="https://sandbox.payhere.lk/pay/checkout">   
            <input type="hidden" name="merchant_id" value="<?php echo $merchant_id; ?>">
            <input type="hidden" name="return_url" value="http://localhost/tuition/payment_success.php">
            <input type="hidden" name="cancel_url" value="http://localhost/tuition/payments.php">
            <input type="hidden" name="notify_url" value="http://localhost/tuition/payment_notify.php">  
            
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            <input type="hidden" name="items" value="<?php echo htmlspecialchars($subject_name . " - " . $billing_month); ?>">
            <input type="hidden" name="currency" value="<?php echo $currency; ?>">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">  
            
            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            <input type="hidden" name="address" value="Sigma Institute Portal">
            <input type="hidden" name="city" value="Colombo">
            <input type="hidden" name="country" value="Sri Lanka">

            <input type="hidden" name="custom_1" value="<?php echo htmlspecialchars($sid); ?>">
            <input type="hidden" name="custom_2" value="<?php echo htmlspecialchars($class_id); ?>">
            <input type="hidden" name="custom_3" value="<?php echo htmlspecialchars($billing_month); ?>">
        </form>

    </div>

    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            setTimeout(() => {
                document.getElementById('payhereForm').submit();
            }, 3000);
        });
    </script>

</body>
</html>