<?php session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['job_id'])) {
    header('Location: employer_dashboard.php');
    exit();
}
$job_id = intval($_GET['job_id']);
$company_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT id FROM jobs WHERE id=? AND company_id=?');
$stmt->bind_param('ii', $job_id, $company_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die('لا تملك هذه الوظيفة');
$stmt2 = $conn->prepare('SELECT applications.*, users.name, users.email, users.phone, users.cv_file, users.cv_link FROM applications JOIN users ON applications.user_id=users.id WHERE applications.job_id=? ORDER BY applications.applied_at DESC');
$stmt2->bind_param('i', $job_id);
$stmt2->execute();
$apps = $stmt2->get_result(); ?>
<!DOCTYPE html>
<html lang='ar' dir='rtl'>

<head>
    <meta charset='utf-8'>
    <title>المتقدمين</title>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <link rel='stylesheet' href='assets/css/style.css'>
</head>

<body><?php include 'navbar.php'; ?><main class='container'>
        <div class='card'>
            <h2>قائمة المتقدمين</h2><?php if ($apps->num_rows > 0): ?><table class='table'>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>الهاتف</th>
                        <th>السيرة</th>
                        <th>التاريخ</th>
                    </tr><?php while ($a = $apps->fetch_assoc()): ?><tr>
                            <td><?php echo htmlspecialchars($a['name']); ?></td>
                            <td><?php echo htmlspecialchars($a['email']); ?></td>
                            <td><?php echo htmlspecialchars($a['phone']); ?></td>
                            <td><?php if (!empty($a['cv_link'])) echo "<a href='" . htmlspecialchars($a['cv_link']) . "' target='_blank'>رابط</a>";
                                            elseif (!empty($a['cv_file'])) echo "<a href='" . htmlspecialchars($a['cv_file']) . "' target='_blank'>ملف</a>";
                                            else echo '-'; ?></td>
                            <td><?php echo $a['applied_at']; ?></td>
                        </tr><?php endwhile; ?>
                </table><?php else: ?><p>لا يوجد متقدمين.</p><?php endif; ?>
        </div>
    </main>
</body>

</html>