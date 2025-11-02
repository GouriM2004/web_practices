<?php
require_once __DIR__ . '/includes/autoload.php';
require_once __DIR__ . '/includes/Config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
$type = isset($_POST['type']) && in_array($_POST['type'], ['excel', 'pdf']) ? $_POST['type'] : 'excel';

// If Composer autoloader exists, load it so PhpSpreadsheet / TCPDF classes are available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (!$group_id) {
    $_SESSION['error'] = 'Invalid group';
    header('Location: view_group.php?id=' . $group_id);
    exit;
}

$groupModel = new Group();
$group = $groupModel->getGroup($group_id);
if (!$group) {
    $_SESSION['error'] = 'Group not found';
    header('Location: index.php');
    exit;
}

// Collect data: group, members, expenses, balances
$expenseModel = new Expense();
$calc = new Calculator();
$members = $groupModel->getMembers($group_id);
$expenses = $expenseModel->getGroupExpenses($group_id); // assume this method exists; fallback to query if not
$balances = $calc->computeBalances($group_id);

// Build a map of members by user id for quick lookup (some modules return indexed arrays)
$membersById = [];
foreach ($members as $m) {
    $membersById[intval($m['id'])] = $m;
}

// Prepare exports directory
$exportsDir = __DIR__ . '/exports';
if (!is_dir($exportsDir)) mkdir($exportsDir, 0755, true);

$timestamp = date('Ymd_His');
$filename = sprintf('report_group_%d_%s.%s', $group_id, $timestamp, $type === 'excel' ? 'xlsx' : 'pdf');
$filePath = $exportsDir . DIRECTORY_SEPARATOR . $filename;

// Build a simple tabular dataset
$rows = [];
$rows[] = ['Expense ID', 'Title', 'Amount', 'Paid By', 'Category', 'Created At'];
foreach ($expenses as $e) {
    $rows[] = [
        $e['id'],
        $e['title'] ?? $e['name'] ?? '',
        $e['amount'],
        $e['paid_by_name'] ?? $e['paid_by'],
        $e['category'] ?? '',
        $e['created_at'] ?? ''
    ];
}

// Balances sheet data
$balanceRows = [];
$balanceRows[] = ['User ID', 'Name', 'Balance'];
foreach ($balances as $uid => $bal) {
    $name = isset($membersById[$uid]) ? $membersById[$uid]['name'] : 'User ' . $uid;
    $balanceRows[] = [$uid, $name, $bal];
}

// Generate file using libraries if available — with safe fallbacks
if ($type === 'excel') {
    // Prefer PhpSpreadsheet, fallback to CSV if not available
    if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, NULL, 'A1');
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Balances');
        $sheet2->fromArray($balanceRows, NULL, 'A1');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);
    } else {
        // Fallback: create CSV which Excel can open
        $csvName = preg_replace('/\.xlsx$/', '.csv', $filePath);
        $fh = fopen($csvName, 'w');
        if ($fh) {
            foreach ($rows as $r) {
                // convert all values to string
                fputcsv($fh, array_map(function ($v) {
                    return is_null($v) ? '' : $v;
                }, $r));
            }
            // blank line then balances
            fputcsv($fh, []);
            fputcsv($fh, ['Balances']);
            foreach ($balanceRows as $r) fputcsv($fh, $r);
            fclose($fh);
            $filePath = $csvName;
            $type = 'excel';
        } else {
            $err = 'Failed to write CSV fallback. Check exports/ permissions.';
            header('Location: view_group.php?id=' . $group_id . '&report_err=' . urlencode($err));
            exit;
        }
    }
} else {
    // PDF generation. Prefer TCPDF; if missing, create HTML fallback file and inform user.
    if (class_exists('TCPDF')) {
        $pdf = new TCPDF();
        $pdf->AddPage();
        $html = '<h2>Group: ' . htmlspecialchars($group['name']) . '</h2>';
        $html .= '<h3>Expenses</h3><table border="1" cellpadding="4">';
        foreach ($rows as $ridx => $r) {
            $html .= '<tr>';
            foreach ($r as $cell) $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<h3>Balances</h3><table border="1" cellpadding="4">';
        foreach ($balanceRows as $r) {
            $html .= '<tr>';
            foreach ($r as $cell) $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html);
        $pdf->Output($filePath, 'F');
    } else {
        // Fallback: write an HTML file that can be opened or converted to PDF manually
        $htmlPath = preg_replace('/\.pdf$/', '.html', $filePath);
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Report</title></head><body>';
        $html .= '<h2>Group: ' . htmlspecialchars($group['name']) . '</h2>';
        $html .= '<h3>Expenses</h3><table border="1" cellpadding="4">';
        foreach ($rows as $r) {
            $html .= '<tr>';
            foreach ($r as $cell) $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<h3>Balances</h3><table border="1" cellpadding="4">';
        foreach ($balanceRows as $r) {
            $html .= '<tr>';
            foreach ($r as $cell) $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table></body></html>';
        if (file_put_contents($htmlPath, $html) !== false) {
            $filePath = $htmlPath;
            // note: we keep type as 'pdf' so UI still treats it as report, but file ext is .html
        } else {
            $err = 'Failed to write HTML fallback for PDF. Check exports/ permissions.';
            header('Location: view_group.php?id=' . $group_id . '&report_err=' . urlencode($err));
            exit;
        }
    }
}

// Save report metadata and create token
$reportModel = new Report();
$fileRel = 'exports/' . basename($filePath);
$report = $reportModel->createReport($group_id, $user_id, $type, $fileRel);
if (!$report) {
    // If metadata save failed, attempt to remove file and notify user
    @unlink($filePath);
    $err = 'Failed to save report metadata.';
    header('Location: view_group.php?id=' . $group_id . '&report_err=' . urlencode($err));
    exit;
}

// Stream file as download to browser (auto-download). Use correct headers based on extension.
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'pdf') $mime = 'application/pdf';
elseif ($ext === 'xlsx') $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
elseif ($ext === 'csv') $mime = 'text/csv';
elseif ($ext === 'html' || $ext === 'htm') $mime = 'text/html';

if (file_exists($filePath)) {
    // Headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    // flush output buffers then read file
    @ob_end_clean();
    readfile($filePath);
    exit;
} else {
    header('Location: view_group.php?id=' . $group_id . '&report_err=' . urlencode('Generated file missing'));
    exit;
}
