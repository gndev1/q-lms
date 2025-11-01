<?php
session_start();
require_once __DIR__ . '/functions.php';

// Only children can view courses
if (!is_child()) {
    header('Location: index.php');
    exit;
}
$user = get_current_user();
$child_id = $user['id'];

// Validate course_id
if (!isset($_GET['course_id'])) {
    header('Location: child_dashboard.php');
    exit;
}
$course_id = (int)$_GET['course_id'];

// Ensure the child is enrolled in this course
$db = get_db();
$stmt = $db->prepare('SELECT completed FROM enrollments WHERE child_id = ? AND course_id = ?');
$stmt->bind_param('ii', $child_id, $course_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    // Not enrolled
    header('Location: child_dashboard.php');
    exit;
}
$stmt->bind_result($completed);
$stmt->fetch();
$course = get_course($course_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($course['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        iframe { width: 100%; height: 500px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($course['title']) ?></h2>
    <p><?= htmlspecialchars($course['description']) ?></p>
    <p><a href="child_dashboard.php">&laquo; Back to Dashboard</a></p>
    <h3>Course Content</h3>
    <?php
    // Attempt to locate a SCORM package directory for this course
    $packageDir = __DIR__ . '/scorm_packages/' . $course_id;
    $indexFile  = $packageDir . '/index.html';
    if (is_dir($packageDir) && file_exists($indexFile)) {
        // Provide an iframe to display the SCORM package
        $relativePath = 'scorm_packages/' . $course_id . '/index.html';
        echo '<iframe src="' . htmlspecialchars($relativePath) . '"></iframe>';
        echo '<p>When you finish the course, click the button below to mark it as completed.</p>';
    } else {
        // Placeholder content when no SCORM package exists
        echo '<p>Course content goes here. There is no SCORM package uploaded for this course yet.</p>';
    }
    ?>
    <?php if (!$completed): ?>
        <form method="get" action="child_dashboard.php">
            <input type="hidden" name="complete_course" value="1">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <button type="submit">Mark Course Completed</button>
        </form>
    <?php else: ?>
        <p><em>You have already completed this course.</em></p>
    <?php endif; ?>
</body>
</html>