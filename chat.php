<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
    header("Location: login.php");
    exit;
}

$group_id = (int)$_GET['group_id'];
$stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

// Check membership
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'approved'");
$stmt->execute([$group_id, $_SESSION['user_id']]);
$membership = $stmt->fetch();

if ($group['type'] == 'private' && !$membership && $group['owner_id'] != $_SESSION['user_id']) {
    die("You are not authorized to view this chat.");
}

// Fetch initial messages
$stmt = $pdo->prepare("SELECT cm.*, u.username FROM chat_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.group_id = ? ORDER BY cm.created_at");
$stmt->execute([$group_id]);
$messages = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<section class="chat-section animate">
    <h2>Chat - <?php echo htmlspecialchars($group['name']); ?></h2>
    <div class="chat-box" id="chat-box">
        <?php foreach ($messages as $msg): ?>
            <div class="message" data-id="<?php echo $msg['id']; ?>">
                <strong><?php echo htmlspecialchars($msg['username']); ?>:</strong>
                <p><?php echo htmlspecialchars($msg['message']); ?></p>
                <span><?php echo $msg['created_at']; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <form id="chat-form" class="chat-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
        <textarea name="message" placeholder="Type your message..." required></textarea>
        <button type="submit" class="btn">Send</button>
    </form>
</section>
<script>
// Real-time chat with AJAX
document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('chat.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const chatBox = document.getElementById('chat-box');
            const message = document.createElement('div');
            message.className = 'message';
            message.dataset.id = data.id;
            message.innerHTML = `<strong>${data.username}:</strong><p>${data.message}</p><span>${data.created_at}</span>`;
            chatBox.appendChild(message);
            chatBox.scrollTop = chatBox.scrollHeight;
            this.reset();
        } else {
            alert(data.error || 'Failed to send message.');
        }
    })
    .catch(error => console.error('Error:', error));
});

// Poll for new messages every 5 seconds
setInterval(() => {
    const chatBox = document.getElementById('chat-box');
    const lastMessageId = chatBox.lastElementChild ? chatBox.lastElementChild.dataset.id : 0;
    
    fetch(`chat.php?group_id=<?php echo $group_id; ?>&last_id=${lastMessageId}`)
    .then(response => response.json())
    .then(data => {
        if (data.messages) {
            data.messages.forEach(msg => {
                const message = document.createElement('div');
                message.className = 'message';
                message.dataset.id = msg.id;
                message.innerHTML = `<strong>${msg.username}:</strong><p>${msg.message}</p><span>${msg.created_at}</span>`;
                chatBox.appendChild(message);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
}, 5000);

// Handle POST requests for sending messages
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $csrf_token = $_POST['csrf_token'];
    $group_id = (int)$_POST['group_id'];

    if ($csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Invalid CSRF token.']);
        exit;
    } elseif (empty($message)) {
        echo json_encode(['error' => 'Message cannot be empty.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (group_id, user_id, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$group_id, $_SESSION['user_id'], $message])) {
        $message_id = $pdo->lastInsertId();
        // Notify group members
        $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ? AND status = 'approved'");
        $stmt->execute([$group_id, $_SESSION['user_id']]);
        $members = $stmt->fetchAll();
        foreach ($members as $member) {
            $notif = "New message in group '{$group['name']}' from {$_SESSION['username']}.";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->execute([$member['user_id'], $notif]);
        }
        echo json_encode([
            'success' => true,
            'id' => $message_id,
            'username' => $_SESSION['username'],
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode(['error' => 'Failed to send message.']);
    }
    exit;
}

// Handle GET requests for polling new messages
if (isset($_GET['last_id'])) {
    header('Content-Type: application/json');
    $last_id = (int)$_GET['last_id'];
    $stmt = $pdo->prepare("SELECT cm.*, u.username FROM chat_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.group_id = ? AND cm.id > ? ORDER BY cm.created_at");
    $stmt->execute([$group_id, $last_id]);
    echo json_encode(['messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
?>
</script>
<?php include 'includes/footer.php'; ?>