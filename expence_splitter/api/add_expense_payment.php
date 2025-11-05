<?php
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/Config.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
$user_id = $_SESSION['user_id'];
$group_id = intval($_POST['group_id'] ?? 0);
$expense_id = intval($_POST['expense_id'] ?? 0);
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);

if ($group_id <= 0 || $expense_id <= 0 || $receiver_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$ep = new ExpensePayment();
$added = $ep->addPayment($expense_id, $user_id, $receiver_id, $amount);
if (!$added) {
    echo json_encode(['success' => false, 'error' => 'Failed to record payment']);
    exit;
}

// add notification and log
$notif = new Notification();
$u = (new User())->getById($user_id);
$uname = $u['name'] ?? $user_id;
$msg = sprintf('%s recorded a payment of %s towards expense #%d', $uname, number_format($amount, 2), $expense_id);
$notif->addNotifications([$receiver_id], $msg, $group_id);
$log = new Log();
$log->add($user_id, $group_id, sprintf('Recorded payment of %s for expense #%d to user %d', number_format($amount, 2), $expense_id, $receiver_id));

// return updated balances and simplified transactions
$calculator = new Calculator();
$balances = $calculator->computeBalances($group_id);
$simplified = $calculator->simplifyDebts($balances);

echo json_encode(['success' => true, 'msg' => 'Payment recorded', 'balances' => $balances, 'simplified' => $simplified]);
