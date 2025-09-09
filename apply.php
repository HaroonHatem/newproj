<?php
session_start();
 include 'db.php';
 if(!isset($_SESSION['user_id']) || $_SESSION['user_type']!=='graduate'){
     header('Location: login.php');
     exit();
 }
 $user_id=$_SESSION['user_id'];
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
 if($stmt2->execute()) $_SESSION['message']='تم التقديم بنجاح';
 else $_SESSION['message']='حدث خطأ';
 header('Location: search_jobs.php');
 exit();
   ?>