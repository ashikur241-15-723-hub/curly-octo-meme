<?php
declare(strict_types=1);

function user_categories(int $userId, ?string $type = null): array
{
    $sql = 'SELECT * FROM categories WHERE user_id = ?';
    $params = [$userId];
    if ($type === 'income' || $type === 'expense') {
        $sql .= ' AND category_type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY category_type, category_name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_category_for_user(int $userId, int $categoryId): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE category_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$categoryId, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function sum_transactions(int $userId, string $type, ?string $from = null, ?string $to = null): float
{
    $sql = 'SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE user_id = ? AND transaction_type = ?';
    $params = [$userId, $type];
    if ($from) {
        $sql .= ' AND transaction_date >= ?';
        $params[] = $from;
    }
    if ($to) {
        $sql .= ' AND transaction_date <= ?';
        $params[] = $to;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (float) $stmt->fetchColumn();
}

function dashboard_stats(int $userId): array
{
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');

    $totalIncome = sum_transactions($userId, 'income');
    $totalExpense = sum_transactions($userId, 'expense');
    $todayExpense = sum_transactions($userId, 'expense', $today, $today);
    $monthExpense = sum_transactions($userId, 'expense', $monthStart, $monthEnd);
    $monthIncome = sum_transactions($userId, 'income', $monthStart, $monthEnd);

    return [
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'balance' => $totalIncome - $totalExpense,
        'today_expense' => $todayExpense,
        'month_expense' => $monthExpense,
        'month_income' => $monthIncome,
    ];
}

function recent_transactions(int $userId, int $limit = 8): array
{
    $stmt = db()->prepare(
        'SELECT t.*, c.category_name
         FROM transactions t
         INNER JOIN categories c ON c.category_id = t.category_id
         WHERE t.user_id = ?
         ORDER BY t.transaction_date DESC, t.transaction_id DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function monthly_income_expense(int $userId, int $months = 6): array
{
    $labels = [];
    $income = [];
    $expense = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $ts = strtotime("-{$i} months");
        $start = date('Y-m-01', $ts);
        $end = date('Y-m-t', $ts);
        $labels[] = date('M Y', $ts);
        $income[] = sum_transactions($userId, 'income', $start, $end);
        $expense[] = sum_transactions($userId, 'expense', $start, $end);
    }
    return compact('labels', 'income', 'expense');
}

function category_expense_breakdown(int $userId, ?string $from = null, ?string $to = null): array
{
    $sql = 'SELECT c.category_name, COALESCE(SUM(t.amount), 0) AS total
            FROM transactions t
            INNER JOIN categories c ON c.category_id = t.category_id
            WHERE t.user_id = ? AND t.transaction_type = \'expense\'';
    $params = [$userId];
    if ($from) {
        $sql .= ' AND t.transaction_date >= ?';
        $params[] = $from;
    }
    if ($to) {
        $sql .= ' AND t.transaction_date <= ?';
        $params[] = $to;
    }
    $sql .= ' GROUP BY c.category_id, c.category_name HAVING total > 0 ORDER BY total DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function daily_expense_overview(int $userId, int $days = 14): array
{
    $labels = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = date('d M', strtotime($day));
        $values[] = sum_transactions($userId, 'expense', $day, $day);
    }
    return compact('labels', 'values');
}

function build_transaction_filters(array $input): array
{
    return [
        'q' => trim((string) ($input['q'] ?? '')),
        'type' => in_array(($input['type'] ?? ''), ['income', 'expense'], true) ? $input['type'] : '',
        'category_id' => (int) ($input['category_id'] ?? 0),
        'payment_method' => trim((string) ($input['payment_method'] ?? '')),
        'date_from' => validate_date($input['date_from'] ?? null) ? $input['date_from'] : '',
        'date_to' => validate_date($input['date_to'] ?? null) ? $input['date_to'] : '',
        'sort' => in_array(($input['sort'] ?? ''), ['date_asc', 'date_desc', 'amount_asc', 'amount_desc'], true)
            ? $input['sort'] : 'date_desc',
        'page' => max(1, (int) ($input['page'] ?? 1)),
        'per_page' => min(100, max(5, (int) ($input['per_page'] ?? 15))),
    ];
}

function filter_transactions(int $userId, array $filters, bool $paginate = true): array
{
    $where = ['t.user_id = ?'];
    $params = [$userId];

    if ($filters['q'] !== '') {
        $where[] = '(t.description LIKE ? OR t.note LIKE ? OR c.category_name LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($filters['type'] !== '') {
        $where[] = 't.transaction_type = ?';
        $params[] = $filters['type'];
    }
    if ($filters['category_id'] > 0) {
        $where[] = 't.category_id = ?';
        $params[] = $filters['category_id'];
    }
    if ($filters['payment_method'] !== '' && in_array($filters['payment_method'], payment_methods(), true)) {
        $where[] = 't.payment_method = ?';
        $params[] = $filters['payment_method'];
    }
    if ($filters['date_from'] !== '') {
        $where[] = 't.transaction_date >= ?';
        $params[] = $filters['date_from'];
    }
    if ($filters['date_to'] !== '') {
        $where[] = 't.transaction_date <= ?';
        $params[] = $filters['date_to'];
    }

    $order = match ($filters['sort']) {
        'date_asc' => 't.transaction_date ASC, t.transaction_id ASC',
        'amount_asc' => 't.amount ASC, t.transaction_id DESC',
        'amount_desc' => 't.amount DESC, t.transaction_id DESC',
        default => 't.transaction_date DESC, t.transaction_id DESC',
    };

    $whereSql = implode(' AND ', $where);
    $countStmt = db()->prepare(
        "SELECT COUNT(*) FROM transactions t
         INNER JOIN categories c ON c.category_id = t.category_id
         WHERE {$whereSql}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sumStmt = db()->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN t.transaction_type = 'income' THEN t.amount ELSE 0 END), 0) AS income_total,
            COALESCE(SUM(CASE WHEN t.transaction_type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense_total
         FROM transactions t
         INNER JOIN categories c ON c.category_id = t.category_id
         WHERE {$whereSql}"
    );
    $sumStmt->execute($params);
    $sums = $sumStmt->fetch() ?: ['income_total' => 0, 'expense_total' => 0];

    $sql = "SELECT t.*, c.category_name
            FROM transactions t
            INNER JOIN categories c ON c.category_id = t.category_id
            WHERE {$whereSql}
            ORDER BY {$order}";

    if ($paginate) {
        $offset = ($filters['page'] - 1) * $filters['per_page'];
        $sql .= ' LIMIT ' . (int) $filters['per_page'] . ' OFFSET ' . (int) $offset;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return [
        'rows' => $rows,
        'total' => $total,
        'income_total' => (float) $sums['income_total'],
        'expense_total' => (float) $sums['expense_total'],
        'pages' => $paginate ? max(1, (int) ceil($total / $filters['per_page'])) : 1,
    ];
}

function get_transaction_for_user(int $userId, int $transactionId): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, c.category_name, c.category_type
         FROM transactions t
         INNER JOIN categories c ON c.category_id = t.category_id
         WHERE t.transaction_id = ? AND t.user_id = ? LIMIT 1'
    );
    $stmt->execute([$transactionId, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function budget_status(int $userId, string $month): array
{
    $start = $month . '-01';
    if (!validate_date($start)) {
        $start = date('Y-m-01');
        $month = date('Y-m');
    }
    $end = date('Y-m-t', strtotime($start));

    $stmt = db()->prepare(
        'SELECT b.*, c.category_name
         FROM budgets b
         LEFT JOIN categories c ON c.category_id = b.category_id
         WHERE b.user_id = ? AND b.budget_month = ?
         ORDER BY b.category_id IS NULL DESC, c.category_name'
    );
    $stmt->execute([$userId, $month]);
    $budgets = $stmt->fetchAll();

    $result = [];
    foreach ($budgets as $b) {
        if ($b['category_id'] === null) {
            $spent = sum_transactions($userId, 'expense', $start, $end);
            $name = 'Overall Monthly';
        } else {
            $spentStmt = db()->prepare(
                'SELECT COALESCE(SUM(amount), 0) FROM transactions
                 WHERE user_id = ? AND category_id = ? AND transaction_type = \'expense\'
                   AND transaction_date BETWEEN ? AND ?'
            );
            $spentStmt->execute([$userId, $b['category_id'], $start, $end]);
            $spent = (float) $spentStmt->fetchColumn();
            $name = $b['category_name'] ?? 'Category';
        }
        $amount = (float) $b['budget_amount'];
        $remaining = $amount - $spent;
        $pct = $amount > 0 ? ($spent / $amount) * 100 : 0;
        $status = 'ok';
        if ($pct >= 100) {
            $status = 'exceeded';
        } elseif ($pct >= 80) {
            $status = 'warning';
        }
        $result[] = [
            'budget' => $b,
            'name' => $name,
            'amount' => $amount,
            'spent' => $spent,
            'remaining' => $remaining,
            'percent' => $pct,
            'status' => $status,
        ];
    }
    return $result;
}

function report_summary(int $userId, ?string $from, ?string $to): array
{
    $income = sum_transactions($userId, 'income', $from, $to);
    $expense = sum_transactions($userId, 'expense', $from, $to);
    $filters = build_transaction_filters([
        'date_from' => $from ?? '',
        'date_to' => $to ?? '',
        'per_page' => 100000,
    ]);
    $data = filter_transactions($userId, $filters, false);
    $breakdown = category_expense_breakdown($userId, $from, $to);

    return [
        'income' => $income,
        'expense' => $expense,
        'balance' => $income - $expense,
        'count' => $data['total'],
        'breakdown' => $breakdown,
        'rows' => $data['rows'],
    ];
}
