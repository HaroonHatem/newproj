<?php
session_start();
include 'db.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// نقطة نهاية AJAX (تعيد JSON)
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $k = '%' . $keyword . '%';
    $stmt = $conn->prepare("SELECT id, name, university, specialization, phone, cv_file FROM users WHERE user_type='graduate' AND (name LIKE ? OR university LIKE ? OR specialization LIKE ?) ORDER BY created_at DESC");
    $stmt->bind_param('sss', $k, $k, $k);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[] = $r;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    echo json_encode($out);
    exit();
}

// عرض الصفحة (غير AJAX)
$res = $conn->query("SELECT id, name, university, specialization, phone, cv_file FROM users WHERE user_type='graduate' ORDER BY created_at DESC");
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

