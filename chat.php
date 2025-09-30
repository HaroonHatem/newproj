<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$is_admin = !empty($_SESSION['is_admin']);
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

if ($is_admin) {
    header('Location: admin_dashboard.php');
    exit();
}

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    if (!empty($message) && $conversation_id > 0) {
        // Debug: Log the message length and content
        error_log("Chat message length: " . strlen($message) . ", content: " . substr($message, 0, 100));
        
        $stmt = $conn->prepare('INSERT INTO chat_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $conversation_id, $user_id, $message);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Chat message insert error: " . $stmt->error);
            $_SESSION['chat_error'] = 'فشل في إرسال الرسالة: ' . $stmt->error;
        } else {
            $_SESSION['chat_success'] = 'تم إرسال الرسالة بنجاح';
        }
        
        // Mark conversation as updated
        $stmt2 = $conn->prepare('UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?');
        $stmt2->bind_param('i', $conversation_id);
        $stmt2->execute();
        
        // Create notification for the other party
        $stmt3 = $conn->prepare('SELECT company_id, graduate_id FROM chat_conversations WHERE id = ?');
        $stmt3->bind_param('i', $conversation_id);
        $stmt3->execute();
        $conv_data = $stmt3->get_result()->fetch_assoc();
        
        $recipient_id = ($user_type === 'company') ? $conv_data['graduate_id'] : $conv_data['company_id'];
        $sender_name = $_SESSION['user_name'];
        
        $stmt4 = $conn->prepare('INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, "message", ?, ?, ?)');
        $notification_title = 'رسالة جديدة';
        $notification_message = 'رسالة جديدة من ' . $sender_name;
        $stmt4->bind_param('issi', $recipient_id, $notification_title, $notification_message, $conversation_id);
        $stmt4->execute();
        
        header('Location: chat.php?conversation_id=' . $conversation_id);
        exit();
    }
}

