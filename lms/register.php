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
    $confirm  = $_POST['confirm'];
    $name     = trim($_POST['name']);
    if ($username === '' || $password === '' || $confirm === '') {
        $error = 'Username and password are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = register_parent($username, $password, $name ?: null);
        if ($result['success']) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Parent/Teacher Account</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Create Parent/Teacher Account</h2>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" action="register.php">
        <label for="username">Username:</label><br>
        <input type="text" name="username" id="username" required><br><br>
        <label for="password">Password:</label><br>
        <input type="password" name="password" id="password" required><br><br>
        <label for="confirm">Confirm Password:</label><br>
        <input type="password" name="confirm" id="confirm" required><br><br>
        <label for="name">Your Name (optional):</label><br>
        <input type="text" name="name" id="name"><br><br>
        <input type="submit" value="Create Account">
    </form>
    <p><a href="index.php">Back to home</a></p>
</body>
</html>