<?php
// ===============================================================
// NearBuy – Logout FIXED BASE
// ===============================================================
declare(strict_types=1);

// Pastikan session berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================
// FIX: Set BASE ke lokasi project kamu SECARA MANUAL
// ================================================
// GANTI sesuai folder di htdocs kamu
$BASE = '/NearBuy-marketplace/public';

// ================================================
// Hapus user dari session
// ================================================
$_SESSION['user'] = null;
unset($_SESSION['user']);

// Regenerasi session_id untuk keamanan
if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
}

// Flash message (opsional)
$_SESSION['flash'] = 'Kamu telah logout';

// Redirect ke halaman utama
header('Location: ' . $BASE . '/index.php');
exit;
