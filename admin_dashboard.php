<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit();
}

// Registrations (latest 20)
$users = $conn->query("SELECT id, name, email, user_type, is_verified, verification_status, created_at FROM users ORDER BY created_at DESC LIMIT 20");

// Applications (latest 20)
$applications = $conn->query("SELECT a.id, a.status, a.applied_at, j.title as job_title, u1.name as graduate_name, u2.name as company_name
FROM applications a
JOIN jobs j ON a.job_id=j.id
JOIN users u1 ON a.user_id=u1.id
JOIN users u2 ON j.company_id=u2.id
ORDER BY a.applied_at DESC LIMIT 20");

// Conversations (latest 20 by update)
$conversations = $conn->query("SELECT cc.id, cc.updated_at, j.title as job_title, ug.name as graduate_name, uc.name as company_name
FROM chat_conversations cc
JOIN jobs j ON cc.job_id=j.id
JOIN users ug ON cc.graduate_id=ug.id
JOIN users uc ON cc.company_id=uc.id
ORDER BY cc.updated_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>لوحة المدير</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<main class="container">
  <div class="card">
    <h2>أهلاً بك في لوحة المدير</h2>
    <p>عرض سريع لأحدث الأنشطة على المنصة.</p>
  </div>

  <div class="card">
    <h3>أحدث التسجيلات</h3>
    <?php if ($users && $users->num_rows > 0): ?>
      <table class="table">
        <tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>التحقق</th><th>التاريخ</th></tr>
        <?php while($u = $users->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['user_type'] === 'company' ? 'شركة' : 'خريج'; ?></td>
            <td><?php echo $u['is_verified'] ? '✓' : ($u['verification_status']==='pending'?'قيد المراجعة':'غير موثق'); ?></td>
            <td><?php echo htmlspecialchars($u['created_at']); ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?><p>لا بيانات.</p><?php endif; ?>
  </div>

  <div class="card">
    <h3>أحدث طلبات التوظيف</h3>
    <?php if ($applications && $applications->num_rows > 0): ?>
      <table class="table">
        <tr><th>الخريج</th><th>الشركة</th><th>الوظيفة</th><th>الحالة</th><th>التاريخ</th></tr>
        <?php while($a = $applications->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($a['graduate_name']); ?></td>
            <td><?php echo htmlspecialchars($a['company_name']); ?></td>
            <td><?php echo htmlspecialchars($a['job_title']); ?></td>
            <td><?php echo $a['status']==='pending'?'معلق':($a['status']==='accepted'?'مقبول':'مرفوض'); ?></td>
            <td><?php echo htmlspecialchars($a['applied_at']); ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?><p>لا بيانات.</p><?php endif; ?>
  </div>

  <div class="card">
    <h3>أحدث المحادثات</h3>
    <?php if ($conversations && $conversations->num_rows > 0): ?>
      <table class="table">
        <tr><th>الخريج</th><th>الشركة</th><th>الوظيفة</th><th>آخر تحديث</th></tr>
        <?php while($c = $conversations->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['graduate_name']); ?></td>
            <td><?php echo htmlspecialchars($c['company_name']); ?></td>
            <td><?php echo htmlspecialchars($c['job_title']); ?></td>
            <td><?php echo htmlspecialchars($c['updated_at']); ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?><p>لا بيانات.</p><?php endif; ?>
  </div>
</main>
</body>
</html>
