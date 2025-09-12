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
      <li><a href="search_jobs.php">بحث عن وظائف</a></li>
      <li><a href="search_graduates.php">بحث عن خريجين</a></li>
      <li><a href="chat.php" style="background-color:#28a745; color:white; padding:5px 10px; border-radius:5px;">المحادثات</a></li>
      <li><a href="notifications.php" style="background-color:#ffc107; color:black; padding:5px 10px; border-radius:5px;">الإشعارات</a></li>
      <?php if($_SESSION['user_type']=='company'): ?>
        <li><a href="employer_dashboard.php">لوحة الشركة</a></li>
      <?php else: ?>
        <li><a href="graduate_dashboard.php">لوحة الخريج</a></li>
      <?php endif; ?>
    </div>
    <div>
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
