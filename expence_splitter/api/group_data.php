<?php
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/Config.php';
session_start();
header('Content-Type: application/json');
$group_id = intval($_GET['group_id'] ?? 0);
if ($group_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid group']);
    exit;
}

$db = Database::getConnection();
$expenseModel = new Expense();
$groupModel = new Group();
$calculator = new Calculator();

$members = $groupModel->getMembers($group_id);
$balances = $calculator->computeBalances($group_id);

$userTotals = [];
foreach ($members as $m) $userTotals[$m['id']] = 0.0;
$stmt = $db->prepare('SELECT paid_by, SUM(amount) as total FROM expenses WHERE group_id = ? GROUP BY paid_by');
if ($stmt) {
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($res as $r) $userTotals[intval($r['paid_by'])] = round((float)$r['total'], 2);
}

$months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $months[$m] = 0.0;
}
$stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total FROM expenses WHERE group_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY ym ORDER BY ym");
if ($stmt) {
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($res as $r) if (isset($months[$r['ym']])) $months[$r['ym']] = round((float)$r['total'], 2);
}

$catTotals = [];
$stmt = $db->prepare('SELECT COALESCE(category, \'Uncategorized\') as category, SUM(amount) as total FROM expenses WHERE group_id = ? GROUP BY category');
if ($stmt) {
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($res as $r) $catTotals[$r['category']] = round((float)$r['total'], 2);
}

// build expenses HTML snippet
$expenses = $expenseModel->getGroupExpenses($group_id);
$expHtml = '';
foreach ($expenses as $e) {
    $expHtml .= '<tr>';
    $expHtml .= '<td>' . htmlspecialchars($e['title']) . '</td>';
    $expHtml .= '<td>₹' . number_format($e['amount'], 2) . '</td>';
    $expHtml .= '<td>' . htmlspecialchars($e['paid_by_name']) . '</td>';
    $expHtml .= '<td>' . htmlspecialchars($e['created_at']) . '</td>';
    $expHtml .= '</tr>';
}

// prepare userTotals arrays (labels and data in members order)
$userTotalsLabels = [];
$userTotalsData = [];
foreach ($members as $m) {
    $userTotalsLabels[] = $m['name'];
    $userTotalsData[] = round($userTotals[$m['id']] ?? 0.0, 2);
}

echo json_encode([
    'success' => true,
    'balances' => $balances,
    'userTotals' => $userTotals,
    'userTotalsLabels' => $userTotalsLabels,
    'userTotalsData' => $userTotalsData,
    'months' => ['labels' => array_keys($months), 'data' => array_values($months)],
    'categories' => ['labels' => array_keys($catTotals), 'data' => array_values($catTotals)],
    'expenses_html' => $expHtml,
]);
