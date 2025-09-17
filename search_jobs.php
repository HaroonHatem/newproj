<?php
session_start();
include 'db.php';
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// Helper: get current graduate verification
function get_current_graduate_verification($conn){
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'graduate') return [false, ''];
    $uid = $_SESSION['user_id'];
    $s = $conn->prepare('SELECT is_verified, verification_status FROM users WHERE id=? LIMIT 1');
    $s->bind_param('i', $uid);
    $s->execute();
    $d = $s->get_result()->fetch_assoc();
    if (!$d) return [false, ''];
    return [ (int)$d['is_verified'] === 1 && $d['verification_status'] === 'approved', $d['verification_status'] ];
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
  $k = '%' . $keyword . '%';
  list($can_apply, $vstatus) = get_current_graduate_verification($conn);
  
  // Check if user is logged in as graduate
  if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'graduate') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT jobs.*, users.name AS company_name, 
            CASE WHEN applications.id IS NOT NULL THEN 1 ELSE 0 END as has_applied
            FROM jobs 
            JOIN users ON jobs.company_id=users.id 
            LEFT JOIN applications ON jobs.id = applications.job_id AND applications.user_id = ?
            WHERE jobs.title LIKE ? OR jobs.description LIKE ? OR jobs.location LIKE ? OR users.name LIKE ? 
            ORDER BY jobs.created_at DESC");
    $stmt->bind_param('issss', $user_id, $k, $k, $k, $k);
  } else {
    $stmt = $conn->prepare("SELECT jobs.*, users.name AS company_name FROM jobs JOIN users ON jobs.company_id=users.id WHERE jobs.title LIKE ? OR jobs.description LIKE ? OR jobs.location LIKE ? OR users.name LIKE ? ORDER BY jobs.created_at DESC");
    $stmt->bind_param('ssss', $k, $k, $k, $k);
  }
  
  $stmt->execute();
  $res = $stmt->get_result();
  $items = [];
  while ($r = $res->fetch_assoc()) {
    $item = ['id' => $r['id'], 'title' => $r['title'], 'company' => $r['company_name'], 'location' => $r['location'], 'description' => $r['description']];
    if (isset($r['has_applied'])) {
      $item['has_applied'] = (int)$r['has_applied'];
    }
    // expose whether this user is allowed to apply
    $item['can_apply'] = $can_apply ? 1 : 0;
    $items[] = $item;
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($items);
  exit();
}

// Get jobs with application status for logged-in graduates
list($can_apply_page, $vstatus_page) = get_current_graduate_verification($conn);
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'graduate') {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT jobs.*, users.name AS company_name, 
            CASE WHEN applications.id IS NOT NULL THEN 1 ELSE 0 END as has_applied
            FROM jobs 
            JOIN users ON jobs.company_id=users.id 
            LEFT JOIN applications ON jobs.id = applications.job_id AND applications.user_id = ?
            ORDER BY jobs.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT jobs.*, users.name AS company_name FROM jobs JOIN users ON jobs.company_id=users.id ORDER BY jobs.created_at DESC";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang='ار' dir='rtl'>

<head>
  <meta charset='utf-8'>
  <title>بحث عن وظائف</title>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <link rel='stylesheet' href='assets/css/style.css'>
</head>

<body><?php include 'navbar.php'; ?>
  <main class='container'>
    <div class='card'>
      <h2>بحث عن وظائف</h2>
      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'graduate' && !$can_apply_page): ?>
        <div class="card" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;margin-bottom:15px;">
          <p>لا يمكنك التقديم على الوظائف حتى يتم التحقق من هويتك والموافقة عليها.</p>
        </div>
      <?php endif; ?>
      <div class='search-form'><input id='job-search' class='input' placeholder='ابحث عن عنوان أو شركة أو موقع'>
      <button id='clear-search' class='btn'>مسح</button></div>
      <div id='jobs-list'><?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?><div class='job-card card'>
              <h3><?php echo htmlspecialchars($row['title']); ?></h3>
              <div class='meta'><?php echo htmlspecialchars($row['company_name']); ?> • <?php echo htmlspecialchars($row['location']); ?></div>
              <p><?php echo nl2br(htmlspecialchars($row['description'])); ?>
            </p><?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'graduate'): ?>
              <?php if (isset($row['has_applied']) && $row['has_applied']): ?>
                <div class="application-status">
                  <span class="status-badge applied">✓ تم التقديم</span>
                  <p style="color: #666; font-size: 14px; margin: 5px 0;">لقد قدمت لهذه الوظيفة من قبل</p>
                </div>
              <?php else: ?>
                <?php if ($can_apply_page): ?>
                  <a class='btn btn-apply' href='apply.php?job_id=<?php echo $row['id']; ?>'>قدم الآن</a>
                <?php else: ?>
                  <button class='btn' disabled title='بانتظار التحقق'>بانتظار التحقق</button>
                <?php endif; ?>
              <?php endif; ?>
              <?php else: ?><a class='btn' href='login.php'>دخول للتقديم</a><?php endif; ?>
            </div><?php endwhile;
                          else: ?><p>لا توجد وظائف.</p><?php endif; ?></div>
    </div>
  </main>
  <script>
    window.USER_TYPE = '<?php echo isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'guest'; ?>';
    window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    window.CAN_APPLY = <?php echo (isset($_SESSION['user_id']) && $_SESSION['user_type']==='graduate' && $can_apply_page) ? 'true' : 'false'; ?>;
  </script>
  <script src='assets/js/script.js'></script>
</body>

</html>