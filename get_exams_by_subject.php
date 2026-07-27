<?php
header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli("localhost", "root", "", "attendence");
if ($conn->connect_error) { echo json_encode([]); exit(); }
$conn->set_charset("utf8mb4");

$subject = isset($_GET['subject']) ? $_GET['subject'] : '';

if (empty($subject)) {
    echo json_encode([]);
    exit();
}

// 💡 තෝරාගත් විෂයට අදාළව දැනට පවත්වා ඇති විභාග සහ ශ්‍රේණි පමණක් ලබා ගනී
$query = "SELECT e.id, e.exam_date, cg.grade 
          FROM exams e 
          INNER JOIN classes c ON e.class_id = c.id 
          INNER JOIN class_grades cg ON c.id = cg.class_id 
          WHERE c.subject = ?
          ORDER BY e.id DESC";

$stmt = $conn->prepare($query);
$response = [];

if ($stmt) {
    $stmt->bind_param("s", $subject);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'id' => $row['id'],
            'exam_date' => $row['exam_date'],
            'grade' => $row['grade']
        ];
    }
    $stmt->close();
}

echo json_encode($response);
?>