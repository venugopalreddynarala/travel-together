<?php
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=not_logged_in");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    error_log("User not found for ID: $user_id");
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_STRING);
    $csrf_token = $_POST['csrf_token'];

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $allowed)) {
                $filename = "Uploads/" . uniqid() . "." . $ext;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filename)) {
                    $profile_picture = $filename;
                } else {
                    $error = "Failed to upload profile picture.";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG allowed.";
            }
        }

        if (!isset($error)) {
            $stmt = $pdo->prepare("UPDATE users SET bio = ?, profile_picture = ?, theme_preference = ? WHERE id = ?");
            if ($stmt->execute([$bio, $profile_picture, $theme, $user_id])) {
                $_SESSION['theme'] = $theme;
                $success = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<section class="profile-section animate">
    <h2>Manage Profile</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <div class="profile-container">
        <?php if ($user['profile_picture']): ?>
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
        <?php endif; ?>
        <form method="POST" class="profile-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            <div class="form-group">
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="profile_picture">Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            </div>
            <div class="form-group">
                <label for="theme">Theme Preference:</label>
                <select id="theme" name="theme">
                    <option value="light" <?php echo $user['theme_preference'] == 'light' ? 'selected' : ''; ?>>Light</option>
                    <option value="dark" <?php echo $user['theme_preference'] == 'dark' ? 'selected' : ''; ?>>Dark</option>
                </select>
            </div>
            <button type="submit" class="btn">Update Profile</button>
        </form>
    </div>
</section>
<?php include 'includes/footer.php'; ?>