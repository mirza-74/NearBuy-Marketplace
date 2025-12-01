<?php
// ===============================================================
// SellExa – Session bootstrap (+ optional CSRF helpers)
// ===============================================================
declare(strict_types=1);

// ----- Session cookie yang lebih aman -----
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // kalau full HTTPS, ganti ke 'None' + Secure

// (Opsional) set path & lifetime cookie
$cookieLifetime = 60 * 60 * 24 * 7; // 7 hari
session_set_cookie_params([
  'lifetime' => $cookieLifetime,
  'path'     => '/',
  'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'httponly' => true,
  'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// (Opsional) regenerasi ID per session baru
if (empty($_SESSION['__init'])) {
  $_SESSION['__init'] = true;
  session_regenerate_id(true);
}

// ===== CSRF helper (opsional, tapi sangat dianjurkan) =====
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
  }
}

if (!function_exists('csrf_verify')) {
  function csrf_verify(?string $token): bool {
    $sess = $_SESSION['csrf'] ?? '';
    return is_string($token) && is_string($sess) && hash_equals($sess, $token);
  }
}

// helper buat nge-print <input hidden> di form
if (!function_exists('csrf_input')) {
  function csrf_input(): void {
    $token = csrf_token();
    echo '<input type="hidden" name="csrf_token" value="'.
         htmlspecialchars($token, ENT_QUOTES, 'UTF-8').
         '">';
  }
}
