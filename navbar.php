<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<link rel="stylesheet" href="assets/css/style.css">
<nav class="topbar">
  <div class="brand"><a href="index.php">بوابة التوظيف</a></div>
  <ul class="menu" style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; gap: 15px;">
      <li><a href="index.php">الرئيسية</a></li>
      <li><a href="search_jobs.php">بحث عن وظائف</a></li>
      <li><a href="search_graduates.php">بحث عن خريجين</a></li>
      <?php if(isset($_SESSION['user_id'])): ?>
        <?php if($_SESSION['user_type']=='company'): ?>
          <li><a href="employer_dashboard.php">لوحة الشركة</a></li>
        <?php else: ?>
          <li><a href="graduate_dashboard.php">لوحة الخريج</a></li>
        <?php endif; ?>
      <?php else: ?>
        <li><a href="login.php">تسجيل دخول</a></li>
        <li><a href="register_graduate.php">تسجيل خريج</a></li>
        <li><a href="register_company.php">تسجيل شركة</a></li>
      <?php endif; ?>
    </div>
    <div>
      <li><a href="logout.php" style="background-color:#e74c3c; color:white; padding:5px 10px; border-radius:5px;">خروج</a></li>
    </div>
  </ul>
</nav>
