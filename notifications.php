<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark notification as read if specified
if (isset($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $notification_id, $user_id);
    $stmt->execute();
    header('Location: notifications.php');
    exit();
}

// Mark all notifications as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    header('Location: notifications.php');
    exit();
}

// Get notifications
$stmt = $conn->prepare('
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Get unread count
$stmt2 = $conn->prepare('SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt2->bind_param('i', $user_id);
$stmt2->execute();
$unread_count = $stmt2->get_result()->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>الإشعارات</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: #e3f2fd;
            border-right: 4px solid #2196f3;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-message {
            color: #666;
            margin-bottom: 5px;
        }
        .notification-time {
            font-size: 12px;
            color: #999;
        }
        .notification-actions {
            margin-top: 10px;
        }
        .mark-read-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .no-notifications {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .mark-all-read-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="container">
        <div class="card">
            <div class="notifications-header">
                <h2>الإشعارات 
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #dc3545; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </h2>
                <?php if ($unread_count > 0): ?>
                    <form method="post" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="mark-all-read-btn">
                            تعيين الكل كمقروء
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-title">
                            <?php echo htmlspecialchars($notification['title']); ?>
                        </div>
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        <div class="notification-time">
                            <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                        </div>
                        <?php if (!$notification['is_read']): ?>
                            <div class="notification-actions">
                                <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="mark-read-btn">
                                    تعيين كمقروء
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <p>لا توجد إشعارات</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
