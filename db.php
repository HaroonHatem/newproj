<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'newproj';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

if (!function_exists('ensureInnoDbEngine')) {
    function ensureInnoDbEngine($conn, $schema, $table)
    {
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

if (!function_exists('tableExists')) {
    function tableExists($conn, $schema, $table)
    {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $schema, $table);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
}

if (!function_exists('dropForeignKeysOnColumn')) {
    function dropForeignKeysOnColumn($conn, $schema, $table, $column)
    {
        if (!tableExists($conn, $schema, $table)) {
            return;
        }
        $stmt = $conn->prepare("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $schema, $table, $column);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $constraint = $row['CONSTRAINT_NAME'];
            if ($constraint) {
                $sql = sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint);
                $conn->query($sql);
            }
        }
        $stmt->close();
    }
}

if (!function_exists('ensureForeignKeyReference')) {
    function ensureForeignKeyReference($conn, $schema, $table, $column, $referencedTable, $referencedColumn = 'id', $onDelete = 'SET NULL', $constraintName = null)
    {
        if (!tableExists($conn, $schema, $table) || !tableExists($conn, $schema, $referencedTable)) {
            return;
        }

        $stmt = $conn->prepare("SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $schema, $table, $column);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasCorrect = false;
        $constraintsToDrop = [];
        while ($row = $result->fetch_assoc()) {
            if (strcasecmp($row['REFERENCED_TABLE_NAME'], $referencedTable) === 0) {
                $hasCorrect = true;
                $constraintName = $row['CONSTRAINT_NAME'];
            } else {
                $constraintsToDrop[] = $row['CONSTRAINT_NAME'];
            }
        }
        $stmt->close();

        foreach ($constraintsToDrop as $constraint) {
            $sql = sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraint);
            $conn->query($sql);
        }

        if ($hasCorrect) {
            return;
        }

        if ($constraintName === null) {
            $constraintName = sprintf('fk_%s_%s', $table, $column);
        }

        $sql = sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`) ON DELETE %s ON UPDATE CASCADE',
            $table,
            $constraintName,
            $column,
            $referencedTable,
            $referencedColumn,
            $onDelete
        );
        $conn->query($sql);
    }
}

if (!function_exists('migrateLegacyAdmins')) {
    function migrateLegacyAdmins($conn, $schema)
    {
        $legacyEmails = ['haroonhatem34@gmail.com', 'hamzahmisr@gmail.com'];
        $legacyRecords = [];
        foreach ($legacyEmails as $email) {
            $stmt = $conn->prepare('SELECT id, name, email, password, created_at FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $email);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res && $res->num_rows === 1) {
                    $legacyRecords[] = $res->fetch_assoc();
                }
            }
            $stmt->close();
        }

        if (empty($legacyRecords)) {
            return;
        }

        $columnsToDrop = [
            ['graduate_verification_requests', 'reviewed_by'],
            ['company_verification_requests', 'reviewed_by'],
            ['account_removals', 'removed_by'],
            ['admin_conversations', 'created_by'],
            ['admin_conversations', 'other_admin_id'],
            ['admin_messages', 'sender_id'],
        ];
        foreach ($columnsToDrop as $target) {
            dropForeignKeysOnColumn($conn, $schema, $target[0], $target[1]);
        }

        foreach ($legacyRecords as $userRow) {
            $email = $userRow['email'];
            $adminId = null;

            $check = $conn->prepare('SELECT id FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1');
            if ($check) {
                $check->bind_param('s', $email);
                if ($check->execute()) {
                    $result = $check->get_result();
                    if ($result && $result->num_rows === 1) {
                        $adminId = (int)$result->fetch_assoc()['id'];
                        $update = $conn->prepare('UPDATE admins SET name = ?, password = ? WHERE id = ?');
                        if ($update) {
                            $update->bind_param('ssi', $userRow['name'], $userRow['password'], $adminId);
                            $update->execute();
                            $update->close();
                        }
                    }
                }
                $check->close();
            }

            if ($adminId === null) {
                $insert = $conn->prepare('INSERT INTO admins (name, email, password, created_at) VALUES (?,?,?,?)');
                if ($insert) {
                    $insert->bind_param('ssss', $userRow['name'], $userRow['email'], $userRow['password'], $userRow['created_at']);
                    if ($insert->execute()) {
                        $adminId = $insert->insert_id;
                    }
                    $insert->close();
                }
            }

            if ($adminId === null) {
                continue;
            }

            $oldId = (int)$userRow['id'];

            $updateStatements = [
                ['UPDATE graduate_verification_requests SET reviewed_by = ? WHERE reviewed_by = ?', $adminId, $oldId],
                ['UPDATE company_verification_requests SET reviewed_by = ? WHERE reviewed_by = ?', $adminId, $oldId],
                ['UPDATE account_removals SET removed_by = ? WHERE removed_by = ?', $adminId, $oldId],
                ['UPDATE admin_conversations SET created_by = ? WHERE created_by = ?', $adminId, $oldId],
                ['UPDATE admin_conversations SET other_admin_id = ? WHERE other_admin_id = ?', $adminId, $oldId],
                ['UPDATE admin_messages SET sender_id = ? WHERE sender_id = ?', $adminId, $oldId],
            ];

            foreach ($updateStatements as $sqlParts) {
                $stmt = $conn->prepare($sqlParts[0]);
                if ($stmt) {
                    $stmt->bind_param('ii', $sqlParts[1], $sqlParts[2]);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $delete = $conn->prepare('DELETE FROM users WHERE id = ?');
            if ($delete) {
                $delete->bind_param('i', $oldId);
                $delete->execute();
                $delete->close();
            }
        }
    }
}

$conn->query("CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

ensureInnoDbEngine($conn, $dbname, 'admins');

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

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE");
}

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_status'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN verification_status ENUM('pending','approved','rejected') DEFAULT 'pending'");
}

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'company_location'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN company_location VARCHAR(100) DEFAULT NULL");
}

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

$conn->query("CREATE TABLE IF NOT EXISTS graduate_verification_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  university VARCHAR(150) NOT NULL,
  field_of_study VARCHAR(150) NOT NULL,
  certificate_file VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_notes TEXT DEFAULT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  reviewed_by INT DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS company_verification_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  company_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  company_location VARCHAR(100) NOT NULL,
  website VARCHAR(255) DEFAULT NULL,
  commercial_register_file VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_notes TEXT DEFAULT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL,
  reviewed_by INT DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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

$conn->query("CREATE TABLE IF NOT EXISTS admin_conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_by INT NOT NULL,
  other_admin_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE,
  FOREIGN KEY (other_admin_id) REFERENCES admins(id) ON DELETE CASCADE,
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
  FOREIGN KEY (sender_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS account_removals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  removed_user_email VARCHAR(150) NOT NULL,
  removed_user_name VARCHAR(150) DEFAULT NULL,
  removed_user_type ENUM('graduate','company') DEFAULT NULL,
  reason TEXT DEFAULT NULL,
  removed_by INT DEFAULT NULL,
  removed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (removed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

migrateLegacyAdmins($conn, $dbname);

ensureForeignKeyReference($conn, $dbname, 'graduate_verification_requests', 'reviewed_by', 'admins', 'id', 'SET NULL');
ensureForeignKeyReference($conn, $dbname, 'company_verification_requests', 'reviewed_by', 'admins', 'id', 'SET NULL');
ensureForeignKeyReference($conn, $dbname, 'admin_conversations', 'created_by', 'admins', 'id', 'CASCADE', 'fk_admin_conversations_created_by');
ensureForeignKeyReference($conn, $dbname, 'admin_conversations', 'other_admin_id', 'admins', 'id', 'CASCADE', 'fk_admin_conversations_other_admin_id');
ensureForeignKeyReference($conn, $dbname, 'admin_messages', 'sender_id', 'admins', 'id', 'CASCADE');
ensureForeignKeyReference($conn, $dbname, 'account_removals', 'removed_by', 'admins', 'id', 'SET NULL');

// Session guard: if an account disappears, log the session out
if (session_status() === PHP_SESSION_ACTIVE) {
    if (!empty($_SESSION['is_admin'])) {
        $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0);
        if ($adminId > 0) {
            $check = $conn->prepare('SELECT id FROM admins WHERE id = ? LIMIT 1');
            if ($check) {
                $check->bind_param('i', $adminId);
                if ($check->execute()) {
                    $res = $check->get_result();
                    if ($res->num_rows === 0) {
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
                $check->close();
            }
        }
    } elseif (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        $check = $conn->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        if ($check) {
            $check->bind_param('i', $uid);
            if ($check->execute()) {
                $res = $check->get_result();
                if ($res->num_rows === 0) {
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
            $check->close();
        }
    }
}
?>
