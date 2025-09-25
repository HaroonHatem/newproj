<?php
session_start();
session_unset();
session_destroy();// انهاء الجلسة
header("Location: index.php"); // أو الصفحة الرئيسية
exit();
?>
