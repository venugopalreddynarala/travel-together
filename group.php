<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$group_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT g.*, u.username AS owner_name FROM groups g JOIN users u ON g.owner_id = u.id WHERE g.id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

// Check membership status
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $_SESSION['user_id']]);
$membership = $stmt->fetch();

// Handle join request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join'])) {
    if ($membership) {
        $error = "You have already requested to join or are a member.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$group_id, $_SESSION['user_id']]);
        $success = "Join request sent.";
    }
}

// Handle member status update (for group owner)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status']) && $group['owner_id'] == $_SESSION['user_id']) {
    $member_id = (int)$_POST['member_id'];
    $status = $_POST['status'];
    $user_id = (int)$_POST['user_id'];
    $stmt = $pdo->prepare("UPDATE group_members SET status = ? WHERE id = ? AND group_id = ?");
    $stmt->execute([$status, $member_id, $group_id]);
    
    // Send notification to user
    $message = "Your request to join group '{$group['name']}' has been $status.";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
    
    $success = "Member status updated.";
}

// Handle delete plan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_plan'])) {
    $plan_id = (int)$_POST['plan_id'];
    $stmt = $pdo->prepare("SELECT * FROM trip_plans WHERE id = ? AND created_by = ?");
    $stmt->execute([$plan_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM trip_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $success = "Trip plan deleted.";
    } else {
        $error = "You are not authorized to delete this plan.";
    }
}

// Fetch members
$stmt = $pdo->prepare("SELECT gm.*, u.username FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ?");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll();

// Fetch trip plans
$stmt = $pdo->prepare("SELECT tp.*, u.username AS creator_name FROM trip_plans tp JOIN users u ON tp.created_by = u.id WHERE tp.group_id = ?");
$stmt->execute([$group_id]);
$plans = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<section class="group-section animate">
    <h2><?php echo htmlspecialchars($group['name']); ?></h2>
    <p><?php echo htmlspecialchars($group['description']); ?></p>
    <p><strong>Type:</strong> <?php echo $group['type']; ?></p>
    <p><strong>Owner:</strong> <?php echo htmlspecialchars($group['owner_name']); ?></p>

    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <?php if ($group['type'] == 'private' && !$membership && $group['owner_id'] != $_SESSION['user_id']): ?>
        <form method="POST">
            <button type="submit" name="join" class="btn">Request to Join</button>
        </form>
    <?php elseif ($membership && $membership['status'] == 'approved' || $group['type'] == 'public' || $group['owner_id'] == $_SESSION['user_id']): ?>
        <a href="chat.php?group_id=<?php echo $group_id; ?>" class="btn">Go to Chat</a>
        <a href="create_plan.php?group_id=<?php echo $group_id; ?>" class="btn">Create Trip Plan</a>
    <?php elseif ($membership && $membership['status'] == 'pending'): ?>
        <p>Your join request is pending.</p>
    <?php endif; ?>

    <?php if ($group['owner_id'] == $_SESSION['user_id']): ?>
        <h3>Manage Members</h3>
        <table class="member-table">
            <tr>
                <th>Username</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><a href="view_profile.php?user_id=<?php echo $member['user_id']; ?>"><?php echo htmlspecialchars($member['username']); ?></a></td>
                    <td><?php echo $member['status']; ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                            <select name="status">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="blocked">Blocked</option>
                            </select>
                            <button type="submit" name="update_status" class="btn">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h3>Trip Plans</h3>
    <?php if ($plans): ?>
        <div class="plan-list">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card animate">
                    <h4><?php echo htmlspecialchars($plan['title']); ?></h4>
                    <p><?php echo htmlspecialchars($plan['description']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo $plan['start_date']; ?></p>
                    <p><strong>End Date:</strong> <?php echo $plan['end_date']; ?></p>
                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($plan['creator_name']); ?></p>
                    <a href="view_plan.php?plan_id=<?php echo $plan['id']; ?>" class="btn">View Plan</a>
                    <?php if ($plan['created_by'] == $_SESSION['user_id']): ?>
                        <a href="edit_plan.php?plan_id=<?php echo $plan['id']; ?>" class="btn">Edit Plan</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <button type="submit" name="delete_plan" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this plan?');">Delete Plan</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No trip plans available.</p>
    <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>