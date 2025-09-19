<?php session_start();
include 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['job_id'])) {
    header('Location: employer_dashboard.php');
    exit();
}
$job_id = intval($_GET['job_id']);
$company_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT id FROM jobs WHERE id=? AND company_id=?');
$stmt->bind_param('ii', $job_id, $company_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die('لا تملك هذه الوظيفة');
$stmt2 = $conn->prepare('
    SELECT applications.*, users.name, users.email, users.phone, users.cv_file, users.cv_link, 
           cc.id as conversation_id,
           (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id = cc.id AND cm.sender_id != ? AND cm.is_read = 0) as unread_count
    FROM applications 
    JOIN users ON applications.user_id=users.id 
    LEFT JOIN chat_conversations cc ON applications.job_id = cc.job_id AND applications.user_id = cc.graduate_id
    WHERE applications.job_id=? 
    ORDER BY applications.applied_at DESC
');
$stmt2->bind_param('ii', $company_id, $job_id);
$stmt2->execute();
$apps = $stmt2->get_result(); ?>
<!DOCTYPE html>
<html lang='ar' dir='rtl'>

<head>
    <meta charset='utf-8'>
    <title>المتقدمين</title>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <link rel='stylesheet' href='assets/css/style.css'>
</head>

<body><?php include 'navbar.php'; ?><main class='container'>
        <div class='card'>
            <h2>قائمة المتقدمين</h2><?php if ($apps->num_rows > 0): ?><table class='table'>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد</th>
                        <th>الهاتف</th>
                        <th>السيرة</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>المحادثة</th>
                        <th>إجراء</th>
                    </tr><?php while ($a = $apps->fetch_assoc()): ?><tr>
                            <td><?php echo htmlspecialchars($a['name']); ?></td>
                            <td><?php echo htmlspecialchars($a['email']); ?></td>
                            <td><?php echo htmlspecialchars($a['phone']); ?></td>
                            <td><?php 
                                if (!empty($a['cv_file'])) {
                                    echo "<a href='view_cv.php?user_id=" . (int)$a['user_id'] . "' target='_blank'>ملف</a>";
                                } else if (!empty($a['cv_link'])) {
                                    echo '-'; // third-party links are not exposed for privacy
                                } else {
                                    echo '-';
                                }
                            ?></td>
                            <td><?php echo $a['applied_at']; ?></td>
                            <td><span class="status-badge status-<?php echo $a['status']; ?>"><?php 
                                switch($a['status']) {
                                  case 'pending': echo 'معلق'; break;
                                  case 'accepted': echo 'مقبول'; break;
                                  case 'rejected': echo 'مرفوض'; break;
                                }
                                ?></span></td>
                            <td>
                                <?php if ($a['conversation_id']): ?>
                                    <a href="chat.php?conversation_id=<?php echo $a['conversation_id']; ?>" class="btn btn-apply">
                                        محادثة
                                        <?php if ($a['unread_count'] > 0): ?>
                                            <span style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; margin-right: 5px;">
                                                <?php echo $a['unread_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #666;">لم تبدأ بعد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['status'] === 'pending'): ?>
                                    <form method="post" action="update_application_status.php" style="display:flex; gap:8px;">
                                        <input type="hidden" name="application_id" value="<?php echo (int)$a['id']; ?>">
                                        <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">
                                        <button class="btn btn-apply" name="action" value="accept" type="submit">قبول</button>
                                        <button class="btn btn-danger" name="action" value="reject" type="submit">رفض</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#666;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr><?php endwhile; ?>
                </table><?php else: ?><p>لا يوجد متقدمين.</p><?php endif; ?>
        </div>
    </main>
</body>

</html> 