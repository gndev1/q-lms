<?php
session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/quiz_data.php';

// Only children can take quizzes
if (!is_child()) {
    header('Location: index.php');
    exit;
}
$user = get_current_user();
$child_id = $user['id'];

// Validate quiz_id
if (!isset($_GET['quiz_id'])) {
    header('Location: child_dashboard.php');
    exit;
}
$quiz_id = (int)$_GET['quiz_id'];
$quiz = get_quiz($quiz_id);
if (!$quiz) {
    header('Location: child_dashboard.php');
    exit;
}

// Ensure the child has completed the associated course
$course_id = $quiz['course_id'];
$db = get_db();
$stmt = $db->prepare('SELECT completed FROM enrollments WHERE child_id = ? AND course_id = ?');
$stmt->bind_param('ii', $child_id, $course_id);
$stmt->execute();
$stmt->bind_result($completed);
if (!$stmt->fetch() || !$completed) {
    // Not enrolled or not completed
    header('Location: child_dashboard.php');
    exit;
}
$stmt->close();

// Check attempt interval
$lastAttempt = get_last_quiz_attempt_date($child_id, $quiz_id);
if ($lastAttempt) {
    $interval = new DateInterval('P' . $quiz['quiz_interval_days'] . 'D');
    $nextAllowed = clone $lastAttempt;
    $nextAllowed->add($interval);
    $now = new DateTime();
    if ($now < $nextAllowed) {
        $remaining = $now->diff($nextAllowed);
        $waitMsg = $remaining->format('%a days, %h hours, %i minutes');
        // Redirect with message
        $_SESSION['msg'] = 'You can attempt this quiz again in ' . $waitMsg . '.';
        header('Location: child_dashboard.php');
        exit;
    }
}

// Get quiz questions from the quiz_data file
global $QUIZZES;
if (!isset($QUIZZES[$quiz_id])) {
    die('Quiz data not found.');
}
$questions = $QUIZZES[$quiz_id];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process answers
    $score = 0;
    foreach ($questions as $idx => $q) {
        $answer = isset($_POST['q' . $idx]) ? (int)$_POST['q' . $idx] : -1;
        if ($answer === $q['answer']) {
            $score += (int)($quiz['max_score'] / count($questions));
        }
    }
    // Record attempt and tokens
    $result = record_quiz_attempt($child_id, $quiz_id, $score);
    $message = 'You scored ' . $score . ' out of ' . $quiz['max_score'] . '.';
    if ($result['tokens_awarded'] > 0) {
        $message .= ' You earned a bonus of ' . $result['tokens_awarded'] . ' tokens for a perfect score!';
    }
    $message .= ' <a href="child_dashboard.php">Return to dashboard</a>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quiz['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2><?= htmlspecialchars($quiz['title']) ?></h2>
    <p><a href="child_dashboard.php">&laquo; Back to Dashboard</a></p>
    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php else: ?>
        <form method="post">
            <?php foreach ($questions as $index => $q): ?>
                <fieldset>
                    <legend><?= htmlspecialchars($q['q']) ?></legend>
                    <?php foreach ($q['options'] as $optIdx => $option): ?>
                        <label>
                            <input type="radio" name="q<?= $index ?>" value="<?= $optIdx ?>" required>
                            <?= htmlspecialchars($option) ?>
                        </label><br>
                    <?php endforeach; ?>
                </fieldset>
                <br>
            <?php endforeach; ?>
            <button type="submit">Submit Quiz</button>
        </form>
    <?php endif; ?>
</body>
</html>