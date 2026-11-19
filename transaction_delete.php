<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/queries.php';

$user = require_login();
$userId = (int) $user['user_id'];

if (!is_post()) {
    redirect('/transactions.php');
}
verify_csrf();

$id = request_int('id');
$tx = $id > 0 ? get_transaction_for_user($userId, $id) : null;
if (!$tx) {
    flash('danger', 'Transaction not found.');
    redirect('/transactions.php');
}

$stmt = db()->prepare('DELETE FROM transactions WHERE transaction_id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
flash('success', 'Transaction deleted.');
redirect('/transactions.php');
