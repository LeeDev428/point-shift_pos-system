<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$page_title = "Messages";
$user_id = $_SESSION['user_id'];

// Handle sending reply
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send') {
        $subject = trim($_POST['subject']);
        $message_text = trim($_POST['message']);
        $recipient_id = $_POST['recipient_id'];
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;
        
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message, parent_message_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $user_id, $recipient_id, $subject, $message_text, $parent_id);
        
        if ($stmt->execute()) {
            $message = "Reply sent successfully!";
            $message_type = "success";
        } else {
            $message = "Error sending reply: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Get all conversations from staff/cashiers
$conversations_query = "
    SELECT m.*, 
           sender.first_name as sender_first_name, 
           sender.last_name as sender_last_name,
           sender.role as sender_role,
           sender.email as sender_email,
           (SELECT COUNT(*) FROM messages WHERE parent_message_id = m.id OR id = m.id) as reply_count,
           (SELECT COUNT(*) FROM messages WHERE parent_message_id = m.id AND recipient_id = ? AND is_read = 0) as unread_replies
    FROM messages m
    JOIN users sender ON m.sender_id = sender.id
    WHERE (m.sender_id != ? OR m.recipient_id = ?)
    AND m.parent_message_id IS NULL
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($conversations_query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Filter conversations
$filter = $_GET['filter'] ?? 'all';
$filtered_conversations = $conversations;

if ($filter === 'unread') {
    $filtered_conversations = array_filter($conversations, function($conv) {
        return $conv['unread_replies'] > 0 || ($conv['recipient_id'] == $_SESSION['user_id'] && $conv['is_read'] == 0);
    });
} elseif ($filter === 'staff') {
    $filtered_conversations = array_filter($conversations, function($conv) {
        return $conv['sender_role'] === 'staff';
    });
} elseif ($filter === 'cashier') {
    $filtered_conversations = array_filter($conversations, function($conv) {
        return $conv['sender_role'] === 'cashier';
    });
}

// Get selected conversation thread
$selected_conversation = null;
$conversation_thread = [];

if (isset($_GET['conversation_id'])) {
    $conversation_id = $_GET['conversation_id'];
    
    // Get parent message
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.first_name as sender_first_name, 
               sender.last_name as sender_last_name,
               sender.role as sender_role,
               sender.email as sender_email
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        WHERE m.id = ?
    ");
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $selected_conversation = $stmt->get_result()->fetch_assoc();
    
    // Get all replies in thread
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.first_name as sender_first_name, 
               sender.last_name as sender_last_name,
               sender.role as sender_role
        FROM messages m
        JOIN users sender ON m.sender_id = sender.id
        WHERE m.parent_message_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $conversation_thread = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mark messages as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE (id = ? OR parent_message_id = ?) AND recipient_id = ?");
    $stmt->bind_param("iii", $conversation_id, $conversation_id, $user_id);
    $stmt->execute();
}

// Count statistics
$total_messages = count($conversations);
$unread_count = $conn->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = $user_id AND is_read = 0")->fetch_assoc()['count'];
$staff_messages = count(array_filter($conversations, fn($c) => $c['sender_role'] === 'staff'));
$cashier_messages = count(array_filter($conversations, fn($c) => $c['sender_role'] === 'cashier'));

ob_start();
?>

<style>
    .message-container {
        height: calc(100vh - 200px);
        overflow-y: auto;
    }
    .conversation-list {
        border-right: 1px solid #dee2e6;
        height: 100%;
        overflow-y: auto;
        background: white;
    }
    .conversation-item {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }
    .conversation-item:hover {
        background: #f8f9fa;
    }
    .conversation-item.active {
        background: #e7f3ff;
        border-left: 4px solid #dc3545;
    }
    .conversation-item.unread {
        background: #fff3cd;
    }
    .message-thread {
        padding: 1.5rem;
        height: calc(100% - 80px);
        overflow-y: auto;
        background: #f8f9fa;
    }
    .message-bubble {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        max-width: 70%;
    }
    .message-bubble.sent {
        background: #dc3545;
        color: white;
        margin-left: auto;
    }
    .message-bubble.received {
        background: white;
        color: #000;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .reply-form {
        border-top: 1px solid #dee2e6;
        padding: 1rem;
        background: white;
    }
</style>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Total Messages</h6>
                    <h3 class="mb-0"><?php echo $total_messages; ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-comments"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Unread</h6>
                    <h3 class="mb-0"><?php echo $unread_count; ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">From Staff</h6>
                    <h3 class="mb-0"><?php echo $staff_messages; ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">From Cashiers</h6>
                    <h3 class="mb-0"><?php echo $cashier_messages; ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-cash-register"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="content-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Staff & Cashier Messages</h5>
        <div class="btn-group">
            <a href="?filter=all<?php echo isset($_GET['conversation_id']) ? '&conversation_id=' . $_GET['conversation_id'] : ''; ?>" 
               class="btn btn-sm <?php echo $filter === 'all' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                All (<?php echo count($conversations); ?>)
            </a>
            <a href="?filter=unread<?php echo isset($_GET['conversation_id']) ? '&conversation_id=' . $_GET['conversation_id'] : ''; ?>" 
               class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                Unread (<?php echo $unread_count; ?>)
            </a>
            <a href="?filter=staff<?php echo isset($_GET['conversation_id']) ? '&conversation_id=' . $_GET['conversation_id'] : ''; ?>" 
               class="btn btn-sm <?php echo $filter === 'staff' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                Staff (<?php echo $staff_messages; ?>)
            </a>
            <a href="?filter=cashier<?php echo isset($_GET['conversation_id']) ? '&conversation_id=' . $_GET['conversation_id'] : ''; ?>" 
               class="btn btn-sm <?php echo $filter === 'cashier' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                Cashiers (<?php echo $cashier_messages; ?>)
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="row g-0 message-container">
            <!-- Conversations List -->
            <div class="col-md-4 conversation-list">
                <?php if (empty($filtered_conversations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No messages found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($filtered_conversations as $conv): ?>
                        <div class="conversation-item <?php echo isset($_GET['conversation_id']) && $_GET['conversation_id'] == $conv['id'] ? 'active' : ''; ?> <?php echo ($conv['unread_replies'] > 0 || ($conv['recipient_id'] == $user_id && $conv['is_read'] == 0)) ? 'unread' : ''; ?>" 
                             onclick="window.location.href='messages.php?conversation_id=<?php echo $conv['id']; ?>&filter=<?php echo $filter; ?>'">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($conv['subject']); ?>
                                        <?php if ($conv['unread_replies'] > 0 || ($conv['recipient_id'] == $user_id && $conv['is_read'] == 0)): ?>
                                            <span class="badge bg-danger">NEW</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($conv['sender_first_name'] . ' ' . $conv['sender_last_name']); ?>
                                        <span class="badge bg-<?php echo $conv['sender_role'] == 'staff' ? 'primary' : 'info'; ?> ms-1">
                                            <?php echo ucfirst($conv['sender_role']); ?>
                                        </span>
                                    </small>
                                    <p class="mb-0 mt-2 small text-truncate"><?php echo htmlspecialchars(substr($conv['message'], 0, 80)); ?></p>
                                </div>
                                <small class="text-muted ms-2"><?php echo date('M d', strtotime($conv['created_at'])); ?></small>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-reply me-1"></i><?php echo $conv['reply_count'] - 1; ?> replies
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Thread -->
            <div class="col-md-8">
                <?php if ($selected_conversation): ?>
                    <div class="p-3 bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($selected_conversation['subject']); ?></h5>
                                <small class="text-muted">
                                    From: <?php echo htmlspecialchars($selected_conversation['sender_first_name'] . ' ' . $selected_conversation['sender_last_name']); ?>
                                    (<?php echo htmlspecialchars($selected_conversation['sender_email']); ?>)
                                    • Started on <?php echo date('F d, Y \a\t g:i A', strtotime($selected_conversation['created_at'])); ?>
                                </small>
                            </div>
                            <span class="badge bg-<?php echo $selected_conversation['sender_role'] == 'staff' ? 'primary' : 'info'; ?> fs-6">
                                <?php echo ucfirst($selected_conversation['sender_role']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="message-thread">
                        <!-- Original Message -->
                        <div class="message-bubble <?php echo $selected_conversation['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong>
                                    <?php echo htmlspecialchars($selected_conversation['sender_first_name'] . ' ' . $selected_conversation['sender_last_name']); ?>
                                    <span class="badge bg-<?php echo $selected_conversation['sender_role'] == 'admin' ? 'danger' : ($selected_conversation['sender_role'] == 'staff' ? 'primary' : 'info'); ?> ms-1">
                                        <?php echo ucfirst($selected_conversation['sender_role']); ?>
                                    </span>
                                </strong>
                                <small><?php echo date('M d, Y g:i A', strtotime($selected_conversation['created_at'])); ?></small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($selected_conversation['message'])); ?></p>
                        </div>
                        
                        <!-- Replies -->
                        <?php foreach ($conversation_thread as $reply): ?>
                            <div class="message-bubble <?php echo $reply['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong>
                                        <?php echo htmlspecialchars($reply['sender_first_name'] . ' ' . $reply['sender_last_name']); ?>
                                        <span class="badge bg-<?php echo $reply['sender_role'] == 'admin' ? 'danger' : ($reply['sender_role'] == 'staff' ? 'primary' : 'info'); ?> ms-1">
                                            <?php echo ucfirst($reply['sender_role']); ?>
                                        </span>
                                    </strong>
                                    <small><?php echo date('M d, Y g:i A', strtotime($reply['created_at'])); ?></small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Reply Form -->
                    <div class="reply-form">
                        <form method="POST">
                            <input type="hidden" name="action" value="send">
                            <input type="hidden" name="recipient_id" value="<?php echo $selected_conversation['sender_id']; ?>">
                            <input type="hidden" name="parent_id" value="<?php echo $selected_conversation['id']; ?>">
                            <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($selected_conversation['subject']); ?>">
                            
                            <div class="input-group">
                                <textarea class="form-control" name="message" rows="3" placeholder="Type your reply..." required></textarea>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Select a conversation to view messages</h5>
                            <p class="text-muted">Messages from staff and cashiers will appear here</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
