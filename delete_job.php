<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
	header('Location: login.php');
	exit();
}
$company_id = (int)$_SESSION['user_id'];
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$job_id) {
	$_SESSION['message'] = 'طلب غير صالح.';
	header('Location: employer_dashboard.php');
	exit();
}

// Verify ownership
$stmt = $conn->prepare('SELECT id FROM jobs WHERE id=? AND company_id=? LIMIT 1');
$stmt->bind_param('ii', $job_id, $company_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
if (!$job) {
	$_SESSION['message'] = 'غير مصرح بحذف هذه الوظيفة.';
	header('Location: employer_dashboard.php');
	exit();
}

// Delete job (cascades remove applications, chats, notifications via FKs)
$del = $conn->prepare('DELETE FROM jobs WHERE id=?');
$del->bind_param('i', $job_id);
if ($del->execute()) {
	$_SESSION['message'] = 'تم حذف الوظيفة بنجاح.';
} else {
	$_SESSION['message'] = 'تعذر حذف الوظيفة.';
}
header('Location: employer_dashboard.php');
exit();
