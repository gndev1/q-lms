<?php
session_start();
require_once __DIR__ . '/functions.php';
// If already logged in redirect
if (get_current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if (login_user($username, $password)) {
        // Redirect based on role
        if (is_parent()) {
            header('Location: parent_dashboard.php');
        } elseif (is_child()) {
            header('Location: child_dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Login</h2>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
        <p class="success">Registration successful. Please log in.</p>
    <?php endif; ?>
    <form method="post" action="login.php">
        <label for="username">Username:</label><br>
        <input type="text" name="username" id="username" required><br><br>
        <label for="password">Password:</label><br>
        <input type="password" name="password" id="password" required><br><br>
        <input type="submit" value="Login">
    </form>
    <p><a href="index.php">Back to home</a></p>
</body>
</html>