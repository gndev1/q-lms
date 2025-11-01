<?php
session_start();
require_once __DIR__ . '/functions.php';

$user = get_current_user();
// If logged in redirect to dashboard
if ($user) {
    if (is_parent()) {
        header('Location: parent_dashboard.php');
        exit;
    } elseif (is_child()) {
        header('Location: child_dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to the Learning Management System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Welcome to the Simple Learning Management System</h1>
    <p>This platform allows parents, teachers and guardians to enrol their children in courses, track progress and redeem rewards.</p>
    <p>
        <a href="register.php">Create Parent/Teacher Account</a> | 
        <a href="login.php">Login</a>
    </p>
</body>
</html>