<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $phone = trim($_POST['phone']);
  $company_location = trim($_POST['company_location']);
  $website = trim($_POST['website']);
  $user_type = 'company';
  $commercial_register_file = null;
  
  // Handle commercial register file upload (required for verification)
  if (!empty($_FILES['commercial_register']['name'])) {
    if ($_FILES['commercial_register']['size'] > 10 * 1024 * 1024) {
      $error = 'حجم السجل التجاري أكبر من 10MB';
    } else {
      $ext = strtolower(pathinfo($_FILES['commercial_register']['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
      if (in_array($ext, $allowed)) {
        if (!is_dir('uploads/company_docs')) mkdir('uploads/company_docs', 0755, true);
        $fname = uniqid('register_') . '.' . $ext;
        $target = 'uploads/company_docs/' . $fname;
        if (move_uploaded_file($_FILES['commercial_register']['tmp_name'], $target)) $commercial_register_file = $target;
      } else $error = 'نوع ملف السجل التجاري غير مدعوم (pdf, jpg, png فقط)';
    }
  } else {
    $error = 'يجب رفع السجل التجاري للتحقق من الهوية';
  }
  
  if (empty($error)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) $error = 'البريد مستخدم سابقاً';
    else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt2 = $conn->prepare('INSERT INTO users (name,email,password,user_type,phone,company_location,website,is_verified,verification_status) VALUES (?,?,?,?,?,?,?,?,?)');
      // Auto-verify admin accounts
      if (in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com'])) {
          $is_verified = 1;
          $verification_status = 'approved';
      } else {
          $is_verified = 0;
          $verification_status = 'pending';
      }
      $stmt2->bind_param('sssssssis', $name, $email, $hash, $user_type, $phone, $company_location, $website, $is_verified, $verification_status);
      if ($stmt2->execute()) {
        $user_id = $stmt2->insert_id;
        
        // Create verification request only for non-admin users
        if (!in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com'])) {
            $stmt3 = $conn->prepare('INSERT INTO company_verification_requests (user_id, company_name, email, phone, company_location, website, commercial_register_file) VALUES (?,?,?,?,?,?,?)');
            $stmt3->bind_param('issssss', $user_id, $name, $email, $phone, $company_location, $website, $commercial_register_file);
            $stmt3->execute();
        }
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_type'] = $user_type;
        $_SESSION['user_email'] = $email;
        $_SESSION['is_admin'] = in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com']);
        if (in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com'])) {
            $_SESSION['message'] = 'تم إنشاء الحساب بنجاح. حسابك محقق تلقائياً كمسؤول.';
        } else {
            $_SESSION['message'] = 'تم إنشاء الحساب بنجاح. سيتم مراجعة طلب التحقق من الهوية من قبل الإدارة.';
        }
        header('Location: employer_dashboard.php');
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
  <title>تسجيل شركة</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body><?php include 'navbar.php'; ?>
  <main class="container">
    <div class="card form-card">
      <h2>تسجيل شركة</h2><?php if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="form-grid"><input class="input" name="name" placeholder="اسم الشركة" required><input class="input" name="email" type="email" placeholder="البريد الإلكتروني" required></div>
        <div class="form-grid"><input class="input" name="password" type="password" placeholder="كلمة المرور" required><input class="input" name="phone" placeholder="هاتف الشركة" required></div>
        <div class="form-grid"><input class="input" name="company_location" placeholder="موقع الشركة (البلد)" required><input class="input" name="website" placeholder="الموقع الإلكتروني (اختياري)"></div>
        
        <h3>التحقق من الهوية</h3>
        <p class="info-text">لضمان صحة البيانات، يرجى رفع السجل التجاري للتحقق من الهوية</p>
        
        <label>السجل التجاري (مطلوب) - pdf, jpg, png حتى 10MB</label>
        <input type="file" name="commercial_register" accept=".pdf,.jpg,.jpeg,.png" required>
        
        <button class="btn btn-primary" type="submit">إنشاء حساب شركة</button>
      </form>
    </div>
  </main>
</body>

</html>