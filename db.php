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

// Ensure legacy tables are upgraded to InnoDB before adding new foreign keys
if (!function_exists('ensureInnoDbEngine')) {
    function ensureInnoDbEngine($conn, $schema, $table) {
        $stmt = $conn->prepare("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $schema, $table);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res && ($row = $res->fetch_assoc())) {
                    if (isset($row['ENGINE']) && strtolower($row['ENGINE']) !== 'innodb') {
                        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                        if ($safeTable !== '') {
                            $sql = sprintf('ALTER TABLE `%s` ENGINE=InnoDB', $safeTable);
                            $conn->query($sql);
                        }
                    }
                }
            }
            $stmt->close();
        }
    }
}

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

ensureInnoDbEngine($conn, $dbname, 'users');
$conn->query("CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(150) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

ensureInnoDbEngine($conn, $dbname, 'jobs');
$conn->query("CREATE TABLE IF NOT EXISTS applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

ensureInnoDbEngine($conn, $dbname, 'applications');
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

// Admin-only chat tables
$conn->query("CREATE TABLE IF NOT EXISTS admin_conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_by INT NOT NULL,
  other_admin_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (other_admin_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_admin_pair (created_by, other_admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS admin_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES admin_conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Account removals log
$conn->query("CREATE TABLE IF NOT EXISTS account_removals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  removed_user_email VARCHAR(150) NOT NULL,
  removed_user_name VARCHAR(150) DEFAULT NULL,
  removed_user_type ENUM('graduate','company') DEFAULT NULL,
  reason TEXT DEFAULT NULL,
  removed_by INT DEFAULT NULL,
  removed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (removed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Session guard: if a user was deleted, log them out immediately
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $check = $conn->prepare('SELECT id FROM users WHERE id=? LIMIT 1');
    $check->bind_param('i', $uid);
    if ($check->execute()) {
        $res = $check->get_result();
        if ($res->num_rows === 0) {
            // Destroy session and redirect to login with notice
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: login.php?removed=1');
            exit();
        }
    }
}
?>

