<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['plan_id'])) {
    header("Location: login.php");
    exit;
}

$plan_id = (int)$_GET['plan_id'];
$stmt = $pdo->prepare("SELECT tp.*, g.id AS group_id FROM trip_plans tp JOIN groups g ON tp.group_id = g.id WHERE tp.id = ? AND tp.created_by = ?");
$stmt->execute([$plan_id, $_SESSION['user_id']]);
$plan = $stmt->fetch();

if (!$plan) {
    die("Trip plan not found or you are not authorized to edit it.");
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
        $stmt = $pdo->prepare("UPDATE trip_plans SET title = ?, description = ?, start_date = ?, end_date = ? WHERE id = ?");
        if ($stmt->execute([$title, $description, $start_date, $end_date, $plan_id])) {
            header("Location: group.php?id=" . $plan['group_id']);
            exit;
        } else {
            $error = "Failed to update trip plan.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<section class="form-section animate">
    <h2>Edit Trip Plan</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($plan['title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($plan['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $plan['start_date']; ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $plan['end_date']; ?>" required>
        </div>
        <button type="submit" class="btn">Update Plan</button>
    </form>
</section>
<?php include 'includes/footer.php'; ?>