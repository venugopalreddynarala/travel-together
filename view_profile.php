<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_GET['user_id'];
$stmt = $pdo->prepare("SELECT username, bio, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}
?>

<?php include 'includes/header.php'; ?>
<section class="profile-section animate">
    <h2><?php echo htmlspecialchars($user['username']); ?>'s Profile</h2>
    <?php if ($user['profile_picture']): ?>
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" style="max-width: 150px; border-radius: 50%; margin-bottom: 1rem;">
    <?php endif; ?>
    <p><strong>Bio:</strong> <?php echo htmlspecialchars($user['bio'] ?: 'No bio available.'); ?></p>
    <a href="javascript:history.back()" class="btn">Back</a>
</section>
<?php include 'includes/footer.php'; ?>