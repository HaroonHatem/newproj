<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'newproj';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
?>