<?php
// ===============================================================
// SellExa – Auth helpers (RBAC)
// ===============================================================
declare(strict_types=1);

function require_login(string $BASE): array {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $u = $_SESSION['user'] ?? null;
  if (!$u) {
    header('Location: ' . $BASE . '/login.php');
    exit;
  }
  return $u;
}

function require_admin(string $BASE): array {
  $u = require_login($BASE);
  if (($u['role'] ?? 'guest') !== 'admin') {
    header('Location: ' . $BASE . '/index.php');
    exit;
  }
  return $u;
}
