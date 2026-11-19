<?php
/**
 * ExpenseTrack configuration sample.
 * Copy this file to config.php and adjust for your XAMPP MySQL setup.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'expense_tracker',
        'user' => 'root',
        'pass' => '', // XAMPP default is empty
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'ExpenseTrack',
        'timezone' => 'Asia/Dhaka',
        'currency_symbol' => '৳',
        // Base URL path if auto-detect is wrong, e.g. '/ExpenseTrack'
        // Leave empty to auto-detect from the script directory.
        'base_path' => '',
    ],
    'session' => [
        'name' => 'EXPENSETRACKSESSID',
        'lifetime' => 86400, // 24 hours
    ],
];
