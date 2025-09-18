<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login.php');
    exit();
}

$company_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employer_dashboard.php');
    exit();
}

$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$application_id || !$job_id || !in_array($action, ['accept','reject'])) {
    $_SESSION['message'] = 'طلب غير صالح';
    header('Location: employer_dashboard.php');
    exit();
}

// Verify this application belongs to a job owned by this company
$stmt = $conn->prepare('SELECT a.id, a.user_id, a.status, j.title, j.company_id FROM applications a JOIN jobs j ON a.job_id=j.id WHERE a.id=? AND a.job_id=? LIMIT 1');
$stmt->bind_param('ii', $application_id, $job_id);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();
if (!$app || (int)$app['company_id'] !== (int)$company_id) {
    $_SESSION['message'] = 'غير مصرح';
    header('Location: employer_dashboard.php');
    exit();
}

$new_status = $action === 'accept' ? 'accepted' : 'rejected';
if ($app['status'] !== 'pending') {
    $_SESSION['message'] = 'تمت معالجة الطلب مسبقاً';
    header('Location: view_applicants.php?job_id=' . $job_id);
    exit();
}

$upd = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
$upd->bind_param('si', $new_status, $application_id);
if ($upd->execute()) {
    // Notify graduate
    $notif_title = $new_status === 'accepted' ? 'قبول طلب التوظيف' : 'رفض طلب التوظيف';
    $notif_msg = ($new_status === 'accepted' ? 'تم قبول طلبك' : 'تم رفض طلبك') . ' للوظيفة: ' . $app['title'];
    $n = $conn->prepare('INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, "application", ?, ?, ?)');
    $n->bind_param('issi', $app['user_id'], $notif_title, $notif_msg, $application_id);
    $n->execute();

    $_SESSION['message'] = 'تم تحديث حالة الطلب بنجاح';
} else {
    $_SESSION['message'] = 'فشل تحديث الحالة';
}

header('Location: view_applicants.php?job_id=' . $job_id);
exit();
