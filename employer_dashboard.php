<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login.php');
    exit();  
}
$company_id = $_SESSION['user_id'];

// Get company verification status and details
$stmt = $conn->prepare('SELECT is_verified, verification_status, name, company_location FROM users WHERE id = ?');
$stmt->bind_param('i', $company_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    // Check if company is verified before allowing job creation
    if (!$user_data['is_verified']) {
        $error = 'لا يمكنك إضافة الوظائف حتى يتم التحقق من هوية شركتك';
    } else {
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
        <!-- Welcome Message -->
        <div class="card welcome-message">
            <h1>مرحباً <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p>مرحباً بك في لوحة الشركة. يمكنك هنا إدارة الوظائف والبحث عن الخريجين المناسبين.</p>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="card success-message">
                <p><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Company Information Card -->
        <div class="card">
            <h2>معلومات الشركة</h2>
            <div class="company-info">
                <p><strong>اسم الشركة:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
                <p><strong>موقع الشركة:</strong> <?php echo htmlspecialchars($user_data['company_location']); ?></p>
            </div>
        </div>
        
        <!-- Verification Status Card -->
        <div class="card verification-status">
            <h2>حالة التحقق من الهوية</h2>
            <?php if ($user_data['is_verified']): ?>
                <div class="status-verified">
                    <span class="status-icon">✓</span>
                    <p>تم التحقق من هوية الشركة بنجاح - يمكنك الآن إضافة الوظائف</p>
                </div>
            <?php elseif ($user_data['verification_status'] === 'pending'): ?>
                <div class="status-pending">
                    <span class="status-icon">⏳</span>
                    <p>طلب التحقق من الهوية قيد المراجعة - يرجى الانتظار حتى يتم الموافقة على حسابك</p>
                </div>
            <?php elseif ($user_data['verification_status'] === 'rejected'): ?>
                <div class="status-rejected">
                    <span class="status-icon">✗</span>
                    <p>تم رفض طلب التحقق من الهوية. يرجى التواصل مع الإدارة لإعادة التقديم</p>
                </div>
            <?php else: ?>
                <div class="status-pending">
                    <span class="status-icon">⏳</span>
                    <p>لم يتم تقديم طلب التحقق بعد - يرجى تقديم طلب التحقق أولاً</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($user_data['is_verified']): ?>
        <!-- Job Creation Form - Only show if verified -->
        <div class='card'>
            <h2>إضافة وظيفة</h2>
            <?php if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
            <form method='post'>
                <input class='input' name='title' placeholder='عنوان الوظيفة' required>
                <input class='input' name='location' placeholder='الموقع'>
                <textarea class='input' name='description' placeholder='وصف الوظيفة' required></textarea>
                <button class='btn btn-primary' name='add_job' type='submit'>إضافة</button>
            </form>
        </div>
        <?php else: ?>
        <!-- Disabled Job Creation - Show when not verified -->
        <div class='card disabled-feature'>
            <h2>إضافة وظيفة</h2>
            <div class="disabled-message">
                <span class="disabled-icon">🔒</span>
                <p>لا يمكنك إضافة الوظائف حتى يتم التحقق من هوية شركتك</p>
                <p>يرجى انتظار موافقة الإدارة على طلب التحقق</p>
            </div>
        </div>
        <?php endif; ?>
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