<?php
session_start();
include 'db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id']) && isset($_POST['request_type'])) {
        $request_id = (int)$_POST['request_id'];
        $request_type = $_POST['request_type'];
        $action = $_POST['action'];
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        $admin_id = $_SESSION['user_id'];
        
        if ($request_type === 'graduate') {
            if ($action === 'approve') {
                // Update graduate verification request
                $stmt = $conn->prepare('UPDATE graduate_verification_requests SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
                $status = 'approved';
                $stmt->bind_param('ssii', $status, $admin_notes, $admin_id, $request_id);
                $stmt->execute();
                
                // Get user_id from request
                $stmt2 = $conn->prepare('SELECT user_id FROM graduate_verification_requests WHERE id = ?');
                $stmt2->bind_param('i', $request_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                $user_id = $result->fetch_assoc()['user_id'];
                
                // Update user verification status
                $stmt3 = $conn->prepare('UPDATE users SET is_verified = 1, verification_status = ? WHERE id = ?');
                $stmt3->bind_param('si', $status, $user_id);
                $stmt3->execute();
                
                $_SESSION['message'] = 'تم الموافقة على طلب التحقق للخريج بنجاح';
            } elseif ($action === 'reject') {
                // Update graduate verification request
                $stmt = $conn->prepare('UPDATE graduate_verification_requests SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
                $status = 'rejected';
                $stmt->bind_param('ssii', $status, $admin_notes, $admin_id, $request_id);
                $stmt->execute();
                
                // Get user_id from request
                $stmt2 = $conn->prepare('SELECT user_id FROM graduate_verification_requests WHERE id = ?');
                $stmt2->bind_param('i', $request_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                $user_id = $result->fetch_assoc()['user_id'];
                
                // Update user verification status
                $stmt3 = $conn->prepare('UPDATE users SET verification_status = ? WHERE id = ?');
                $stmt3->bind_param('si', $status, $user_id);
                $stmt3->execute();
                
                $_SESSION['message'] = 'تم رفض طلب التحقق للخريج';
            }
        } elseif ($request_type === 'company') {
            if ($action === 'approve') {
                // Update company verification request
                $stmt = $conn->prepare('UPDATE company_verification_requests SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
                $status = 'approved';
                $stmt->bind_param('ssii', $status, $admin_notes, $admin_id, $request_id);
                $stmt->execute();
                
                // Get user_id from request
                $stmt2 = $conn->prepare('SELECT user_id FROM company_verification_requests WHERE id = ?');
                $stmt2->bind_param('i', $request_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                $user_id = $result->fetch_assoc()['user_id'];
                
                // Update user verification status
                $stmt3 = $conn->prepare('UPDATE users SET is_verified = 1, verification_status = ? WHERE id = ?');
                $stmt3->bind_param('si', $status, $user_id);
                $stmt3->execute();
                
                $_SESSION['message'] = 'تم الموافقة على طلب التحقق للشركة بنجاح';
            } elseif ($action === 'reject') {
                // Update company verification request
                $stmt = $conn->prepare('UPDATE company_verification_requests SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
                $status = 'rejected';
                $stmt->bind_param('ssii', $status, $admin_notes, $admin_id, $request_id);
                $stmt->execute();
                
                // Get user_id from request
                $stmt2 = $conn->prepare('SELECT user_id FROM company_verification_requests WHERE id = ?');
                $stmt2->bind_param('i', $request_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                $user_id = $result->fetch_assoc()['user_id'];
                
                // Update user verification status
                $stmt3 = $conn->prepare('UPDATE users SET verification_status = ? WHERE id = ?');
                $stmt3->bind_param('si', $status, $user_id);
                $stmt3->execute();
                
                $_SESSION['message'] = 'تم رفض طلب التحقق للشركة';
            }
        }
        
        header('Location: admin_verification.php');
        exit();
    }
}

// Get pending graduate verification requests
$stmt = $conn->prepare('
    SELECT gvr.*, u.name as user_name, u.email as user_email 
    FROM graduate_verification_requests gvr 
    JOIN users u ON gvr.user_id = u.id 
    WHERE gvr.status = ? AND u.email NOT IN ("haroonhatem34@gmail.com","hamzahmisr@gmail.com")
    ORDER BY gvr.submitted_at ASC
');
$status = 'pending';
$stmt->bind_param('s', $status);
$stmt->execute();
$pending_graduate_requests = $stmt->get_result();

// Get pending company verification requests
$stmt2 = $conn->prepare('
    SELECT cvr.*, u.name as user_name, u.email as user_email 
    FROM company_verification_requests cvr 
    JOIN users u ON cvr.user_id = u.id 
    WHERE cvr.status = ? AND u.email NOT IN ("haroonhatem34@gmail.com","hamzahmisr@gmail.com")
    ORDER BY cvr.submitted_at ASC
');
$stmt2->bind_param('s', $status);
$stmt2->execute();
$pending_company_requests = $stmt2->get_result();

// Get all graduate verification requests for history
$stmt3 = $conn->prepare('
    SELECT gvr.*, u.name as user_name, u.email as user_email,
           admin.name as admin_name, "graduate" as request_type
    FROM graduate_verification_requests gvr 
    JOIN users u ON gvr.user_id = u.id 
    LEFT JOIN users admin ON gvr.reviewed_by = admin.id
    WHERE u.email NOT IN ("haroonhatem34@gmail.com","hamzahmisr@gmail.com")
    ORDER BY gvr.submitted_at DESC
');
$stmt3->execute();
$all_graduate_requests = $stmt3->get_result();

// Get all company verification requests for history
$stmt4 = $conn->prepare('
    SELECT cvr.*, u.name as user_name, u.email as user_email,
           admin.name as admin_name, "company" as request_type
    FROM company_verification_requests cvr 
    JOIN users u ON cvr.user_id = u.id 
    LEFT JOIN users admin ON cvr.reviewed_by = admin.id
    WHERE u.email NOT IN ("haroonhatem34@gmail.com","hamzahmisr@gmail.com")
    ORDER BY cvr.submitted_at DESC
');
$stmt4->execute();
$all_company_requests = $stmt4->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>إدارة طلبات التحقق</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="container">
        <!-- Welcome Message -->
        <div class="card welcome-message">
            <h1>مرحباً <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p>مرحباً بك في لوحة إدارة طلبات التحقق. يمكنك هنا مراجعة وموافقة أو رفض طلبات التحقق من الهوية.</p>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="card success-message">
                <p><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>طلبات التحقق المعلقة - الخريجين</h2>
            <?php if ($pending_graduate_requests->num_rows > 0): ?>
                <?php while ($request = $pending_graduate_requests->fetch_assoc()): ?>
                    <div class="verification-request">
                        <div class="request-header">
                            <h3><?php echo htmlspecialchars($request['full_name']); ?></h3>
                            <span class="request-date"><?php echo $request['submitted_at']; ?></span>
                        </div>
                        <div class="request-details">
                            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                            <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                            <p><strong>الجامعة:</strong> <?php echo htmlspecialchars($request['university']); ?></p>
                            <p><strong>التخصص:</strong> <?php echo htmlspecialchars($request['field_of_study']); ?></p>
                            <p><strong>شهادة التخرج:</strong> 
                                <a href="<?php echo htmlspecialchars($request['certificate_file']); ?>" target="_blank" class="btn btn-small">عرض الشهادة</a>
                            </p>
                        </div>
                        <form method="post" class="verification-actions">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="request_type" value="graduate">
                            <textarea name="admin_notes" placeholder="ملاحظات الإدارة (اختياري)" class="input"></textarea>
                            <div class="action-buttons">
                                <button type="submit" name="action" value="approve" class="btn btn-success">موافقة</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger">رفض</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>لا توجد طلبات تحقق للخريجين معلقة</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>طلبات التحقق المتعلقة - الشركات</h2>
            <?php if ($pending_company_requests->num_rows > 0): ?>
                <?php while ($request = $pending_company_requests->fetch_assoc()): ?>
                    <div class="verification-request">
                        <div class="request-header">
                            <h3><?php echo htmlspecialchars($request['company_name']); ?></h3>
                            <span class="request-date"><?php echo $request['submitted_at']; ?></span>
                        </div>
                        <div class="request-details">
                            <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                            <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($request['phone']); ?></p>
                            <p><strong>موقع الشركة:</strong> <?php echo htmlspecialchars($request['company_location']); ?></p>
                            <p><strong>الموقع الإلكتروني:</strong> <?php echo htmlspecialchars($request['website']); ?></p>
                            <p><strong>السجل التجاري:</strong> 
                                <a href="<?php echo htmlspecialchars($request['commercial_register_file']); ?>" target="_blank" class="btn btn-small">عرض السجل</a>
                            </p>
                        </div>
                        <form method="post" class="verification-actions">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="request_type" value="company">
                            <textarea name="admin_notes" placeholder="ملاحظات الإدارة (اختياري)" class="input"></textarea>
                            <div class="action-buttons">
                                <button type="submit" name="action" value="approve" class="btn btn-success">موافقة</button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger">رفض</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>لا توجد طلبات تحقق للشركات معلقة</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>تاريخ طلبات التحقق - الخريجين</h2>
            <?php if ($all_graduate_requests->num_rows > 0): ?>
                <table class="table">
                    <tr>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجامعة</th>
                        <th>التخصص</th>
                        <th>الحالة</th>
                        <th>تاريخ التقديم</th>
                        <th>مراجع بواسطة</th>
                        <th>الإجراءات</th>
                    </tr>
                    <?php while ($request = $all_graduate_requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo htmlspecialchars($request['university']); ?></td>
                            <td><?php echo htmlspecialchars($request['field_of_study']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php 
                                    switch($request['status']) {
                                        case 'pending': echo 'معلق'; break;
                                        case 'approved': echo 'موافق عليه'; break;
                                        case 'rejected': echo 'مرفوض'; break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo $request['submitted_at']; ?></td>
                            <td><?php echo $request['admin_name'] ? htmlspecialchars($request['admin_name']) : '-'; ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($request['certificate_file']); ?>" target="_blank" class="btn btn-small">عرض الشهادة</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>لا توجد طلبات تحقق للخريجين</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>تاريخ طلبات التحقق - الشركات</h2>
            <?php if ($all_company_requests->num_rows > 0): ?>
                <table class="table">
                    <tr>
                        <th>اسم الشركة</th>
                        <th>البريد الإلكتروني</th>
                        <th>الهاتف</th>
                        <th>موقع الشركة</th>
                        <th>الموقع الإلكتروني</th>
                        <th>الحالة</th>
                        <th>تاريخ التقديم</th>
                        <th>مراجع بواسطة</th>
                        <th>الإجراءات</th>
                    </tr>
                    <?php while ($request = $all_company_requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo htmlspecialchars($request['phone']); ?></td>
                            <td><?php echo htmlspecialchars($request['company_location']); ?></td>
                            <td><?php echo htmlspecialchars($request['website']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php 
                                    switch($request['status']) {
                                        case 'pending': echo 'معلق'; break;
                                        case 'approved': echo 'موافق عليه'; break;
                                        case 'rejected': echo 'مرفوض'; break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo $request['submitted_at']; ?></td>
                            <td><?php echo $request['admin_name'] ? htmlspecialchars($request['admin_name']) : '-'; ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($request['commercial_register_file']); ?>" target="_blank" class="btn btn-small">عرض السجل</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>لا توجد طلبات تحقق للشركات</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
