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
    die("You are not authorized to create a plan.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $csrf_token = $_POST['csrf_token'];

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date cannot be before start date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO trip_plans (group_id, title, description, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$group_id, $title, $description, $start_date, $end_date, $_SESSION['user_id']])) {
            header("Location: group.php?id=$group_id");
            exit;
        } else {
            $error = "Failed to create trip plan.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<section class="form-section animate">
    <h2>Create Trip Plan for <?php echo htmlspecialchars($group['name']); ?></h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" required>
        </div>
        <button type="submit" class="btn">Create Plan</button>
    </form>
</section>
<?php include 'includes/footer.php'; ?>