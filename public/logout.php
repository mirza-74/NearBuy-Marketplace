<?php
// NearBuy – Logout
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BASE sama seperti di header
$BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// bersihkan user
$_SESSION['user'] = null;
unset($_SESSION['user']);

if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
}

$_SESSION['flash'] = 'Kamu telah logout';

// redirect ke index
header('Location: ' . $BASE . '/index.php');
exit;
