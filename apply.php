<?php
session_start();
 include 'db.php';
 if(!isset($_SESSION['user_id']) || $_SESSION['user_type']!=='graduate'){
     header('Location: login.php');
     exit();
 }
 $user_id=$_SESSION['user_id'];

 // Block unverified graduates from applying
 $verifyStmt = $conn->prepare('SELECT is_verified, verification_status FROM users WHERE id = ? LIMIT 1');
 $verifyStmt->bind_param('i', $user_id);
 $verifyStmt->execute();
 $verify = $verifyStmt->get_result()->fetch_assoc();
 if (!$verify || !$verify['is_verified'] || $verify['verification_status'] !== 'approved') {
     $_SESSION['message'] = 'لا يمكنك التقديم حتى يتم التحقق من هويتك والموافقة عليها.';
     header('Location: graduate_dashboard.php');
     exit();
 }

 $job_id=isset($_GET['job_id'])?intval($_GET['job_id']):0;
 if(!$job_id){ header('Location: search_jobs.php');
  exit();
 }
 $stmt=$conn->prepare('SELECT id FROM applications WHERE job_id=? AND user_id=? LIMIT 1');
 $stmt->bind_param('ii',$job_id,$user_id); $stmt->execute();
 $r=$stmt->get_result();
 if($r->num_rows>0){
     $_SESSION['message']='لقد قدمت لهذه الوظيفة سابقاً';
     header('Location: search_jobs.php');
     exit();
 }
$stmt2=$conn->prepare('INSERT INTO applications (job_id,user_id,status) VALUES (?,?,"pending")');
$stmt2->bind_param('ii',$job_id,$user_id);
if($stmt2->execute()) {
    $application_id = $stmt2->insert_id;
    
    // Get job and company details for notification
    $stmt3 = $conn->prepare('SELECT j.title, j.company_id, u.name as company_name FROM jobs j JOIN users u ON j.company_id = u.id WHERE j.id = ?');
    $stmt3->bind_param('i', $job_id);
    $stmt3->execute();
    $job_data = $stmt3->get_result()->fetch_assoc();
    
    // Get graduate name for notification
    $stmt4 = $conn->prepare('SELECT name FROM users WHERE id = ?');
    $stmt4->bind_param('i', $user_id);
    $stmt4->execute();
    $graduate_name = $stmt4->get_result()->fetch_assoc()['name'];
    
    // Create notification for company
    $stmt5 = $conn->prepare('INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, "application", ?, ?, ?)');
    $notification_title = 'طلب توظيف جديد';
    $notification_message = 'تلقيت طلب توظيف جديد من ' . $graduate_name . ' للوظيفة: ' . $job_data['title'];
    $stmt5->bind_param('issi', $job_data['company_id'], $notification_title, $notification_message, $application_id);
    $stmt5->execute();
    
    // Create chat conversation
    $stmt6 = $conn->prepare('INSERT INTO chat_conversations (job_id, company_id, graduate_id, application_id) VALUES (?, ?, ?, ?)');
    $stmt6->bind_param('iiii', $job_id, $job_data['company_id'], $user_id, $application_id);
    $stmt6->execute();
    
    $_SESSION['message']='تم التقديم بنجاح. يمكنك التواصل مع الشركة من خلال المحادثة';
} else {
    $_SESSION['message']='حدث خطأ';
}
header('Location: search_jobs.php');
exit();
   ?>