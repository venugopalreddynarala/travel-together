<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $csrf_token = $_POST['csrf_token'];

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = "Email or username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, bio) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $bio])) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Registration failed.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<section class="form-section animate">
    <h2>Register</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="bio">Bio (Optional):</label>
            <textarea id="bio" name="bio"></textarea>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</section>
<?php include 'includes/footer.php'; ?>