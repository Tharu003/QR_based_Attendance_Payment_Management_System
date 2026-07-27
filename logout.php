<?php
session_start();
session_destroy();
header("Location: st_login.php");
exit();
?>