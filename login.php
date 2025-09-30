<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  // Check if this email was removed by admin
  $rm = $conn->prepare('SELECT reason, removed_at FROM account_removals WHERE removed_user_email = ? ORDER BY removed_at DESC LIMIT 1');
  $rm->bind_param('s', $email);
  $rm->execute();
  $rmr = $rm->get_result();
  if ($rmr && $rmr->num_rows > 0) {
    $row = $rmr->fetch_assoc();
    $error = 'تمت إزالة حسابك من قبل الإدارة. السبب: ' . ($row['reason'] ?: 'لا يتوافق مع متطلبات المنصة') . ' (تاريخ: ' . $row['removed_at'] . ')';
  } else {
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
        $_SESSION['is_admin'] = in_array(strtolower($user['email']), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com']);
        if (!empty($_SESSION['is_admin'])) header('Location: admin_dashboard.php');
        else if ($user['user_type'] === 'graduate') header('Location: graduate_dashboard.php');
        else header('Location: index.php');
        exit();
      } else $error = 'كلمة المرور غير صحيحة';
    } else $error = 'البريد غير مسجل';
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="utf-8">
  <title>تسجيل دخول</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body><?php include 'navbar.php'; ?>
  <main class="container">
    <div class="card form-card">
      <h2>تسجيل دخول</h2><?php 
        if (isset($_GET['removed']) && $_GET['removed'] == '1') {
          echo '<p class="error">تم تسجيل خروجك لأن حسابك لم يعد متاحاً.</p>';
        }
        if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; 
      ?>
      <div style="margin-bottom:10px;">
        <button class="btn" type="button" onclick="if(document.referrer){history.back();}else{window.location.href='index.php';}"><- عودة</button>
      </div>
      <form method="post" autocomplete="off">
        <label>البريد الإلكتروني</label>
        <input class="input" type="email" name="email" required autocomplete="off" value="<?php echo isset($email) && !empty($error) ? htmlspecialchars($email) : ''; ?>">
        <label>كلمة المرور</label>
        <input class="input" type="password" name="password" required autocomplete="new-password">
        <button class="btn btn-primary" type="submit">دخول</button></form>
    </div>
  </main>
</body>

</html>