// Get conversations list (exclude admins for non-admin users)
if ($user_type === 'company') {
    $stmt = $conn->prepare('
        SELECT cc.*, j.title as job_title, u.name as graduate_name, u.university, u.specialization,
               (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id = cc.id AND cm.sender_id != ? AND cm.is_read = 0) as unread_count
        FROM chat_conversations cc 
        JOIN jobs j ON cc.job_id = j.id 
        JOIN users u ON cc.graduate_id = u.id 
        WHERE cc.company_id = ? 
        ORDER BY cc.updated_at DESC
    ');
    $stmt->bind_param('ii', $user_id, $user_id);
} else {
    $stmt = $conn->prepare('
        SELECT cc.*, j.title as job_title, u.name as company_name,
               (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id = cc.id AND cm.sender_id != ? AND cm.is_read = 0) as unread_count
        FROM chat_conversations cc 
        JOIN jobs j ON cc.job_id = j.id 
        JOIN users u ON cc.company_id = u.id 
        WHERE cc.graduate_id = ? 
        ORDER BY cc.updated_at DESC
    ');
    $stmt->bind_param('ii', $user_id, $user_id);
}
$stmt->execute();
$conversations = $stmt->get_result();

// Get current conversation details
$current_conversation = null;
$messages = [];
if ($conversation_id > 0) {
    // Verify user has access to this conversation
    $stmt = $conn->prepare('SELECT * FROM chat_conversations WHERE id = ? AND (company_id = ? OR graduate_id = ?)');
    $stmt->bind_param('iii', $conversation_id, $user_id, $user_id);
    $stmt->execute();
    $current_conversation = $stmt->get_result()->fetch_assoc();
    
    if ($current_conversation) {
        // Get messages
        $stmt2 = $conn->prepare('
            SELECT cm.*, u.name as sender_name, u.user_type 
            FROM chat_messages cm 
            JOIN users u ON cm.sender_id = u.id 
            WHERE cm.conversation_id = ? 
            ORDER BY cm.created_at ASC
        ');
        $stmt2->bind_param('i', $conversation_id);
        $stmt2->execute();
        $messages = $stmt2->get_result();
        
        // Mark messages as read
        $stmt3 = $conn->prepare('UPDATE chat_messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?');
        $stmt3->bind_param('ii', $conversation_id, $user_id);
        $stmt3->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>المحادثات</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-container {
            display: flex;
            height: 80vh;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .conversations-list {
            width: 300px;
            background: #f8f9fa;
            border-left: 1px solid #ddd;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background: #e9ecef;
        }
        .conversation-item.active {
            background: var(--accent);
            color: white;
        }
        .conversation-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .conversation-meta {
            font-size: 12px;
            color: #666;
        }
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            margin-right: 5px;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px;
            background: var(--accent);
            color: white;
            font-weight: bold;
        }
        .messages-container {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .message.sent {
            align-items: flex-end;
        }
        .message.received {
            align-items: flex-start;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        .message.sent .message-bubble {
            background: var(--accent);
            color: white;
        }
        .message.received .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #ddd;
        }
        .message-sender {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .message.sent .message-sender {
            text-align: right;
            color: var(--accent);
        }
        .message.received .message-sender {
            text-align: left;
            color: var(--bg1);
        }
        .sender-type {
            font-size: 10px;
            font-weight: normal;
            opacity: 0.7;
        }
        .message-time {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .message-input-area {
            padding: 15px;
            background: white;
            border-top: 1px solid #ddd;
        }
        .message-input-form {
            display: flex;
            gap: 10px;
        }
        .message-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
        }
        .send-button {
            padding: 10px 20px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .send-button:hover {
            background: var(--bg1);
        }
        .return-button {
            background-color: var(--accent);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .return-button:hover {
            background-color: var(--bg1);
            color: white;
            text-decoration: none;
        }
        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            font-size: 18px;
        }
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: 90vh;
            }
            .conversations-list {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>المحادثات</h2>
                <a href="index.php" class="return-button">
                    ← العودة للصفحة الرئيسية
                </a>
            </div>
            
            <?php if (isset($_SESSION['chat_error'])): ?>
                <div class="card" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-bottom: 15px;">
                    <p><?php echo htmlspecialchars($_SESSION['chat_error']); unset($_SESSION['chat_error']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['chat_success'])): ?>
                <div class="card" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 15px;">
                    <p><?php echo htmlspecialchars($_SESSION['chat_success']); unset($_SESSION['chat_success']); ?></p>
                </div>
            <?php endif; ?>
            <div class="chat-container">
                <div class="conversations-list">
                    <?php if (($is_admin && isset($conversations) && $conversations instanceof mysqli_result && $conversations->num_rows > 0) || (!$is_admin && isset($conversations) && $conversations instanceof ArrayObject && $conversations->count() > 0)): ?>
                        <?php if ($is_admin): ?>
                            <?php while ($conv = $conversations->fetch_assoc()): ?>
                                <div class="conversation-item <?php echo ($conversation_id == $conv['id']) ? 'active' : ''; ?>" 
                                     onclick="location.href='chat.php?conversation_id=<?php echo $conv['id']; ?>'">
                                    <div class="conversation-title">
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($conv['job_title']); ?>
                                    </div>
                                    <div class="conversation-meta">
                                        <?php if ($user_type === 'company'): ?>
                                            <?php echo htmlspecialchars($conv['graduate_name']); ?>
                                            <?php if ($conv['university']): ?>
                                                - <?php echo htmlspecialchars($conv['university']); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($conv['company_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv): ?>
                                <div class="conversation-item <?php echo ($conversation_id == $conv['id']) ? 'active' : ''; ?>" 
                                     onclick="location.href='chat.php?conversation_id=<?php echo $conv['id']; ?>'">
                                    <div class="conversation-title">
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($conv['job_title']); ?>
                                    </div>
                                    <div class="conversation-meta">
                                        <?php if ($user_type === 'company'): ?>
                                            <?php echo htmlspecialchars($conv['graduate_name']); ?>
                                            <?php if ($conv['university']): ?>
                                                - <?php echo htmlspecialchars($conv['university']); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($conv['company_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #666;">
                            لا توجد محادثات
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-area">
                    <?php if ($current_conversation): ?>
                        <div class="chat-header">
                            <?php if ($user_type === 'company'): ?>
                                <?php 
                                $stmt = $conn->prepare('SELECT name, university, specialization FROM users WHERE id = ?');
                                $stmt->bind_param('i', $current_conversation['graduate_id']);
                                $stmt->execute();
                                $graduate_info = $stmt->get_result()->fetch_assoc();
                                ?>
                                المحادثة مع: <?php echo htmlspecialchars($graduate_info['name']); ?>
                                <?php if ($graduate_info['university']): ?>
                                    - <?php echo htmlspecialchars($graduate_info['university']); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php 
                                $stmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
                                $stmt->bind_param('i', $current_conversation['company_id']);
                                $stmt->execute();
                                $company_name = $stmt->get_result()->fetch_assoc()['name'];
                                ?>
                                المحادثة مع: <?php echo htmlspecialchars($company_name); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="messages-container" id="messages-container">
                            <?php if ($messages->num_rows > 0): ?>
                                <?php while ($message = $messages->fetch_assoc()): ?>
                                    <div class="message <?php echo ($message['sender_id'] == $user_id) ? 'sent' : 'received'; ?>">
                                        <div class="message-sender">
                                            <?php echo htmlspecialchars($message['sender_name']); ?>
                                            <?php if ($message['user_type'] == 'graduate'): ?>
                                                <span class="sender-type">(خريج)</span>
                                            <?php else: ?>
                                                <span class="sender-type">(شركة)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-bubble">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="text-align: center; color: #666; margin-top: 50px;">
                                    ابدأ المحادثة بإرسال رسالة
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="message-input-area">
                            <form method="post" class="message-input-form">
                                <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                                <textarea name="message" class="message-input" placeholder="اكتب رسالتك هنا..." required maxlength="2000" rows="3"></textarea>
                                <button type="submit" name="send_message" class="send-button">إرسال</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-conversation">
                            اختر محادثة لعرضها
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        // Scroll to bottom on page load
        window.addEventListener('load', scrollToBottom);
        
        // Handle form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.message-input-form');
            const textarea = document.querySelector('.message-input');
            
            if (form && textarea) {
                form.addEventListener('submit', function(e) {
                    const message = textarea.value.trim();
                    if (message.length === 0) {
                        e.preventDefault();
                        alert('يرجى كتابة رسالة');
                        return false;
                    }
                    if (message.length > 2000) {
                        e.preventDefault();
                        alert('الرسالة طويلة جداً. الحد الأقصى 2000 حرف');
                        return false;
                    }
                    // Don't clear the textarea here - let the server handle it
                });
                
                // Auto-resize textarea
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }
        });
        
        // Auto-refresh messages every 10 seconds (less frequent to avoid data loss)
        setInterval(function() {
            if (<?php echo $conversation_id; ?> > 0) {
                const textarea = document.querySelector('.message-input');
                if (textarea && textarea.value.trim() === '') {
                    location.reload();
                }
            }
        }, 10000);
    </script>
</body>
</html>


