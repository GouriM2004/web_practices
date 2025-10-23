<?php
// manage_members.php
session_start();
require_once 'includes/autoload.php';
if (!isset($_SESSION['user_id'])) header('Location: index.php');
$user_id = $_SESSION['user_id'];
$groupModel = new Group();
$group_id = intval($_POST['group_id'] ?? 0);
if ($group_id <= 0) header('Location: dashboard.php?msg=' . urlencode('Invalid group'));
if (isset($_POST['invite'])) {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $userModel = new User();
        $u = $userModel->findByEmail($email);
        if ($u) {
            $groupModel->addMember($group_id, $u['id']);
            header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('User added if not already present'));
            exit;
        } else {
            header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('User not found — ask them to register, then add'));
            exit;
        }
    }
}
header('Location: view_group.php?id=' . $group_id);
