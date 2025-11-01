<?php
session_start();
require_once __DIR__ . '/functions.php';
logout_user();
header('Location: index.php');
exit;