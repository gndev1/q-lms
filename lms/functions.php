<?php
/*
 * Collection of helper functions for the Simple Learning Management System.
 *
 * These routines encapsulate common database operations such as user
 * registration, course enrolment, token management, and learning record
 * statement logging.  Each function expects that sessions have already
 * started when called from web pages.
 */

require_once __DIR__ . '/config.php';

/**
 * Register a new parent/guardian/teacher account.
 *
 * @param string $username Desired login name.
 * @param string $password Plain‑text password (will be hashed).
 * @param string|null $name Optional display name.
 * @return array An associative array with keys 'success' (bool) and
 *               optionally 'message' on error.
 */
function register_parent($username, $password, $name = null) {
    $db = get_db();
    // Check for existing username
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    $stmt->close();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password_hash, role, name) VALUES (?, ?, "parent", ?)');
    $stmt->bind_param('sss', $username, $hash, $name);
    if ($stmt->execute()) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Register a new child account for a given parent.
 *
 * @param int $parent_id ID of the parent user creating this child.
 * @param string $username Unique username for the child.
 * @param string $password Child's password.
 * @param string|null $name Optional name for the child.
 * @return array
 */
function register_child($parent_id, $username, $password, $name = null) {
    $db = get_db();
    // Ensure parent exists and is of correct role
    $stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND role = "parent"');
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid parent'];
    }
    $stmt->close();
    // Check if username already taken
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    $stmt->close();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password_hash, role, parent_id, name) VALUES (?, ?, "child", ?, ?)');
    $stmt->bind_param('ssiss', $username, $hash, $parent_id, $name);
    $success = $stmt->execute();
    if ($success) {
        $child_id = $stmt->insert_id;
        // Initialise token counter for the child
        $tstmt = $db->prepare('INSERT INTO child_tokens (child_id) VALUES (?)');
        $tstmt->bind_param('i', $child_id);
        $tstmt->execute();
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Failed to create child account'];
}

/**
 * Attempt to authenticate a user.  On success the user's ID and role are
 * stored in the session.
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
function login_user($username, $password) {
    $db = get_db();
    $stmt = $db->prepare('SELECT id, password_hash, role FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            return true;
        }
    }
    return false;
}

/**
 * Log the current user out and clear session variables.
 */
function logout_user() {
    $_SESSION = [];
    session_destroy();
}

/**
 * Retrieve information about the currently logged‑in user.
 *
 * @return array|null Associative array containing user fields or null if not logged in.
 */
function get_current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Whether the currently logged in user has the parent role.
 *
 * @return bool
 */
function is_parent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'parent';
}

/**
 * Whether the currently logged in user has the child role.
 *
 * @return bool
 */
function is_child() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'child';
}

/**
 * Fetch all children belonging to a given parent.
 *
 * @param int $parent_id
 * @return array Array of associative arrays for each child user.
 */
function get_children_of_parent($parent_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE parent_id = ? ORDER BY id');
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $children = [];
    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }
    return $children;
}

/**
 * Retrieve all available courses.
 *
 * @return array
 */
