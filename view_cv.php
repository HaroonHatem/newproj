<?php
session_start();
include 'db.php';

// Strict privacy: Only the owner (graduate) can view their own CV, or an employer viewing applicant's CV via application context.

function deny() {
    http_response_code(403);
    echo 'غير مصرح بالوصول';
    exit();
}

// Identify target user whose CV we want to show
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id <= 0) deny();

// Fetch CV file path for the user
$stmt = $conn->prepare('SELECT id, user_type, cv_file, cv_link FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
if (!$u) deny();

// Reject showing third-party links entirely for privacy and safety
if (!empty($u['cv_link'])) deny();

// Access rules
// 1) Must be logged in
if (!isset($_SESSION['user_id'])) deny();

$requester_id = (int)$_SESSION['user_id'];
$requester_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

// 2) Owner can view their own CV
if ($requester_id === (int)$u['id']) {
    $allowed = true;
} else {
    $allowed = false;
    // 3) Employers can view CV only if the graduate applied to one of their jobs
    if ($requester_type === 'company') {
        $chk = $conn->prepare('SELECT 1 FROM applications a JOIN jobs j ON a.job_id=j.id WHERE a.user_id=? AND j.company_id=? LIMIT 1');
        $chk->bind_param('ii', $user_id, $requester_id);
        $chk->execute();
        $allowed = $chk->get_result()->num_rows > 0;
    }
}

if (!$allowed) deny();

$file = $u['cv_file'];
if (empty($file) || !is_file($file)) {
    http_response_code(404);
    echo 'الملف غير موجود';
    exit();
}

// Serve file securely
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'pdf') $mime = 'application/pdf';
elseif ($ext === 'doc') $mime = 'application/msword';
elseif ($ext === 'docx') $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="cv_' . $u['id'] . '.' . $ext . '"');
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
readfile($file);
exit();
?>


