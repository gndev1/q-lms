<?php
session_start();
require_once __DIR__ . '/functions.php';

// Only parents may access this page
if (!is_parent()) {
    header('Location: index.php');
    exit;
}

$current_user = get_current_user();
$parent_id = $current_user['id'];

// Handle creation of a new child account
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add child
    if (isset($_POST['add_child'])) {
        $child_username = trim($_POST['child_username']);
        $child_password = $_POST['child_password'];
        $child_confirm  = $_POST['child_confirm'];
        $child_name     = trim($_POST['child_name']);
        if ($child_username === '' || $child_password === '') {
            $message = 'Child username and password are required.';
        } elseif ($child_password !== $child_confirm) {
            $message = 'Child passwords do not match.';
        } else {
            $result = register_child($parent_id, $child_username, $child_password, $child_name ?: null);
            $message = $result['success'] ? 'Child account created successfully.' : $result['message'];
        }
    }
    // Enrol child in course
    if (isset($_POST['enrol'])) {
        $childId = (int)$_POST['child_id'];
        $courseId = (int)$_POST['course_id'];
        enrol_child_in_course($childId, $courseId);
        $message = 'Enrolled child in course.';
    }
    // Add new prize item
    if (isset($_POST['add_item'])) {
        $item_name  = trim($_POST['item_name']);
        $item_desc  = trim($_POST['item_desc']);
        $item_cost  = (int)$_POST['item_cost'];
        if ($item_name === '' || $item_cost <= 0) {
            $message = 'Prize name and positive cost are required.';
        } else {
            add_prize_item($parent_id, $item_name, $item_desc, $item_cost);
            $message = 'Prize item added.';
        }
    }
}

// Unenroll via GET
if (isset($_GET['unenrol'])) {
    $childId = (int)$_GET['child_id'];
    $courseId = (int)$_GET['course_id'];
    unenrol_child_from_course($childId, $courseId);
    $message = 'Removed child from course.';
}

// Delete prize item via GET
if (isset($_GET['delete_item'])) {
    $itemId = (int)$_GET['item_id'];
    delete_prize_item($parent_id, $itemId);
    $message = 'Prize item removed.';
}

// Fetch data for display
$children = get_children_of_parent($parent_id);
$courses  = get_all_courses();
$prizeItems = get_prize_items($parent_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parent Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Parent/Teacher Dashboard</h2>
    <p>Hello, <?= htmlspecialchars($current_user['name'] ?: $current_user['username']) ?>! [<a href="logout.php">Logout</a>]</p>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <h3>My Children / Students</h3>
    <?php if (empty($children)): ?>
        <p>No child accounts yet.</p>
    <?php else: ?>
        <?php foreach ($children as $child): ?>
            <div class="child-block">
                <strong><?= htmlspecialchars($child['username']) ?></strong>
                <?php if ($child['name']): ?> (<?= htmlspecialchars($child['name']) ?>)<?php endif; ?>
                — Tokens: <?= get_token_balance($child['id']) ?><br>
                <em>Enrolled courses:</em>
                <ul>
                    <?php
                    // Fetch the child's enrollments
                    $db = get_db();
                    $stmt = $db->prepare('SELECT e.course_id, e.completed, c.title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.child_id = ?');
                    $stmt->bind_param('i', $child['id']);
                    $stmt->execute();
                    $enrols = $stmt->get_result();
                    $enrolledCourseIds = [];
                    while ($en = $enrols->fetch_assoc()) {
                        $enrolledCourseIds[] = $en['course_id'];
                        echo '<li>' . htmlspecialchars($en['title']);
                        if ($en['completed']) {
                            echo ' (completed)';
                        }
                        echo ' <a href="?unenrol=1&child_id=' . $child['id'] . '&course_id=' . $en['course_id'] . '" class="small">[Unenrol]</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
                <!-- Enrol in new course -->
                <form method="post" class="enrol-form">
                    <input type="hidden" name="child_id" value="<?= $child['id'] ?>">
                    <select name="course_id">
                        <?php foreach ($courses as $course): ?>
                            <?php if (!in_array($course['id'], $enrolledCourseIds)): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="enrol">Enrol</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h3>Add a New Child/Student</h3>
    <form method="post" class="child-form">
        <input type="hidden" name="add_child" value="1">
        <label>Child Username:<br><input type="text" name="child_username" required></label><br>
        <label>Password:<br><input type="password" name="child_password" required></label><br>
        <label>Confirm Password:<br><input type="password" name="child_confirm" required></label><br>
        <label>Name (optional):<br><input type="text" name="child_name"></label><br>
        <button type="submit">Create Child Account</button>
    </form>

    <h3>My Prize Shop</h3>
    <?php if (empty($prizeItems)): ?>
        <p>No prize items yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($prizeItems as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                    (Cost: <?= (int)$item['cost'] ?> tokens)
                    <?php if ($item['description']): ?> – <?= htmlspecialchars($item['description']) ?><?php endif; ?>
                    <a href="?delete_item=1&item_id=<?= $item['id'] ?>" class="small">[Delete]</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <h4>Add Prize Item</h4>
    <form method="post" class="prize-form">
        <input type="hidden" name="add_item" value="1">
        <label>Item Name:<br><input type="text" name="item_name" required></label><br>
        <label>Description:<br><textarea name="item_desc"></textarea></label><br>
        <label>Cost (tokens):<br><input type="number" name="item_cost" min="1" required></label><br>
        <button type="submit">Add Item</button>
    </form>

</body>
</html>