function get_all_courses() {
    $db = get_db();
    $result = $db->query('SELECT * FROM courses ORDER BY id');
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Retrieve course information by ID.
 *
 * @param int $course_id
 * @return array|null
 */
function get_course($course_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Enrol a child in a course.  Does nothing if the child is already
 * enrolled.
 *
 * @param int $child_id
 * @param int $course_id
 */
function enrol_child_in_course($child_id, $course_id) {
    $db = get_db();
    // Check existing enrolment
    $stmt = $db->prepare('SELECT id FROM enrollments WHERE child_id = ? AND course_id = ?');
    $stmt->bind_param('ii', $child_id, $course_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        $insert = $db->prepare('INSERT INTO enrollments (child_id, course_id) VALUES (?, ?)');
        $insert->bind_param('ii', $child_id, $course_id);
        $insert->execute();
    }
}

/**
 * Unenrol a child from a course.  Any associated completion data and quiz
 * attempts remain untouched but will no longer be visible.
 *
 * @param int $child_id
 * @param int $course_id
 */
function unenrol_child_from_course($child_id, $course_id) {
    $db = get_db();
    $stmt = $db->prepare('DELETE FROM enrollments WHERE child_id = ? AND course_id = ?');
    $stmt->bind_param('ii', $child_id, $course_id);
    $stmt->execute();
}

/**
 * Mark a course as completed for a child and award tokens.  Records an LRS
 * statement.  If the course is already marked as completed then the
 * operation does nothing.
 *
 * @param int $child_id
 * @param int $course_id
 */
function complete_course($child_id, $course_id) {
    $db = get_db();
    // Only update if not already completed
    $stmt = $db->prepare('SELECT completed FROM enrollments WHERE child_id = ? AND course_id = ?');
    $stmt->bind_param('ii', $child_id, $course_id);
    $stmt->execute();
    $stmt->bind_result($completed);
    if ($stmt->fetch() && !$completed) {
        $stmt->close();
        // Mark as completed
        $update = $db->prepare('UPDATE enrollments SET completed = 1, completion_date = NOW() WHERE child_id = ? AND course_id = ?');
        $update->bind_param('ii', $child_id, $course_id);
        $update->execute();
        // Award tokens
        $course = get_course($course_id);
        if ($course) {
            add_tokens($child_id, $course['tokens_awarded']);
        }
        // Record LRS statement
        record_statement($child_id, 'completed', 'course', $course_id, json_encode(['success' => true]));
    }
}

/**
 * Fetch the last quiz attempt date for a child on a given quiz.
 *
 * @param int $child_id
 * @param int $quiz_id
 * @return DateTime|null
 */
function get_last_quiz_attempt_date($child_id, $quiz_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT attempt_date FROM quiz_attempts WHERE child_id = ? AND quiz_id = ? ORDER BY attempt_date DESC LIMIT 1');
    $stmt->bind_param('ii', $child_id, $quiz_id);
    $stmt->execute();
    $stmt->bind_result($date);
    if ($stmt->fetch()) {
        return new DateTime($date);
    }
    return null;
}

/**
 * Create a new quiz attempt entry, determine pass/fail and award tokens.
 *
 * @param int $child_id
 * @param int $quiz_id
 * @param int $score
 * @return array result array containing pass, tokens_awarded and message
 */
function record_quiz_attempt($child_id, $quiz_id, $score) {
    $db = get_db();
    // Determine passing threshold as 50% of max_score
    $quiz = get_quiz($quiz_id);
    if (!$quiz) {
        return ['success' => false, 'message' => 'Quiz not found'];
    }
    $max_score = $quiz['max_score'];
    $passed = ($score >= ($max_score / 2)) ? 1 : 0;
    // Insert attempt
    $stmt = $db->prepare('INSERT INTO quiz_attempts (child_id, quiz_id, attempt_date, score, passed) VALUES (?, ?, NOW(), ?, ?)');
    $stmt->bind_param('iiii', $child_id, $quiz_id, $score, $passed);
    $stmt->execute();
    $tokens_awarded = 0;
    if ($passed) {
        // Award base course tokens only once at completion; here we award bonus tokens for perfect score
        if ($score >= $max_score) {
            $tokens_awarded = $quiz['tokens_perfect'];
            add_tokens($child_id, $tokens_awarded);
        }
    }
    // Record LRS statement
    $resultData = ['score' => $score, 'max' => $max_score, 'passed' => $passed == 1];
    record_statement($child_id, 'attempted', 'quiz', $quiz_id, json_encode($resultData));
    return ['success' => true, 'passed' => $passed, 'tokens_awarded' => $tokens_awarded];
}

/**
 * Retrieve quiz information by ID.
 *
 * @param int $quiz_id
 * @return array|null
 */
function get_quiz($quiz_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM quizzes WHERE id = ?');
    $stmt->bind_param('i', $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Fetch the quiz associated with a course.  Returns null if none exists.
 *
 * @param int $course_id
 * @return array|null
 */
function get_quiz_by_course($course_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM quizzes WHERE course_id = ?');
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Award tokens to a child.  Tokens are added to tokens_earned in child_tokens.
 *
 * @param int $child_id
 * @param int $amount
 */
function add_tokens($child_id, $amount) {
    $db = get_db();
    $stmt = $db->prepare('UPDATE child_tokens SET tokens_earned = tokens_earned + ? WHERE child_id = ?');
    $stmt->bind_param('ii', $amount, $child_id);
    $stmt->execute();
}

/**
 * Deduct tokens from a child's balance when spending in the prize shop.
 *
 * @param int $child_id
 * @param int $amount
 */
function spend_tokens($child_id, $amount) {
    $db = get_db();
    $stmt = $db->prepare('UPDATE child_tokens SET tokens_spent = tokens_spent + ? WHERE child_id = ?');
    $stmt->bind_param('ii', $amount, $child_id);
    $stmt->execute();
}

/**
 * Get the current token balance for a child.
 *
 * @param int $child_id
 * @return int
 */
function get_token_balance($child_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT tokens_earned, tokens_spent FROM child_tokens WHERE child_id = ?');
    $stmt->bind_param('i', $child_id);
    $stmt->execute();
    $stmt->bind_result($earned, $spent);
    if ($stmt->fetch()) {
        return $earned - $spent;
    }
    return 0;
}

/**
 * Record an xAPI‑style statement to the learning record store.  Result is
 * stored as JSON.
 *
 * @param int $actor_id
 * @param string $verb (e.g. 'started', 'completed', 'attempted')
 * @param string $object_type (e.g. 'course', 'quiz')
 * @param int $object_id
 * @param string $result JSON encoded result details
 */
function record_statement($actor_id, $verb, $object_type, $object_id, $result) {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO lrs_statements (actor_id, verb, object_type, object_id, result, timestamp) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->bind_param('issis', $actor_id, $verb, $object_type, $object_id, $result);
    $stmt->execute();
}

/**
 * Retrieve all prize items for a given parent.
 *
 * @param int $parent_id
 * @return array
 */
function get_prize_items($parent_id) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM prize_items WHERE parent_id = ?');
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Add a new prize item for a parent.
 *
 * @param int $parent_id
 * @param string $name
 * @param string $description
 * @param int $cost
 */
function add_prize_item($parent_id, $name, $description, $cost) {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO prize_items (parent_id, name, description, cost) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('issi', $parent_id, $name, $description, $cost);
    $stmt->execute();
}

/**
 * Remove a prize item owned by a parent.
 *
 * @param int $parent_id
 * @param int $item_id
 */
function delete_prize_item($parent_id, $item_id) {
    $db = get_db();
    $stmt = $db->prepare('DELETE FROM prize_items WHERE id = ? AND parent_id = ?');
    $stmt->bind_param('ii', $item_id, $parent_id);
    $stmt->execute();
}

/**
 * Process a prize purchase for a child.  Checks the child has enough
 * tokens, deducts the cost, creates an order record and records an LRS
 * statement.  Returns true on success, false on failure.
 *
 * @param int $child_id
 * @param int $item_id
 * @return bool
 */
function purchase_prize($child_id, $item_id) {
    $db = get_db();
    // Fetch item details
    $stmt = $db->prepare('SELECT id, cost FROM prize_items WHERE id = ?');
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    if (!$item) {
        return false;
    }
    $balance = get_token_balance($child_id);
    if ($balance < $item['cost']) {
        return false;
    }
    // Deduct tokens
    spend_tokens($child_id, $item['cost']);
    // Record order
    $stmt = $db->prepare('INSERT INTO prize_orders (child_id, item_id, order_date) VALUES (?, ?, NOW())');
    $stmt->bind_param('ii', $child_id, $item_id);
    $stmt->execute();
    // Record LRS statement
    record_statement($child_id, 'redeemed', 'prize', $item_id, json_encode(['cost' => $item['cost']]));
    return true;
}