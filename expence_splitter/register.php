<?php
// register.php
session_start();
require_once 'includes/autoload.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') header('Location: index.php');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if (!$name || !$email || !$password) header('Location: index.php?msg=' . urlencode('All fields required'));
$userModel = new User();
if ($userModel->findByEmail($email)) header('Location: index.php?msg=' . urlencode('Email already registered'));
$uid = $userModel->create($name, $email, $password);
if ($uid) {
    $_SESSION['user_id'] = $uid;
    $_SESSION['user_name'] = $name;
    header('Location: dashboard.php');
} else {
    header('Location: index.php?msg=' . urlencode('Registration failed'));
}
