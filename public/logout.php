<?php
// ===============================================================
// SellExa – Logout
// ===============================================================
declare(strict_types=1);

// Sesuaikan dengan base path public kamu
$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';

// 1) Hapus data user di sesi (jangan destroy seluruh sesi agar flash bisa tampil)
$_SESSION['user'] = null;
unset($_SESSION['user']);

// (opsional) bersihkan data lain yang ingin kamu reset saat logout
// unset($_SESSION['cart_id']);
// unset($_SESSION['csrf']); // kalau kamu mau regenerasi token setelahnya

// 2) Regenerasi ID sesi untuk mencegah session fixation
if (function_exists('session_regenerate_id')) {
  session_regenerate_id(true);
}

// 3) Hapus cookie remember-me jika ada (opsional; aktifkan jika kamu pakai fitur ini)
if (!empty($_COOKIE['sellexa_remember'])) {
  setcookie('sellexa_remember', '', time() - 3600, '/', '', false, true);
}

// 4) Set flash message (akan dibaca di header atau halaman berikutnya)
$_SESSION['flash'] = 'Kamu sudah logout. Sampai jumpa lagi!';

// 5) Redirect ke beranda
header('Location: ' . $BASE . '/index.php');
exit;
