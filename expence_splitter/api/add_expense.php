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
if ($group_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid group']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$paid_by = intval($_POST['paid_by'] ?? 0);
$category = trim($_POST['category'] ?? '') ?: null;
$shared_with = $_POST['shared_with'] ?? [];
$split_mode = $_POST['split_mode'] ?? 'equal';

// prepare split_values similar to previous logic
$split_values = null;
if ($split_mode === 'percentage') {
    $split_values = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'pct_') === 0) {
            $uid = intval(substr($k, 4));
            $split_values[$uid] = floatval($v);
        }
    }
} elseif ($split_mode === 'custom') {
    $split_values = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'amt_') === 0) {
            $uid = intval(substr($k, 4));
            $split_values[$uid] = floatval($v);
        }
    }
}

$expenseModel = new Expense();
// validate
if (!$title || $amount <= 0 || $paid_by <= 0 || !is_array($shared_with) || count($shared_with) == 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid expense data']);
    exit;
}

$eid = $expenseModel->addExpense($group_id, $title, $amount, $paid_by, $shared_with, $split_mode, $split_values, $category);
if (!$eid) {
    echo json_encode(['success' => false, 'error' => 'Failed to add expense']);
    exit;
}

// After adding, return updated group data by calling group_data endpoint logic inline
$db = Database::getConnection();
$calculator = new Calculator();
$balances = $calculator->computeBalances($group_id);

// totals per user
$members = (new Group())->getMembers($group_id);
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

// months
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

// categories
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

// summary for current user
$userMonthTotal = 0.0;
$stmt = $db->prepare('SELECT SUM(amount) as total FROM expenses WHERE group_id = ? AND paid_by = ? AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())');
if ($stmt) {
    $stmt->bind_param('ii', $group_id, $user_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $userMonthTotal = round((float)($r['total'] ?? 0), 2);
}
$summaryText = '';
if ($userMonthTotal > 0) {
    $stmt = $db->prepare('SELECT COALESCE(category, \'Uncategorized\') as category, SUM(amount) as total FROM expenses WHERE group_id = ? AND paid_by = ? AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) GROUP BY category ORDER BY total DESC');
    if ($stmt) {
        $stmt->bind_param('ii', $group_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $userCat = [];
        foreach ($res as $r) $userCat[$r['category']] = round((float)$r['total'], 2);
        $top = array_slice($userCat, 0, 3, true);
        $pieces = [];
        foreach ($top as $c => $amt) {
            $pct = round(($amt / $userMonthTotal) * 100);
            $pieces[] = "$pct% on $c";
        }
        $summaryText = sprintf('You spent ₹%s this month — %s.', number_format($userMonthTotal, 2), implode(', ', $pieces));
    }
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
    'msg' => 'Expense added',
    'balances' => $balances,
    'userTotals' => $userTotals,
    'userTotalsLabels' => $userTotalsLabels,
    'userTotalsData' => $userTotalsData,
    'months' => ['labels' => array_keys($months), 'data' => array_values($months)],
    'categories' => ['labels' => array_keys($catTotals), 'data' => array_values($catTotals)],
    'expenses_html' => $expHtml,
    'summaryText' => $summaryText
]);
