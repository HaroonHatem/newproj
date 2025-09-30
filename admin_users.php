<?php
session_start();
include 'db.php';
if (empty($_SESSION['is_admin'])) { header('Location: index.php'); exit(); }

$currentAdminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0);

// Delete action (POST only, CSRF minimal via session check)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_user_id'])) {
  $deleteId = intval($_POST['delete_user_id']);
  if ($deleteId === $currentAdminId) {
    $_SESSION['message'] = '� ����� ��� ���.';
  } else {
    $stmtChk = $conn->prepare('SELECT id, name, email, user_type, cv_file FROM users WHERE id=? LIMIT 1');
    $stmtChk->bind_param('i', $deleteId);
    $stmtChk->execute();
    $resChk = $stmtChk->get_result();
    $userRow = $resChk ? $resChk->fetch_assoc() : null;
    $stmtChk->close();

    if (!$userRow) {
      $_SESSION['message'] = '��ꫢ��� �� �����.';
    } else {
      // Do not allow deleting accounts that are registered as admins
      $adminGuard = $conn->prepare('SELECT id FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1');
      if ($adminGuard) {
        $adminGuard->bind_param('s', $userRow['email']);
        $adminGuard->execute();
        $adminRes = $adminGuard->get_result();
        if ($adminRes && $adminRes->num_rows > 0) {
          $_SESSION['message'] = '� ���� ��� ���� ������.';
          $adminGuard->close();
          header('Location: admin_users.php');
          exit();
        }
        $adminGuard->close();
      }

      $conn->begin_transaction();
      try {
        if ($userRow['user_type'] === 'graduate') {
          if (!empty($userRow['cv_file']) && file_exists($userRow['cv_file'])) { @unlink($userRow['cv_file']); }
          $gvs = $conn->prepare('SELECT certificate_file FROM graduate_verification_requests WHERE user_id=?');
          $gvs->bind_param('i', $deleteId);
          $gvs->execute();
          $gvr = $gvs->get_result();
          while ($g = $gvr->fetch_assoc()) {
            if (!empty($g['certificate_file']) && file_exists($g['certificate_file'])) { @unlink($g['certificate_file']); }
          }
          $gvs->close();
          $conn->query('DELETE FROM graduate_verification_requests WHERE user_id='.(int)$deleteId);
        }

        if ($userRow['user_type'] === 'company') {
          $cvs = $conn->prepare('SELECT commercial_register_file FROM company_verification_requests WHERE user_id=?');
          $cvs->bind_param('i', $deleteId);
          $cvs->execute();
          $cvr = $cvs->get_result();
          while ($c = $cvr->fetch_assoc()) {
            if (!empty($c['commercial_register_file']) && file_exists($c['commercial_register_file'])) { @unlink($c['commercial_register_file']); }
          }
          $cvs->close();
          $conn->query('DELETE FROM company_verification_requests WHERE user_id='.(int)$deleteId);
        }

        $reason = '�� ���� �饫�� �� �� �靧��� ��� ����埘 ��頟� ���뭡.';
        $log = $conn->prepare('INSERT INTO account_removals (removed_user_email, removed_user_name, removed_user_type, reason, removed_by) VALUES (?,?,?,?,?)');
        $log->bind_param('ssssi', $userRow['email'], $userRow['name'], $userRow['user_type'], $reason, $currentAdminId);
        $log->execute();
        $log->close();

        $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
        $stmt->bind_param('i', $deleteId);
        if ($stmt->execute()) {
          $conn->commit();
          $_SESSION['message'] = '�� ��� ��ꫢ��� ����� ����� ��ꩢ��.';
        } else {
          $conn->rollback();
          $_SESSION['message'] = '�㨩 �饨�.';
        }
        $stmt->close();
      } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = '�� ��럘 �饨�: '.$e->getMessage();
      }
    }
  }
  header('Location: admin_users.php');
  exit();
}

$res = $conn->query("SELECT u.id, u.name, u.email, u.user_type, u.created_at
FROM users u
LEFT JOIN admins a ON LOWER(u.email) = LOWER(a.email)
WHERE a.id IS NULL
ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>�靧��� - ��ꫢ�����</title>
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
    <h2>����� ��ꫢ�����</h2>
    <?php if(isset($_SESSION['message'])){ echo '<p class="success">'.htmlspecialchars($_SESSION['message']).'</p>'; unset($_SESSION['message']); } ?>
    <?php if ($res && $res->num_rows>0): ?>
    <table class="table">
      <tr>
        <th>#</th>
        <th>�韫�</th>
        <th>�頩�</th>
        <th>�����</th>
        <th>����</th>
        <th>�����</th>
      </tr>
      <?php while($u=$res->fetch_assoc()): ?>
      <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['name']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo $u['user_type']==='company'?'���':'���'; ?></td>
        <td><?php echo $u['created_at']; ?></td>
        <td>
          <form method="post" onsubmit="return confirm('�� ��� ��� 쨟 ��ꫢ���?');">
            <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
            <button class="danger" type="submit">���</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
    <?php else: ?><p>� ���� ꫢ�����.</p><?php endif; ?>
  </div>
</main>
</body>
</html>
