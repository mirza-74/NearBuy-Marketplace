<?php
// ===============================================================
// NearBuy – Logout
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

if (!isset($BASE) || !$BASE) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('~^(.*/public)~', $scriptDir, $m)) {
        $BASE = $m[1];
    } else {
        $BASE = '/NearBuy-marketplace/public';
    }
}
$BASE = '/' . ltrim($BASE, '/');
$BASE = rtrim($BASE, '/');

$_SESSION['user'] = null;
unset($_SESSION['user']);

if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
}

if (!empty($_COOKIE['sellexa_remember'])) {
    setcookie('sellexa_remember', '', time() - 3600, '/', '', false, true);
}

$_SESSION['flash'] = 'Kamu sudah logout. Sampai jumpa lagi.';

header('Location: ' . $BASE . '/index.php');
exit;
