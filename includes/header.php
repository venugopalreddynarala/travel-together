<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Together</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light'; ?>">
    <header class="header">
        <nav class="navbar">
            <div class="logo">Travel Together</div>
            <button class="nav-toggle" aria-label="Toggle navigation">☰</button>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="create_group.php">Create Group</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="change_password.php">Change Password</a></li>
                    <li><a href="notifications.php">Notifications
                        <?php
                        require_once 'db.php';
                        if (tableExists($pdo, 'notifications')) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
                            $stmt->execute([$_SESSION['user_id']]);
                            $unread = $stmt->fetchColumn();
                            if ($unread > 0) {
                                echo "<span class='notification-badge'>$unread</span>";
                            }
                        }
                        ?>
                    </a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <li><button id="theme-toggle" class="btn-theme">Toggle Theme</button></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container">