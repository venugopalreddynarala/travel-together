<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['plan_id'])) {
    header("Location: login.php");
    exit;
}

$plan_id = (int)$_GET['plan_id'];
$stmt = $pdo->prepare("SELECT tp.*, g.name AS group_name, g.id AS group_id, u.username AS creator_name FROM trip_plans tp JOIN groups g ON tp.group_id = g.id JOIN users u ON tp.created_by = u.id WHERE tp.id = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    die("Trip plan not found.");
}

// Check membership
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'approved'");
$stmt->execute([$plan['group_id'], $_SESSION['user_id']]);
$membership = $stmt->fetch();

if ($plan['type'] == 'private' && !$membership && $plan['owner_id'] != $_SESSION['user_id']) {
    die("You are not authorized to view this plan.");
}

// Handle new comment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $csrf_token = $_POST['csrf_token'];

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } elseif (empty($comment)) {
        $error = "Comment cannot be empty.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO plan_comments (plan_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$plan_id, $_SESSION['user_id'], $comment]);
        $success = "Comment added.";
    }
}

// Fetch comments
$stmt = $pdo->prepare("SELECT pc.*, u.username FROM plan_comments pc JOIN users u ON pc.user_id = u.id WHERE pc.plan_id = ? ORDER BY pc.created_at");
$stmt->execute([$plan_id]);
$comments = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<section class="plan-section animate">
    <h2><?php echo htmlspecialchars($plan['title']); ?></h2>
    <p><strong>Group:</strong> <?php echo htmlspecialchars($plan['group_name']); ?></p>
    <p><strong>Description:</strong> <?php echo htmlspecialchars($plan['description']); ?></p>
    <p><strong>Start Date:</strong> <?php echo $plan['start_date']; ?></p>
    <p><strong>End Date:</strong> <?php echo $plan['end_date']; ?></p>
    <p><strong>Created By:</strong> <?php echo htmlspecialchars($plan['creator_name']); ?></p>
    <a href="group.php?id=<?php echo $plan['group_id']; ?>" class="btn">Back to Group</a>

    <h3>Comments</h3>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <div class="comment-box">
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                <span><?php echo $comment['created_at']; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($membership || $group['owner_id'] == $_SESSION['user_id'] || $group['type'] == 'public'): ?>
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <textarea name="comment" placeholder="Add a comment..." required></textarea>
            <button type="submit" class="btn">Post Comment</button>
        </form>
    <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>