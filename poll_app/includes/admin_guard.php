<?php
// includes/admin_guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/bootstrap.php';

if (!Auth::check()) {
    header("Location: login.php");
    exit;
}
