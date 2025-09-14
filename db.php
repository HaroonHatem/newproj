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

// Add verification status to users table (with error handling)
// Check if columns exist before adding them
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE");
}

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_status'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN verification_status ENUM('pending','approved','rejected') DEFAULT 'pending'");
}

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'company_location'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN company_location VARCHAR(100) DEFAULT NULL");
}

// Chat conversations table
$conn->query("CREATE TABLE IF NOT EXISTS chat_conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  company_id INT NOT NULL,
  graduate_id INT NOT NULL,
  application_id INT NOT NULL,
  status ENUM('active','closed') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (graduate_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  UNIQUE KEY unique_conversation (job_id, graduate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Chat messages table
$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  message_type ENUM('text','file') DEFAULT 'text',
  file_path VARCHAR(255) DEFAULT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Notifications table
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('application','message','verification') NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  related_id INT DEFAULT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
?>

