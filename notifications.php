<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $success = "Notifications marked as read.";
}

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<section class="notification-section animate">
    <h2>Notifications</h2>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form method="POST" style="margin-bottom: 1rem;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" name="mark_read" class="btn">Mark All as Read</button>
    </form>
    <div class="notification-list">
        <?php if ($notifications): ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                    <p><?php echo htmlspecialchars($notif['message']); ?></p>
                    <span><?php echo $notif['created_at']; ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No notifications.</p>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>