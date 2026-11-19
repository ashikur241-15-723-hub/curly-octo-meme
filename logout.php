<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
logout_user();
start_secure_session();
flash('success', 'You have been logged out.');
redirect('/login.php');
