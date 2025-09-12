<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login.php');
    exit();  
}
$company_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    if ($title && $description) {
        $stmt = $conn->prepare('INSERT INTO jobs (company_id,title,description,location) VALUES (?,?,?,?)');
        $stmt->bind_param('isss', $company_id, $title, $description, $location);
        $stmt->execute();
        $_SESSION['message'] = 'تمت إضافة الوظيفة';
        header('Location: employer_dashboard.php');
        exit();
    } else $error = 'املأ الحقول';
}
$stmt = $conn->prepare('SELECT * FROM jobs WHERE company_id=? ORDER BY created_at DESC');
$stmt->bind_param('i', $company_id);
$stmt->execute();
$jobs = $stmt->get_result(); ?>
<!DOCTYPE html>
<html lang='ar' dir='rtl'>

<head>
    <meta charset='utf-8'>
    <title>لوحة الشركة</title>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <link rel='stylesheet' href='assets/css/style.css'>
</head>

<body><?php include 'navbar.php'; ?><main class='container'>
        <div class='card'>
            <h2>إضافة وظيفة</h2><?php if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?><form method='post'><input class='input' name='title' placeholder='عنوان الوظيفة' required><input class='input' name='location' placeholder='الموقع'><textarea class='input' name='description' placeholder='وصف الوظيفة' required></textarea><button class='btn btn-primary' name='add_job' type='submit'>إضافة</button></form>
        </div>
        <div class='card'>
            <h2>وظائفك المنشورة</h2><?php if (isset($_SESSION['message'])) {
                                        echo '<p class="success">' . htmlspecialchars($_SESSION['message']) . '</p>';
                                        unset($_SESSION['message']);
                                    }
                                    if ($jobs->num_rows > 0): ?><table class='table'>
                    <tr>
                        <th>العنوان</th>
                        <th>الموقع</th>
                        <th>تاريخ</th>
                        <th>إجراءات</th>
                    </tr><?php while ($r = $jobs->fetch_assoc()): ?><tr>
                            <td><?php echo htmlspecialchars($r['title']); ?></td>
                            <td><?php echo htmlspecialchars($r['location']); ?></td>
                            <td><?php echo $r['created_at']; ?></td>
                            <td><a class='btn btn-apply' href='view_applicants.php?job_id=<?php echo $r['id']; ?>'>المتقدمين</a> <a class='btn' href='edit_job.php?id=<?php echo $r['id']; ?>'>تعديل</a> <a class='btn btn-danger' href='delete_job.php?id=<?php echo $r['id']; ?>' onclick="return confirm('هل متأكد؟')">حذف</a></td>
                        </tr><?php endwhile; ?>
                </table><?php else: ?><p>لا توجد وظائف.</p><?php endif; ?>
        </div>
    </main>
</body>

</html>