<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle search and filter
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$query = "SELECT g.*, u.username AS owner_name FROM groups g JOIN users u ON g.owner_id = u.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND g.name LIKE ?";
    $params[] = "%$search%";
}
if ($type && in_array($type, ['public', 'private'])) {
    $query .= " AND g.type = ?";
    $params[] = $type;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$groups = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<section class="welcome-section">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <div class="search-filter">
        <form method="GET" class="form">
            <input type="text" name="search" placeholder="Search groups..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <select name="type">
                <option value="">All Types</option>
                <option value="public" <?php echo $type == 'public' ? 'selected' : ''; ?>>Public</option>
                <option value="private" <?php echo $type == 'private' ? 'selected' : ''; ?>>Private</option>
            </select>
            <button type="submit" class="btn">Search</button>
        </form>
    </div>
    <h3>Available Groups</h3>
    <div class="group-list">
        <?php if ($groups): ?>
            <?php foreach ($groups as $group): ?>
                <div class="group-card animate">
                    <h4><?php echo htmlspecialchars($group['name']); ?></h4>
                    <p><?php echo htmlspecialchars($group['description']); ?></p>
                    <p><strong>Type:</strong> <?php echo $group['type']; ?></p>
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($group['owner_name']); ?></p>
                    <a href="group.php?id=<?php echo $group['id']; ?>" class="btn">View Group</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No groups found.</p>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>