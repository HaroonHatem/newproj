<?php session_start(); include 'db.php'; ?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>الرئيسية</title><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/css/style.css"></head><body><?php include 'navbar.php'; ?>
<main class="container">
  <section class="hero card"><h1>مرحباً بك في بوابة توظيف الخريجين</h1><p class="lead">وصل الخريجين بأصحاب العمل بسهولة وبشكل منسق.</p></section>
  <section class="cards-grid">
    <a class="card card-1" href="login.php"><div class="icon">🔑</div><h3>تسجيل دخول</h3><p>دخول الخريجين أو الشركات</p></a>
    <a class="card card-2" href="register_graduate.php"><div class="icon">🆕</div><h3>تسجيل خريج</h3><p>سجّل بياناتك وارفع سيرتك</p></a>
    <a class="card card-3" href="search_jobs.php"><div class="icon">💼</div><h3>بحث عن وظائف</h3><p>استعرض الوظائف المتاحة وقدّم لها</p></a>
    <a class="card card-4" href="search_graduates.php"><div class="icon">🎓</div><h3>بحث عن خريجين</h3><p>لأصحاب العمل: اعثر على المتقدمين</p></a>
  </section>
</main><script src="assets/js/script.js"></script></body></html>