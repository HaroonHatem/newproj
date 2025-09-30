<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $handled = false;
  $adminCandidate = false;

  $adminStmt = $conn->prepare('SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1');
  if ($adminStmt) {
    $adminStmt->bind_param('s', $email);
    if ($adminStmt->execute()) {
      $adminRes = $adminStmt->get_result();
      if ($adminRes && $adminRes->num_rows === 1) {
        $adminCandidate = true;
        $admin = $adminRes->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
          $_SESSION['admin_id'] = (int)$admin['id'];
          $_SESSION['user_id'] = (int)$admin['id'];
          $_SESSION['user_name'] = $admin['name'];
          $_SESSION['user_email'] = $admin['email'];
          $_SESSION['user_type'] = 'admin';
          $_SESSION['is_admin'] = 1;
          header('Location: admin_dashboard.php');
          exit();
        } else {
          $error = '��� ����� �� ��率';
          $handled = true;
        }
      }
    }
    $adminStmt->close();
  }

  if (!$handled && !$adminCandidate) {
    $rm = $conn->prepare('SELECT reason, removed_at FROM account_removals WHERE removed_user_email = ? ORDER BY removed_at DESC LIMIT 1');
    $rm->bind_param('s', $email);
    $rm->execute();
    $rmr = $rm->get_result();
    if ($rmr && $rmr->num_rows > 0) {
      $row = $rmr->fetch_assoc();
      $error = '�� ���� ����� �� �� �靧���. �髠�: ' . ($row['reason'] ?: '� ���� �� ��頟� ���뭡') . ' (����: ' . $row['removed_at'] . ')';
      $handled = true;
    }
  }

  if (!$handled && !$adminCandidate) {
    $stmt = $conn->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
      $user = $res->fetch_assoc();
      if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = 0;
        unset($_SESSION['admin_id']);
        if ($user['user_type'] === 'graduate') header('Location: graduate_dashboard.php');
        else header('Location: index.php');
        exit();
      } else $error = '��� ����� �� ��率';
    } else $error = '�頩� �� ꫤ�';
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="utf-8">
  <title>����� ����</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body><?php include 'navbar.php'; ?>
  <main class="container">
    <div class="card form-card">
      <h2>����� ����</h2><?php 
        if (isset($_GET['removed']) && $_GET['removed'] == '1') {
          echo '<p class="error">�� ����� ����� �� ����� �� �� ꢟ���.</p>';
        }
        if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; 
      ?>
      <div style="margin-bottom:10px;">
        <button class="btn" type="button" onclick="if(document.referrer){history.back();}else{window.location.href='index.php';}"><- ����</button>
      </div>
      <form method="post" autocomplete="off">
        <label>�頩� ���袩���</label>
        <input class="input" type="email" name="email" required autocomplete="off" value="<?php echo isset($email) && !empty($error) ? htmlspecialchars($email) : ''; ?>">
        <label>��� �����</label>
        <input class="input" type="password" name="password" required autocomplete="new-password">
        <button class="btn btn-primary" type="submit">����</button></form>
    </div>
  </main>
</body>

</html>
