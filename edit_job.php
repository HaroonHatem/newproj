<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
	header('Location: login.php');
	exit();
}
$company_id = (int)$_SESSION['user_id'];
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$job_id) { header('Location: employer_dashboard.php'); exit(); }

// Fetch job and verify ownership
$stmt = $conn->prepare('SELECT id, title, description, location FROM jobs WHERE id=? AND company_id=? LIMIT 1');
$stmt->bind_param('ii', $job_id, $company_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
if (!$job) { $_SESSION['message'] = 'غير مصرح بتعديل هذه الوظيفة.'; header('Location: employer_dashboard.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
	$title = trim($_POST['title']);
	$location = trim($_POST['location']);
	$description = trim($_POST['description']);
	if ($title && $description) {
		$upd = $conn->prepare('UPDATE jobs SET title=?, description=?, location=? WHERE id=? AND company_id=?');
		$upd->bind_param('sssii', $title, $description, $location, $job_id, $company_id);
		if ($upd->execute()) {
			$_SESSION['message'] = 'تم تحديث الوظيفة بنجاح.';
			header('Location: employer_dashboard.php');
			exit();
		} else {
			$error = 'تعذر التحديث.';
		}
	} else {
		$error = 'يرجى تعبئة الحقول.';
	}
}
?>
<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
	<meta charset='utf-8'>
	<title>تعديل وظيفة</title>
	<meta name='viewport' content='width=device-width,initial-scale=1'>
	<link rel='stylesheet' href='assets/css/style.css'>
</head>
<body>
<?php include 'navbar.php'; ?>
<main class='container'>
	<div class='card form-card'>
		<h2>تعديل وظيفة</h2>
		<?php if (!empty($error)) echo '<p class="error">'.htmlspecialchars($error).'</p>'; ?>
		<form method='post'>
			<label>اسم الوظيفة</label>
			<input class='input' name='title' value='<?php echo htmlspecialchars($job['title']); ?>' required>
			<label>gps</label>
			<input class='input' name='location' value='<?php echo htmlspecialchars($job['location']); ?>'>
			<label>وصف الوظيفة</label>
			<textarea class='input' name='description' required><?php echo htmlspecialchars($job['description']); ?></textarea>
			<button class='btn btn-primary' name='update_job' type='submit'>حفظ</button>
			<a class='btn' href='employer_dashboard.php'>إلغاء</a>
		</form>
	</div>
</main>
</body>
</html>
