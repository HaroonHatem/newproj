<?php
session_start();
include 'db.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']); $email=trim($_POST['email']); $password=$_POST['password']; $phone=trim($_POST['phone']); $website=trim($_POST['website']);
  $user_type='company';
  $stmt=$conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1'); $stmt->bind_param('s',$email); $stmt->execute(); $r=$stmt->get_result();
  if($r->num_rows>0) $error='البريد مستخدم سابقاً'; else { $hash=password_hash($password,PASSWORD_DEFAULT); $stmt2=$conn->prepare('INSERT INTO users (name,email,password,user_type,phone,website) VALUES (?,?,?,?,?,?)'); $stmt2->bind_param('ssssss',$name,$email,$hash,$user_type,$phone,$website); if($stmt2->execute()){ $_SESSION['user_id']=$stmt2->insert_id; $_SESSION['user_name']=$name; $_SESSION['user_type']=$user_type; header('Location: employer_dashboard.php'); exit(); } else $error='خطأ أثناء التسجيل'; }
}
?><!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>تسجيل شركة</title><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/css/style.css"></head><body><?php include 'navbar.php'; ?>
<main class="container"><div class="card form-card"><h2>تسجيل شركة</h2><?php if(!empty($error)) echo '<p class="error">'.htmlspecialchars($error).'</p>'; ?>
<form method="post"><div class="form-grid"><input class="input" name="name" placeholder="اسم الشركة" required><input class="input" name="email" type="email" placeholder="البريد الإلكتروني" required></div><div class="form-grid"><input class="input" name="password" type="password" placeholder="كلمة المرور" required><input class="input" name="phone" placeholder="هاتف الشركة"></div><input class="input" name="website" placeholder="موقع الشركة (اختياري)"><button class="btn btn-primary" type="submit">إنشاء حساب شركة</button></form></div></main></body></html>