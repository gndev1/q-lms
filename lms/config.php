<?php
/*
 * Configuration file for the Simple Learning Management System.
 *
 * Update the database credentials below to match your LAMP environment.  The
 * application uses mysqli and will terminate execution if a connection cannot
 * be established.  Character encoding is forced to utf8mb4 to support
 * international text.
 */

// Database connection parameters
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'lms_db';

/**
 * Create and return a new MySQLi connection.
 *
 * @return mysqli A connected MySQLi object.
 */
function get_db() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($mysqli->connect_errno) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}
?>