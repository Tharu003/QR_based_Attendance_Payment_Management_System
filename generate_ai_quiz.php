<?php
ini_set('display_errors', 0); // අනවශ්‍ය warning errors json එක කැඩීම වැළැක්වීමට
header('Content-Type: application/json');

// ❗ Composer Libraries load කිරීම (මෙය නිවැරදිව ඔබගේ path එකට අනුව යොදන්න)
require_once __DIR__ . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User අවසර පරීක්ෂාව
$allowed_roles = ['admin', 'teacher'];
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)){
    echo json_encode(["error" => "Unauthorized access."]);
    exit();
}

// PDF එක ලැබී ඇත්දැයි බැලීම
if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["error" => "Please upload a valid PDF file."]);
    exit();
}

// ---------------------------------------------------------
// 🛡️ 1. වඩාත් නිවැරදි PDF TEXT PARSER (SMALOT LIBRARY)
// ---------------------------------------------------------
function parse_pdf_to_text($filename) {
    try {
        // Smalot PDF Parser එක මඟින් වඩාත් නිවැරදිව Text Extract කිරීම
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($filename);
        $text   = $pdf->getText();
        
        // සිංහල සහ ඉංග්‍රීසි අකුරු, ඉලක්කම් පමණක් ඉතිරි කර අනවශ්‍ය කේත ඉවත් කිරීම
        $text = preg_replace('/[^a-zA-Z0-9\s.,?!-\x{0D80}-\x{0DFF}]/u', '', $text);
        return trim($text);
    } catch (\Exception $e) {
        return ""; // කිසියම් දෝෂයක් ආවොත් හිස් අගයක් යවයි
    }
}

$pdf_path = $_FILES['pdf_file']['tmp_name'];
$pdf_text = parse_pdf_to_text($pdf_path);

// පරීක්ෂා කිරීම සඳහා අවම අකුරු ප්‍රමාණය 20 දක්වා අඩු කර ඇත
if (strlen($pdf_text) < 20) {
    echo json_encode(["error" => "Could not extract text from this PDF. The font format may not be supported or it is an image."]);
    exit();
}

// ---------------------------------------------------------
// 🔑 2. GEMINI API SETUP & RETRY LOGIC (429 ERROR PREVENTION)
// ---------------------------------------------------------
$api_key = "AIzaSyDVYUpMppcHB7M0XXqV56lq8Tm6ttfJ99Y";
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;

$prompt = "Analyze the following educational text and generate exactly 5 multiple choice questions (MCQs) based ONLY on this content. "
        . "CRITICAL: The entire question text, and options (a, b, c, d) MUST be written in Sinhala language. "
        . "The structure must match the JSON schema provided.";

$payload = [
    "contents" => [
        ["parts" => [["text" => "TEXT CONTENT TO ANALYZE:\n" . substr($pdf_text, 0, 4000) . "\n\n" . $prompt]]]
    ],
    "generationConfig" => [
        "responseMimeType" => "application/json", 
        "responseSchema" => [
            "type" => "ARRAY",
            "items" => [
                "type" => "OBJECT",
                "properties" => [
                    "text" => ["type" => "STRING", "description" => "The question written in Sinhala"],
                    "a" => ["type" => "STRING", "description" => "Option A in Sinhala"],
                    "b" => ["type" => "STRING", "description" => "Option B in Sinhala"],
                    "c" => ["type" => "STRING", "description" => "Option C in Sinhala"],
                    "d" => ["type" => "STRING", "description" => "Option D in Sinhala"],
                    "correct" => ["type" => "STRING", "description" => "Must be either a, b, c, or d"]
                ],
                "required" => ["text", "a", "b", "c", "d", "correct"]
            ]
        ]
    ]
];

// --- 🛠️ RETRY LOOP ---
$max_retries = 3;
$retry_count = 0;
$delay = 2; 
$response = "";
$http_code = 0;

while ($retry_count < $max_retries) {
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        break; 
    } elseif ($http_code === 429) {
        $retry_count++;
        sleep($delay);
        $delay *= 2; 
    } else {
        break;
    }
}

if ($http_code !== 200) {
    echo json_encode([
        "error" => "Gemini API Error (HTTP Code: $http_code)", 
        "details" => json_decode($response, true)
    ]);
    exit();
}

$result = json_decode($response, true);
$ai_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

$questions = json_decode(trim($ai_text), true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
    echo json_encode(["error" => "Failed to parse AI response into JSON array.", "raw" => $ai_text]);
} else {
    echo json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
exit();
?>