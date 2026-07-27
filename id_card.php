<?php
include "db.php";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT s.*, c.subject 
            FROM students s 
            LEFT JOIN student_classes sc ON s.student_id = sc.student_id 
            LEFT JOIN classes c ON sc.class_id = c.id 
            WHERE s.student_id = $id LIMIT 1";
            
    $res = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($res) > 0) {
        $student = mysqli_fetch_assoc($res);
    } else {
        die("<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Student Not Found!</h1>");
    }
} else {
    die("<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Invalid Request! Please use the ID button.</h1>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo $student['student_name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #7209b7;
            --dark: #1e1e2f;
            --light: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* ID Card Container */
        .id-card {
            width: 340px;
            height: 540px;
            background: #fff;
            border-radius: 25px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            position: relative;
            text-align: center;
            border: 4px solid #fff;
        }

        /* Top Decorative Header */
        .header-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            height: 160px;
            position: relative;
            padding-top: 25px;
            color: white;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0% 100%);
        }

        .header-section h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .header-section p {
            margin: 2px 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        /* Profile Image with Gradient Border */
        .profile-wrap {
            width: 130px;
            height: 130px;
            margin: -75px auto 10px;
            position: relative;
            z-index: 10;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, var(--primary), var(--secondary)) border-box;
            border: 5px solid transparent;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .profile-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Student Info */
        .info-section {
            padding: 10px 25px;
        }

        .student-name {
            font-size: 22px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 4px;
            text-transform: capitalize;
        }

        .id-badge {
            display: inline-block;
            background: #eef2ff;
            color: var(--primary);
            padding: 4px 15px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* Details Grid */
        .details-grid {
            text-align: left;
            background: #fcfcfd;
            border-radius: 15px;
            padding: 15px;
            margin: 0 20px;
            border: 1px solid #f1f5f9;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .detail-item:last-child { margin-bottom: 0; }

        .icon-box {
            width: 30px;
            height: 30px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-right: 12px;
        }

        .detail-content label {
            display: block;
            font-size: 10px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
        }

        .detail-content span {
            font-size: 13px;
            color: #334155;
            font-weight: 600;
        }

        /* QR Section */
        .footer-qr {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 0 25px;
        }

        .qr-card {
            background: white;
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .footer-text {
            text-align: left;
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Floating Print Button */
        .print-btn {
            position: fixed;
            bottom: 30px;
            background: var(--dark);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .print-btn:hover {
            transform: translateY(-5px);
            background: #000;
        }

        /* Print Optimization */
        @media print {
            .print-btn { display: none; }
            body { background: none; }
            .id-card { box-shadow: none; border: 1px solid #eee; margin: 0 auto; }
        }
    </style>
</head>
<body>

<div class="id-card">
    <div class="header-section">
        <h2>Σ SIGMA</h2>
        <p>Higher Education Institute</p>
    </div>

    <div class="profile-wrap">
        <?php if (!empty($student['photo']) && file_exists($student['photo'])): ?>
            <img src="<?php echo $student['photo']; ?>" alt="Profile">
        <?php else: ?>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['student_name']); ?>&background=random&size=200" alt="Profile">
        <?php endif; ?>
    </div>

    <div class="info-section">
        <div class="student-name"><?php echo $student['student_name']; ?></div>
        <div class="id-badge"><?php echo $student['qr_token']; ?></div>

        <div class="details-grid">
            <div class="detail-item">
                <div class="icon-box"><i class="fas fa-graduation-cap"></i></div>
                <div class="detail-content">
                    <label>Academic Grade</label>
                    <span>Grade <?php echo $student['registered_grade']; ?></span>
                </div>
            </div>

            <div class="detail-item">
                <div class="icon-box"><i class="fas fa-book-open"></i></div>
                <div class="detail-content">
                    <label>Enrolled Subject</label>
                    <span><?php echo $student['subject'] ?: 'Not Assigned'; ?></span>
                </div>
            </div>

            <div class="detail-item">
                <div class="icon-box"><i class="fas fa-phone"></i></div>
                <div class="detail-content">
                    <label>Contact Number</label>
                    <span><?php echo $student['phone'] ?: 'N/A'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-qr">
        <div class="qr-card">
            <img width="70" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $student['qr_code']; ?>" alt="QR">
        </div>
        <div class="footer-text">
            <strong>Authorized ID Card</strong><br>
            Please carry this card at all times within the institute premises.
        </div>
    </div>
</div>

<a href="javascript:void(0)" onclick="window.print();" class="print-btn">
    <i class="fas fa-print"></i> Print Professional ID
</a>

</body>
</html>