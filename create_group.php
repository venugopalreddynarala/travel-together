<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $type = $_POST['type'];
    $csrf_token = $_POST['csrf_token'];

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO groups (name, description, type, owner_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $type, $_SESSION['user_id']])) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Failed to create group.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<section class="form-section animate">
    <h2>Create a Group</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label for="name">Group Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        <div class="form-group">
            <label for="type">Type:</label>
            <select id="type" name="type">
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
        </div>
        <button type="submit" class="btn">Create Group</button>
    </form>
</section>
<?php include 'includes/footer.php'; ?>