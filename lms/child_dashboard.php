<?php
session_start();
require_once __DIR__ . '/functions.php';
// Only children may access
if (!is_child()) {
    header('Location: index.php');
    exit;
}
$user = get_current_user();
$child_id = $user['id'];
// Parent ID to fetch prize shop
$parent_id = $user['parent_id'];

// Handle purchase request
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['purchase_item'])) {
        $itemId = (int)$_POST['item_id'];
        if (purchase_prize($child_id, $itemId)) {
            $message = 'Prize redeemed successfully! Let your parent/teacher know.';
        } else {
            $message = 'Unable to redeem prize. Check your token balance.';
        }
    }
}

// Mark course completed if callback via GET param complete_course
if (isset($_GET['complete_course'])) {
    $courseId = (int)$_GET['course_id'];
    // Only mark if enrolled
    complete_course($child_id, $courseId);
    $message = 'Course marked as complete. You can now attempt the quiz.';
}

// Data retrieval
$db = get_db();
$stmt = $db->prepare('SELECT e.course_id, e.completed, e.completion_date, c.title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.child_id = ?');
$stmt->bind_param('i', $child_id);
$stmt->execute();
$enrols = $stmt->get_result();
$enrolledCourses = $enrols->fetch_all(MYSQLI_ASSOC);

$prizeItems = get_prize_items($parent_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Child Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Student Dashboard</h2>
    <p>Hello, <?= htmlspecialchars($user['name'] ?: $user['username']) ?>! [<a href="logout.php">Logout</a>]</p>
    <p>Your token balance: <strong><?= get_token_balance($child_id) ?></strong></p>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <h3>My Courses</h3>
    <?php if (empty($enrolledCourses)): ?>
        <p>You are not enrolled in any courses. Ask your parent/teacher to enrol you.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($enrolledCourses as $en): ?>
                <li>
                    <strong><?= htmlspecialchars($en['title']) ?></strong>
                    <?php if ($en['completed']): ?>
                        – Completed on <?= htmlspecialchars($en['completion_date']) ?>.
                        <?php
                        // Show quiz option
                        $quiz = get_quiz_by_course($en['course_id']);
                        if ($quiz) {
                            // Check last attempt date
                            $lastAttempt = get_last_quiz_attempt_date($child_id, $quiz['id']);
                            $canAttempt = true;
                            if ($lastAttempt) {
                                $interval = new DateInterval('P' . $quiz['quiz_interval_days'] . 'D');
                                $nextAllowed = clone $lastAttempt;
                                $nextAllowed->add($interval);
                                $now = new DateTime();
                                if ($now < $nextAllowed) {
                                    $canAttempt = false;
                                    $remaining = $now->diff($nextAllowed);
                                    $waitMsg = $remaining->format('%a days, %h hours, %i minutes');
                                }
                            }
                            if ($canAttempt): ?>
                                – <a href="quiz.php?quiz_id=<?= $quiz['id'] ?>">Attempt Quiz</a>
                            <?php else: ?>
                                – Next quiz attempt available in <?= $waitMsg ?>
                            <?php endif;
                        }
                        ?>
                    <?php else: ?>
                        – <a href="course.php?course_id=<?= $en['course_id'] ?>">Start Course</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Prize Shop</h3>
    <?php if (empty($prizeItems)): ?>
        <p>Your parent/teacher has not added any prizes yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($prizeItems as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                    (Cost: <?= (int)$item['cost'] ?> tokens)
                    <?php if ($item['description']): ?> – <?= htmlspecialchars($item['description']) ?><?php endif; ?>
                    <?php if (get_token_balance($child_id) >= $item['cost']): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="purchase_item" value="1">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button type="submit">Redeem</button>
                        </form>
                    <?php else: ?>
                        – Not enough tokens
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</body>
</html>