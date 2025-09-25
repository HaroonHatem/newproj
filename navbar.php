<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<link rel="stylesheet" href="assets/css/style.css">
<nav class="topbar">
  <div class="brand"><a href="index.php">بوابة التوظيف</a></div>
  <ul class="menu" style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
    <?php if(isset($_SESSION['user_id'])): ?>
    <div style="display: flex; gap: 15px;">
      <li><a href="index.php">الرئيسية</a></li>
      <?php if(empty($_SESSION['is_admin']) && $_SESSION['user_type'] != 'company'): ?>
      <?php
        // if graduate, show link but mark disabled if not verified
        $can_apply_nav = true;
        if ($_SESSION['user_type'] === 'graduate') {
          include_once 'db.php';
          $st = $conn->prepare('SELECT is_verified, verification_status FROM users WHERE id=?');
          $st->bind_param('i', $_SESSION['user_id']);
          $st->execute();
          $ud = $st->get_result()->fetch_assoc();
          $can_apply_nav = ($ud && (int)$ud['is_verified'] === 1 && $ud['verification_status'] === 'approved');
        }
      ?>
      <?php if($can_apply_nav): ?>
      <li><a href="search_jobs.php">بحث عن وظائف</a></li>
      <?php else: ?>
      <li><span style="opacity:.6; cursor:not-allowed;">بحث عن وظائف (بانتظار التحقق)</span></li>
      <?php endif; ?>
      <?php endif; ?>
      <?php if(empty($_SESSION['is_admin']) && $_SESSION['user_type'] != 'graduate'): ?>
      <li><a href="search_graduates.php">بحث عن خريجين</a></li>
      <?php endif; ?>
      <?php
        // Unread notifications count for current user
        $notif_count = 0;
        if (isset($_SESSION['user_id'])) {
          include_once 'db.php';
          $nst = $conn->prepare('SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0');
          $nst->bind_param('i', $_SESSION['user_id']);
          $nst->execute();
          $nr = $nst->get_result();
          if ($nr) { $notif_count = (int)$nr->fetch_assoc()['c']; }
        }
      ?>
      <li>
        <a href="notifications.php" style="background-color:#007bff; color:white;
                                           padding:5px 10px; border-radius:5px;
                                           display:inline-flex; align-items:center; gap:6px;">
          الإشعارات
          <?php if ($notif_count > 0): ?>
            <span style="background:#dc3545; color:#fff; border-radius:50%; padding:2px 6px; font-size:12px; line-height:1; display:inline-block; min-width:18px; text-align:center;">
              <?php echo $notif_count; ?>
            </span>
          <?php endif; ?>
        </a>
      </li>
      <li><a href="chat.php" style="background-color:#28a745; color:white; padding:5px 10px; border-radius:5px;">المحادثات</a></li>
      <?php if(!empty($_SESSION['is_admin'])): ?>
        <li><a href="admin_dashboard.php">لوحة المدير</a></li>
      <?php elseif($_SESSION['user_type']=='company'): ?>
        <li><a href="employer_dashboard.php">لوحة الشركة</a></li>
      <?php else: ?>
        <li><a href="graduate_dashboard.php">لوحة الخريج</a></li>
      <?php endif; ?>
    </div>
    <div>
      <li style="color: #2c3e50; font-weight: bold; padding: 5px 10px;">
        مرحباً، <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        <?php if(!empty($_SESSION['is_admin'])): ?>
          <span style="font-size: 15px; color: #8e44ad;">(مدير)</span>
        <?php elseif($_SESSION['user_type'] == 'graduate'): ?>
          <span style="font-size: 13px; color:rgb(7, 90, 41);">(خريج)</span>
        <?php elseif($_SESSION['user_type'] == 'company'): ?>
          <?php
          // Get company verification status for navbar display
          if (isset($_SESSION['user_id'])) {
            include_once 'db.php';
            $stmt = $conn->prepare('SELECT is_verified, verification_status FROM users WHERE id = ?');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $company_data = $result->fetch_assoc();
          }
          ?>
          <span style="font-size: 13px; color:rgb(46, 116, 163);">(شركة)</span>
          <?php if (isset($company_data) && !$company_data['is_verified']): ?>
            <span style="font-size: 10px; color: #e74c3c; margin-right: 5px;">⏳ غير موثق</span>
          <?php elseif (isset($company_data) && $company_data['is_verified']): ?>
            <span style="font-size: 10px; color:rgb(21, 76, 44); margin-right: 5px;">✓ موثق</span>
          <?php endif; ?>
        <?php endif; ?>
      </li>
      <?php if(!empty($_SESSION['is_admin'])): ?>
        <li><a href="admin_users.php" style="background-color:#2c3e50; color:white; padding:5px 10px; border-radius:5px;">إدارة المستخدمين</a></li>
        <li><a href="admin_verification.php" style="background-color:#8e44ad; color:white; padding:5px 10px; border-radius:5px;">طلبات التحقق</a></li>
      <?php endif; ?>
      <li><a href="logout.php" style="background-color:#e74c3c; color:white; padding:5px 10px; border-radius:5px;">خروج</a></li>
    </div>
    <?php else: ?>
    <div style="display: flex; gap: 15px;">
      <li><a href="login.php">تسجيل دخول</a></li>
      <li><a href="register_graduate.php">انشاء حساب كونك خريج</a></li>
      <li><a href="register_company.php">انشاء حساب كونك صاحب شركة</a></li>
    </div>
    <?php endif; ?>
  </ul>
</nav>
