<?php session_start();
include 'db.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="utf-8">
  <title>الرئيسية</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body><?php include 'navbar.php'; ?>
  <?php if (isset($_SESSION['user_id'])) { ?>
  <main class="container">
    <section class="hero card">
      <h1>مرحباً <?php echo htmlspecialchars($_SESSION['user_name']); ?> في بوابة توظيف الخريجين</h1>
      <p class="lead">
        <?php if($_SESSION['user_type'] == 'graduate') { ?>
          ابحث عن الوظائف المناسبة لك وقدم طلبات التوظيف
        <?php } elseif($_SESSION['user_type'] == 'company') { ?>
          <?php
          // Get company verification status
          $stmt = $conn->prepare('SELECT is_verified, verification_status FROM users WHERE id = ?');
          $stmt->bind_param('i', $_SESSION['user_id']);
          $stmt->execute();
          $result = $stmt->get_result();
          $company_data = $result->fetch_assoc();
          ?>
          <?php if (!empty($company_data['is_verified'])) { ?>
            ابحث عن الخريجين المناسبين ونشر الوظائف
          <?php } else { ?>
            ابحث عن الخريجين المناسبين - إضافة الوظائف متاحة بعد التحقق من الهوية
          <?php } ?>
        <?php } elseif(!empty($_SESSION['is_admin'])) { ?>
          إدارة النظام ومراجعة طلبات التحقق
        <?php } ?>
      </p>
    </section>
    <section class="cards-grid">
      <?php if($_SESSION['user_type'] == 'graduate') { ?>
      <!-- Graduate interface: Show only Search for Jobs -->
      <a class="card card-1" href="search_jobs.php">
        <div class="icon">💼</div>
        <h3>بحث عن وظائف</h3>
        <p>استعرض الوظائف المتاحة وقدّم لها</p>
      </a>
      <?php } elseif($_SESSION['user_type'] == 'company') { ?>
      <!-- Company interface: Show only Search for Graduates -->
      <a class="card card-1" href="search_graduates.php">
        <div class="icon">🎓</div>
        <h3>بحث عن خريجين</h3>
        <p>لأصحاب العمل: اعثر على المتقدمين</p>
      </a>
      <?php } else { ?>
      <!-- Admin interface: Show all buttons -->
      <a class="card card-1" href="search_jobs.php">
        <div class="icon">💼</div>
        <h3>بحث عن وظائف</h3>
        <p>استعرض الوظائف المتاحة وقدّم لها</p>
      </a>
      <a class="card card-2" href="search_graduates.php">
        <div class="icon">🎓</div>
        <h3>الخريجين</h3>
        <p>لأصحاب العمل: اعثر على المتقدمين</p>
      </a>
      <a class="card card-3" href="chat.php">
        <div class="icon">💬</div>
        <h3>المحادثات</h3>
        <p>تواصل مع الشركات أو الخريجين</p>
      </a>
      <a class="card card-4" href="notifications.php">
        <div class="icon">🔔</div>
        <h3>الإشعارات</h3>
        <p>اطلع على آخر التحديثات</p>
      </a>
      <?php } ?>
    </section>
  </main>
  <?php } else { ?>
  <main class="container">
    <section class="hero card">
      <h1>مرحباً بك في بوابة توظيف الخريجين</h1>
      <p class="lead">سجّل دخولك أو أنشئ حساباً للمتابعة.</p>
    </section>
    <section class="cards-grid">
      <a class="card card-1" href="login.php">
        <div class="icon">🔑</div>
        <h3>تسجيل دخول</h3>
        <p>دخول الخريجين أو الشركات</p>
      </a>
      <a class="card card-2" href="register_graduate.php">
        <div class="icon">🆕</div>
        <h3>انشاء حساب كونك خريج</h3>
        <p>سجّل بياناتك وارفع سيرتك</p>
      </a>
      <a class="card card-3" href="register_company.php">
        <div class="icon">🏢</div>
        <h3>انشاء حساب كونك صاحب شركة</h3>
        <p>سجّل شركتك وابدأ بنشر الوظائف</p>
      </a>
    </section>
  </main>
  <?php } ?>
  <script src="assets/js/script.js"></script>
</body>

</html>