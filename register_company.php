<?php
session_start();
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  // Normalize and validate Yemen phone: +967 + 9 digits, starting with 70/71/73/77/78
  $raw_phone = isset($_POST['phone']) ? preg_replace('/\D+/', '', $_POST['phone']) : '';
  if (strlen($raw_phone) === 12 && substr($raw_phone, 0, 3) === '967') {
    $raw_phone = substr($raw_phone, 3);
  }
  if (!preg_match('/^(70|71|73|77|78)\d{7}$/', $raw_phone)) {
    $error = '��� ��쟢� ������ 鸞 �� ���� 9 ���� �� �� 70 �� 71 �� 73 �� 77 �� 78.';
  }
  $phone = '+967' . $raw_phone;
  $company_location = trim($_POST['company_location']);
  $website = trim($_POST['website']);
  $user_type = 'company';
  $commercial_register_file = null;
  
  // Handle commercial register file upload (required for verification)
  if (empty($error) && !empty($_FILES['commercial_register']['name'])) {
    if ($_FILES['commercial_register']['size'] > 10 * 1024 * 1024) {
      $error = '��� �髤� �颤��� �蠩 �� 10MB';
    } else {
      $ext = strtolower(pathinfo($_FILES['commercial_register']['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
      if (in_array($ext, $allowed)) {
        if (!is_dir('uploads/company_docs')) mkdir('uploads/company_docs', 0755, true);
        $fname = uniqid('register_') . '.' . $ext;
        $target = 'uploads/company_docs/' . $fname;
        if (move_uploaded_file($_FILES['commercial_register']['tmp_name'], $target)) $commercial_register_file = $target; else $error = '�㨩 ��� �髤� �颤���';
      } else $error = '��� ��� �髤� �颤��� �� ���� (pdf, jpg, png ���)';
    }
  } else if (empty($error)) {
    $error = '鸞 ��� �髤� �颤��� �颥�� �� �����';
  }
  
  if (empty($error)) {
    // Block re-registration for emails previously removed by admin
    $rm = $conn->prepare('SELECT id FROM account_removals WHERE LOWER(removed_user_email)=LOWER(?) ORDER BY removed_at DESC LIMIT 1');
    $rm->bind_param('s', $email);
    $rm->execute();
    $rmr = $rm->get_result();
    if ($rmr && $rmr->num_rows > 0) {
      $error = '� ���� ������� 쨟 �頩� �㟧� �颫���. �� ���� 쨟 �饫�� �� �� �靧���.';
    }
  }

  if (empty($error)) {
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) $error = '�頩� ꫢ��� �����';
    else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt2 = $conn->prepare('INSERT INTO users (name,email,password,user_type,phone,company_location,website,is_verified,verification_status) VALUES (?,?,?,?,?,?,?,?,?)');
      $is_verified = 0;
      $verification_status = 'pending';
      $stmt2->bind_param('sssssssis', $name, $email, $hash, $user_type, $phone, $company_location, $website, $is_verified, $verification_status);
      if ($stmt2->execute()) {
        $user_id = $stmt2->insert_id;

        $stmt3 = $conn->prepare('INSERT INTO company_verification_requests (user_id, company_name, email, phone, company_location, website, commercial_register_file) VALUES (?,?,?,?,?,?,?)');
        if ($stmt3) {
          $stmt3->bind_param('issssss', $user_id, $name, $email, $phone, $company_location, $website, $commercial_register_file);
          $stmt3->execute();
          $stmt3->close();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_type'] = $user_type;
        $_SESSION['user_email'] = $email;
        $_SESSION['is_admin'] = 0;
        unset($_SESSION['admin_id']);
        $_SESSION['message'] = '�� �묟� �饫�� �뤟�. ��� ꩟�� �� �颥�� �� ����� �� �� �靧���.';
        header('Location: employer_dashboard.php');
        exit();
      } else $error = '�� ��럘 �颫���';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="utf-8">
  <title>����� ���</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body><?php include 'navbar.php'; ?>
  <main class="container">
    <div class="card form-card">
      <h2>����� ���</h2><?php if (!empty($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
      <div style="margin-bottom:10px;">
        <button class="btn" type="button" onclick="if(document.referrer){history.back();}else{window.location.href='index.php';}">����</button>
      </div>
      <form method="post" enctype="multipart/form-data" autocomplete="off">
        <div class="form-grid"><input class="input" name="name" placeholder="��� �鬩�" required autocomplete="off" value="<?php echo isset($name) && !empty($error) ? htmlspecialchars($name) : ''; ?>"><input class="input" name="email" type="email" placeholder="�頩� ���袩���" required autocomplete="off" value="<?php echo isset($email) && !empty($error) ? htmlspecialchars($email) : ''; ?>"></div>
        <div class="form-grid"><input class="input" name="password" type="password" placeholder="��� �����" required autocomplete="new-password">
          <div>
            <label style="display:block; font-size:12px; color:#555; margin-bottom:4px;">��쟢� (�����)</label>
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="display:flex; align-items:center; gap:6px; background:#f6f6f6; border:1px solid #ddd; padding:8px 10px; border-radius:6px;">
                <span>????</span>
                <span style="direction:ltr;">+967</span>
              </span>
              <input class="input" name="phone" placeholder="xxxxxxxxx" inputmode="numeric" pattern="(70|71|73|77|78)[0-9]{7}" title="9 ���� ���� �� 70 �� 71 �� 73 �� 77 �� 78" maxlength="9" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,9);" autocomplete="off" value="<?php echo isset($raw_phone) && !empty($error) ? htmlspecialchars($raw_phone) : ''; ?>">
            </div>
          </div>
        </div>
        <div class="form-grid"><input class="input" name="company_location" placeholder="���� �鬩� (���)" required autocomplete="off" value="<?php echo isset($company_location) && !empty($error) ? htmlspecialchars($company_location) : ''; ?>"><input class="input" name="website" placeholder="������ ���袩��� (����)" autocomplete="off" value="<?php echo isset($website) && !empty($error) ? htmlspecialchars($website) : ''; ?>"></div>
        
        <h3>�颥�� �� �����</h3>
        <p class="info-text">���� ��� ���럢? 賓� ��� �髤� �颤��� �颥�� �� �����</p>
        
        <label>�髤� �颤��� (�����) - pdf, jpg, png ��� 10MB</label>
        <input type="file" name="commercial_register" accept=".pdf,.jpg,.jpeg,.png" required autocomplete="off">
        
        <button class="btn btn-primary" type="submit">�묟� ���� ���</button>
      </form>
    </div>
  </main>
</body>

</html>
