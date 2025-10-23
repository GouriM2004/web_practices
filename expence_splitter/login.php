<?php
// login.php
session_start();
require_once 'includes/autoload.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') header('Location: index.php');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if (!$email || !$password) header('Location: index.php?msg=' . urlencode('All fields required'));
$userModel = new User();
$user = $userModel->findByEmail($email);
if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    header('Location: dashboard.php');
} else {
    header('Location: index.php?msg=' . urlencode('Invalid credentials'));
}
