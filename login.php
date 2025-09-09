<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];
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
      if ($user['user_type'] === 'graduate') header('Location: graduate_dashboard.php');
      else header('Location: index.php');
      exit();
    } else $error = 'كلمة المرور غير صحيحة';
  } else $error = 'البريد غير مسجل';
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
      <h2>تسجيل دخول</h2><?php if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
      <form method="post">
        <label>البريد الإلكتروني</label>
        <input class="input" type="email" name="email" required>
        <label>كلمة المرور</label>
        <input class="input" type="password" name="password" required>
        <button class="btn btn-primary" type="submit">دخول</button></form>
    </div>
  </main>
</body>

</html>