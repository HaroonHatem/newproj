<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $university = trim($_POST['university']);
  $specialization = trim($_POST['specialization']);
  // Normalize and validate Yemen phone: +967 + 9 digits, starting with 70/71/73/77/78
  $raw_phone = isset($_POST['phone']) ? preg_replace('/\D+/', '', $_POST['phone']) : '';
  if (strlen($raw_phone) === 12 && substr($raw_phone, 0, 3) === '967') {
    $raw_phone = substr($raw_phone, 3);
  }
  if (!preg_match('/^(70|71|73|77|78)\d{7}$/', $raw_phone)) {
    $error = 'رقم الهاتف اليمني يجب أن يكون 9 أرقام ويبدأ بـ 70 أو 71 أو 73 أو 77 أو 78.';
  }
  $phone = '+967' . $raw_phone;
  $cv_link = trim($_POST['cv_link']);
  $user_type = 'graduate';
  $cv_file = null;
  $certificate_file = null;
  
  // Handle CV file upload
  if (empty($error) && !empty($_FILES['cv']['name'])) {
    if ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
      $error = 'حجم الملف أكبر من 5MB';
    } else {
      $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf', 'doc', 'docx'];
      if (in_array($ext, $allowed)) {
        if (!is_dir('uploads')) mkdir('uploads', 0755, true);
        $fname = uniqid('cv_') . '.' . $ext;
        $target = 'uploads/' . $fname;
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $target)) $cv_file = $target; else $error = 'تعذر رفع ملف السيرة';
      } else $error = 'نوع الملف غير مدعوم';
    }
  }
  
  // Handle certificate file upload (required for verification)
  if (empty($error) && !empty($_FILES['certificate']['name'])) {
    if ($_FILES['certificate']['size'] > 10 * 1024 * 1024) {
      $error = 'حجم شهادة التخرج أكبر من 10MB';
    } else {
      $ext = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
      if (in_array($ext, $allowed)) {
        if (!is_dir('uploads/certificates')) mkdir('uploads/certificates', 0755, true);
        $fname = uniqid('cert_') . '.' . $ext;
        $target = 'uploads/certificates/' . $fname;
        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $target)) $certificate_file = $target; else $error = 'تعذر رفع شهادة التخرج';
      } else $error = 'نوع ملف الشهادة غير مدعوم (pdf, jpg, png فقط)';
    }
  } else if (empty($error)) {
    $error = 'يجب رفع شهادة التخرج للتحقق من الهوية';
  }
  
  if (empty($error)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) $error = 'البريد مستخدم سابقاً';
    else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt2 = $conn->prepare('INSERT INTO users (name,email,password,user_type,university,specialization,phone,cv_file,cv_link,is_verified,verification_status) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
      // Auto-verify admin accounts
      if (in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com'])) {
          $is_verified = 1;
          $verification_status = 'approved';
      } else {
          $is_verified = 0;
          $verification_status = 'pending';
      }
      $stmt2->bind_param('sssssssssis', $name, $email, $hash, $user_type, $university, $specialization, $phone, $cv_file, $cv_link, $is_verified, $verification_status);
      if ($stmt2->execute()) {
        $user_id = $stmt2->insert_id;
        
        // Create verification request only for non-admin users
        if (!in_array(strtolower($email), ['haroonhatem34@gmail.com','hamzahmisr@gmail.com'])) {
            $stmt3 = $conn->prepare('INSERT INTO graduate_verification_requests (user_id, full_name, email, phone, university, field_of_study, certificate_file) VALUES (?,?,?,?,?,?,?)');
            $stmt3->bind_param('issssss', $user_id, $name, $email, $phone, $university, $specialization, $certificate_file);
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
      <div style="margin-bottom:10px;">
        <button class="btn" type="button" onclick="if(document.referrer){history.back();}else{window.location.href='index.php';}">عودة</button>
      </div>
      <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
          <input class="input" name="name" placeholder="الاسم الكامل" required>
          <input class="input" name="email" type="email" placeholder="البريد الإلكتروني" required>
        </div>
        <div class="form-grid">
          <input class="input" name="password" type="password" placeholder="كلمة المرور" required>
          <div>
            <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">الهاتف (اليمن)</label>
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="display:flex; align-items:center; gap:6px; background:#f6f6f6; border:1px solid #ddd; padding:8px 10px; border-radius:6px;">
                <span>🇾🇪</span>
                <span style="direction:ltr;">+967</span>
              </span>
              <input class="input" name="phone" placeholder="xxxxxxxxx" inputmode="numeric" pattern="(70|71|73|77|78)[0-9]{7}" title="9 أرقام تبدأ بـ 70 أو 71 أو 73 أو 77 أو 78" maxlength="9" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,9);">
            </div>
          </div>
        </div>
        <div class="form-grid"><input class="input" name="university" placeholder="الجامعة">
        <input class="input" name="specialization" placeholder="التخصص">
      </div>
      <label>رابط السيرة (اختياري)</label>
      <input class="input" name="cv_link" placeholder="رابط السيرة (Google Drive أو رابط مباشر)">
      <label>أو رفع السيرة (pdf/doc/docx) حتى 5MB</label>
      <input type="file" name="cv" accept=".pdf,.doc,.docx">
      
      <h3>التحقق من الهوية</h3>
      <p class="info-text">لضمان صحة البيانات، يرجى رفع شهادة التخرج للتحقق من الهوية</p>
      <label>شهادة التخرج (مطلوب) - pdf, jpg, png حتى 10MB</label>
      <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required>
      
      <button class="btn btn-primary" type="submit">إنشاء الحساب</button>
      </form>
    </div>
  </main>
</body>

</html>