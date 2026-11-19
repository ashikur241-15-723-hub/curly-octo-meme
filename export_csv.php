<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];
$filters = build_transaction_filters($_GET);
$result = filter_transactions($userId, $filters, false);

$filename = 'expensetrack-transactions-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// UTF-8 BOM for Excel
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, [
    'Transaction ID', 'Date', 'Type', 'Category', 'Description',
    'Payment Method', 'Amount (BDT)', 'Note',
]);

foreach ($result['rows'] as $tx) {
    fputcsv($out, [
        $tx['transaction_id'],
        $tx['transaction_date'],
        $tx['transaction_type'],
        $tx['category_name'],
        $tx['description'],
        $tx['payment_method'],
        number_format((float) $tx['amount'], 2, '.', ''),
        $tx['note'] ?? '',
    ]);
}

fputcsv($out, []);
fputcsv($out, ['Total Income', number_format($result['income_total'], 2, '.', '')]);
fputcsv($out, ['Total Expense', number_format($result['expense_total'], 2, '.', '')]);
fputcsv($out, ['Net Balance', number_format($result['income_total'] - $result['expense_total'], 2, '.', '')]);
fclose($out);
exit;
