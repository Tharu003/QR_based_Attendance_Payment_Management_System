<?php
/** @var mysqli $conn */
session_start();
include "db.php";

header('Content-Type: application/json');

if(!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = intval($_GET['student_id'] ?? 0);
$class_id   = intval($_GET['class_id'] ?? 0);
$month_name = trim($_GET['month'] ?? ''); 

if($student_id == 0 || $class_id == 0 || empty($month_name)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Parameters']);
    exit;
}

// 2026 හෝ පවතින වර්ෂය ගනී
$current_year = date('Y'); 
$start_date = date('Y-m-d', strtotime("first day of $month_name $current_year"));
$end_date   = date('Y-m-d', strtotime("last day of $month_name $current_year"));

// SQL Injection සහ Syntax Error මඟහැරීමට Prepared Statement භාවිතා කිරීම
$query = "SELECT date, time, status FROM attendance WHERE student_id = ? AND class_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iiss", $student_id, $class_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $dates = [];
    while($row = mysqli_fetch_assoc($result)) {
        $dates[] = [
            'date'   => $row['date'],
            'time'   => date('h:i A', strtotime($row['time'])),
            'status' => $row['status']
        ];
    }
    echo json_encode(['success' => true, 'dates' => $dates]);
} else {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
}