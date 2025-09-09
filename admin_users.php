<?php
session_start();
include 'db.php';
if (empty($_SESSION['is_admin'])) { header('Location: index.php'); exit(); }

// Delete action (POST only, CSRF minimal via session check)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_user_id'])) {
  $deleteId = intval($_POST['delete_user_id']);
  if ($deleteId === $_SESSION['user_id']) {
    $_SESSION['message'] = 'لا يمكنك حذف نفسك.';
  } else {
    // Block deletion of site owners by email
    $stmtChk = $conn->prepare('SELECT email FROM users WHERE id=? LIMIT 1');
    $stmtChk->bind_param('i', $deleteId);
    $stmtChk->execute();
    $resChk = $stmtChk->get_result();
    $rowChk = $resChk ? $resChk->fetch_assoc() : null;
    $emailChk = $rowChk ? strtolower($rowChk['email']) : '';
    $owners = ['haroonhatem34@gmail.com','hamzahmisr@gmail.com'];
    if (in_array($emailChk, $owners, true)) {
      $_SESSION['message'] = 'لا يمكن حذف مالكي الموقع.';
    } else {
      $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
      $stmt->bind_param('i', $deleteId);
      if ($stmt->execute()) $_SESSION['message'] = 'تم حذف المستخدم.'; else $_SESSION['message'] = 'تعذر الحذف.';
    }
  }
  header('Location: admin_users.php');
  exit();
}

// Do not list site owners in the admin list
$res = $conn->query("SELECT id, name, email, user_type, created_at FROM users WHERE LOWER(email) NOT IN ('haroonhatem34@gmail.com','hamzahmisr@gmail.com') ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>الإدارة - المستخدمون</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .danger { background:#e74c3c; color:#fff; padding:5px 10px; border-radius:5px; border:none; cursor:pointer; }
  </style>
  </head>
<body>
<?php include 'navbar.php'; ?>
<main class="container">
  <div class="card">
    <h2>إدارة المستخدمين</h2>
    <?php if(isset($_SESSION['message'])){ echo '<p class="success">'.htmlspecialchars($_SESSION['message']).'</p>'; unset($_SESSION['message']); } ?>
    <?php if ($res && $res->num_rows>0): ?>
    <table class="table">
      <tr>
        <th>#</th>
        <th>الاسم</th>
        <th>البريد</th>
        <th>النوع</th>
        <th>تاريخ</th>
        <th>إجراء</th>
      </tr>
      <?php while($u=$res->fetch_assoc()): ?>
      <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['name']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo $u['user_type']==='company'?'شركة':'خريج'; ?></td>
        <td><?php echo $u['created_at']; ?></td>
        <td>
          <form method="post" onsubmit="return confirm('هل تريد حذف هذا المستخدم؟');">
            <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
            <button class="danger" type="submit">حذف</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    <?php else: ?><p>لا يوجد مستخدمون.</p><?php endif; ?>
  </div>
</main>
</body>
</html>


