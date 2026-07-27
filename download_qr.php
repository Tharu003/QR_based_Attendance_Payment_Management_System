<?php
if(!isset($_GET['data'])) exit;

$data = $_GET['data'];
$url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".$data;

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="'.$data.'.png' );

echo file_get_contents($url);
?>