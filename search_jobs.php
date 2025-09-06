<?php
session_start();
include 'db.php';
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
  $k = '%' . $keyword . '%';
  $stmt = $conn->prepare("SELECT jobs.*, users.name AS company_name FROM jobs JOIN users ON jobs.company_id=users.id WHERE jobs.title LIKE ? OR jobs.description LIKE ? OR jobs.location LIKE ? OR users.name LIKE ? ORDER BY jobs.created_at DESC");
  $stmt->bind_param('ssss', $k, $k, $k, $k);
  $stmt->execute();
  $res = $stmt->get_result();
  $items = [];
  while ($r = $res->fetch_assoc()) {
    $items[] = ['id' => $r['id'], 'title' => $r['title'], 'company' => $r['company_name'], 'location' => $r['location'], 'description' => $r['description']];
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($items);
  exit();
}
$sql = "SELECT jobs.*, users.name AS company_name FROM jobs JOIN users ON jobs.company_id=users.id ORDER BY jobs.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang='ar' dir='rtl'>

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
      <div class='search-form'><input id='job-search' class='input' placeholder='ابحث عن عنوان أو شركة أو موقع'>
      <button id='clear-search' class='btn'>مسح</button></div>
      <div id='jobs-list'><?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?><div class='job-card card'>
              <h3><?php echo htmlspecialchars($row['title']); ?></h3>
              <div class='meta'><?php echo htmlspecialchars($row['company_name']); ?> • <?php echo htmlspecialchars($row['location']); ?></div>
              <p><?php echo nl2br(htmlspecialchars($row['description'])); ?>
            </p><?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'graduate'): ?>
              <a class='btn btn-apply' href='apply.php?job_id=<?php echo $row['id']; ?>'>قدم الآن</a>
              <?php else: ?><a class='btn' href='login.php'>دخول للتقديم</a><?php endif; ?>
            </div><?php endwhile;
                          else: ?><p>لا توجد وظائف.</p><?php endif; ?></div>
    </div>
  </main>
  <script src='assets/js/script.js'></script>
</body>

</html>