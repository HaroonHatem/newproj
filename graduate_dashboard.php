<?php
session_start();
include 'db.php';

// Check if user is logged in and is a graduate
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'graduate') {
    header('Location: login.php');
    exit();
}

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// Get user verification status
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT is_verified, verification_status FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// نقطة نهاية AJAX (تعيد JSON)
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $k = '%' . $keyword . '%';
    $stmt = $conn->prepare("SELECT id, name, university, specialization, phone, cv_file FROM users WHERE user_type='graduate' AND email NOT IN ('haroonhatem34@gmail.com','hamzahmisr@gmail.com') AND (name LIKE ? OR university LIKE ? OR specialization LIKE ?) ORDER BY created_at DESC");
    $stmt->bind_param('sss', $k, $k, $k);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit();
}

// Get user's applications
$stmt_apps = $conn->prepare('
    SELECT a.*, j.title as job_title, u.name as company_name, 
           cc.id as conversation_id,
           (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id = cc.id AND cm.sender_id != ? AND cm.is_read = 0) as unread_count
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.company_id = u.id
    LEFT JOIN chat_conversations cc ON a.job_id = cc.job_id AND a.user_id = cc.graduate_id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
');
$stmt_apps->bind_param('ii', $user_id, $user_id);
$stmt_apps->execute();
$user_applications = $stmt_apps->get_result();

// عرض الصفحة (غير AJAX)
$res = $conn->query("SELECT id, name, university, specialization, phone, cv_file FROM users WHERE user_type='graduate' AND email NOT IN ('haroonhatem34@gmail.com','hamzahmisr@gmail.com') ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>بحث عن خريجين</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<main class="container">
  <!-- Welcome Message -->
  <div class="card welcome-message">
    <h1>مرحباً <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
    <p>مرحباً بك في لوحة الخريج. يمكنك هنا إدارة طلبات التوظيف والبحث عن الوظائف المناسبة لك.</p>
  </div>
  
  <?php if (isset($_SESSION['message'])): ?>
    <div class="card success-message">
      <p><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
    </div>
  <?php endif; ?>
  
  <!-- Verification Status Card -->
  <div class="card verification-status">
    <h2>حالة التحقق من الهوية</h2>
    <?php if ($user_data['is_verified']): ?>
      <div class="status-verified">
        <span class="status-icon">✓</span>
        <p>تم التحقق من هويتك بنجاح</p>
      </div>
    <?php elseif ($user_data['verification_status'] === 'pending'): ?>
      <div class="status-pending">
        <span class="status-icon">⏳</span>
        <p>طلب التحقق من الهوية قيد المراجعة</p>
      </div>
    <?php elseif ($user_data['verification_status'] === 'rejected'): ?>
      <div class="status-rejected">
        <span class="status-icon">✗</span>
        <p>تم رفض طلب التحقق من الهوية. يرجى التواصل مع الإدارة</p>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- User Applications Section -->
  <div class="card">
    <h2>طلبات التوظيف الخاصة بي</h2>
    <?php if ($user_applications->num_rows > 0): ?>
      <table class="table">
        <tr>
          <th>الوظيفة</th>
          <th>الشركة</th>
          <th>الحالة</th>
          <th>تاريخ التقديم</th>
          <th>المحادثة</th>
        </tr>
        <?php while ($app = $user_applications->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($app['job_title']); ?></td>
            <td><?php echo htmlspecialchars($app['company_name']); ?></td>
            <td>
              <span class="status-badge status-<?php echo $app['status']; ?>">
                <?php 
                switch($app['status']) {
                  case 'pending': echo 'معلق'; break;
                  case 'accepted': echo 'مقبول'; break;
                  case 'rejected': echo 'مرفوض'; break;
                }
                ?>
              </span>
            </td>
            <td><?php echo date('Y-m-d', strtotime($app['applied_at'])); ?></td>
            <td>
              <?php if ($app['conversation_id']): ?>
                <a href="chat.php?conversation_id=<?php echo $app['conversation_id']; ?>" class="btn btn-apply">
                  محادثة
                  <?php if ($app['unread_count'] > 0): ?>
                    <span style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; margin-right: 5px;">
                      <?php echo $app['unread_count']; ?>
                    </span>
                  <?php endif; ?>
                </a>
              <?php else: ?>
                <span style="color: #666;">لم تبدأ بعد</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>
    <?php else: ?>
      <p>لم تقدم على أي وظائف بعد. <a href="search_jobs.php">ابحث عن وظائف</a></p>
    <?php endif; ?>
  </div>
  
  <div class="card">
    <h2>بحث عن خريجين</h2>
    <div class="search-form">
      <input id="grad-search" class="input" placeholder="ابحث باسم الخريج أو الجامعة أو التخصص">
      <button id="clear-grad" class="btn">مسح</button>
    </div>
    <div id="grads-list">
      <?php if ($res && $res->num_rows > 0): ?>
        <?php while ($r = $res->fetch_assoc()): ?>
          <div class="grad-card card">
            <h3><?php echo htmlspecialchars($r['name']); ?></h3>
            <div class="meta"><?php echo htmlspecialchars($r['university']) . ' • ' . htmlspecialchars($r['specialization']); ?></div>
            <p>الهاتف: <?php echo htmlspecialchars($r['phone']); ?></p>
            <?php if (!empty($r['cv_file'])): ?>
              <a class="btn btn-apply" href="<?php echo htmlspecialchars($r['cv_file']); ?>" target="_blank">عرض السيرة (ملف)</a>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>لا توجد خريجين.</p>
      <?php endif; ?>
    </div>
  </div>
</main>
<script src="assets/js/script.js"></script>
</body>
</html>

