<?php
header('Content-Type: application/json; charset=utf-8'); //

$conn = new mysqli("localhost", "root", "", "attendence"); //[cite: 4]
if ($conn->connect_error) { //[cite: 4]
    echo json_encode([]); //[cite: 4]
    exit(); //[cite: 4]
} //[cite: 4]
$conn->set_charset("utf8mb4"); //[cite: 4]

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0; //[cite: 4]

if ($teacher_id === 0) { //[cite: 4]
    echo json_encode([]); //[cite: 4]
    exit(); //[cite: 4]
} //[cite: 4]

// නිවැරදි කරන ලද Query එක - class_grades ටේබල් එක සහ grade එක ඉවත් කර ඇත.
$query = "SELECT id AS class_id, subject 
          FROM classes 
          WHERE teacher_id = ?
          ORDER BY subject ASC";

$stmt = $conn->prepare($query); //[cite: 4]
$response_data = []; //[cite: 4]

if ($stmt) { //[cite: 4]
    $stmt->bind_param("i", $teacher_id); //[cite: 4]
    $stmt->execute(); //[cite: 4]
    $result = $stmt->get_result(); //[cite: 4]

    if ($result && $result->num_rows > 0) { //[cite: 4]
        while ($row = $result->fetch_assoc()) { //[cite: 4]
            $response_data[] = [ //[cite: 4]
                'class_id' => $row['class_id'], //[cite: 4]
                'subject'  => $row['subject']
            ]; //[cite: 4]
        } //[cite: 4]
    } //[cite: 4]
    $stmt->close(); //[cite: 4]
} //[cite: 4]

echo json_encode($response_data, JSON_UNESCAPED_UNICODE); //[cite: 4]
?>