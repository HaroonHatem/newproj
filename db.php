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
// Ensure required tables exist (safety in case schema.sql wasn't executed)
$conn->query("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  user_type ENUM('graduate','company') NOT NULL DEFAULT 'graduate',
  university VARCHAR(150) DEFAULT NULL,
  specialization VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  cv_file VARCHAR(255) DEFAULT NULL,
  cv_link VARCHAR(255) DEFAULT NULL,
  website VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(150) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
?>

