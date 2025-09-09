<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $university = trim($_POST['university']);
  $specialization = trim($_POST['specialization']);
  $phone = trim($_POST['phone']);
  $cv_link = trim($_POST['cv_link']);
  $user_type = 'graduate';
  $cv_file = null;
  if (!empty($_FILES['cv']['name'])) {
    if ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
      $error = 'حجم الملف أكبر من 5MB';
    } else {
      $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf', 'doc', 'docx'];
      if (in_array($ext, $allowed)) {
        if (!is_dir('uploads')) mkdir('uploads', 0755, true);
        $fname = uniqid('cv_') . '.' . $ext;
        $target = 'uploads/' . $fname;
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $target)) $cv_file = $target;
      } else $error = 'نوع الملف غير مدعوم';
    }
  }
  if (empty($error)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) $error = 'البريد مستخدم سابقاً';
    else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt2 = $conn->prepare('INSERT INTO users (name,email,password,user_type,university,specialization,phone,cv_file,cv_link) VALUES (?,?,?,?,?,?,?,?,?)');
      $stmt2->bind_param('sssssssss', $name, $email, $hash, $user_type, $university, $specialization, $phone, $cv_file, $cv_link);
      if ($stmt2->execute()) {
        $_SESSION['user_id'] = $stmt2->insert_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_type'] = $user_type;
        $_SESSION['user_email'] = $email;
        $_SESSION['is_admin'] = in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com']);
        header('Location: graduate_dashboard.php');
        exit();
      } else $error = 'خطأ أثناء التسجيل';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="utf-8">
  <title>تسجيل خريج</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body><?php include 'navbar.php'; ?>
  <main class="container">
    <div class="card form-card">
      <h2>تسجيل خريج</h2>
      <?php if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
          <input class="input" name="name" placeholder="الاسم الكامل" required>
          <input class="input" name="email" type="email" placeholder="البريد الإلكتروني" required>
        </div>
        <div class="form-grid">
          <input class="input" name="password" type="password" placeholder="كلمة المرور" required>
          <input class="input" name="phone" placeholder="الهاتف">
        </div>
        <div class="form-grid"><input class="input" name="university" placeholder="الجامعة">
        <input class="input" name="specialization" placeholder="التخصص">
      </div>
      <label>رابط السيرة (اختياري)</label>
      <input class="input" name="cv_link" placeholder="رابط السيرة (Google Drive أو رابط مباشر)">
      <label>أو رفع السيرة (pdf/doc/docx) حتى 5MB</label>
      <input type="file" name="cv" accept=".pdf,.doc,.docx">
      <button class="btn btn-primary" type="submit">إنشاء الحساب</button>
      </form>
    </div>
  </main>
</body>

</html>