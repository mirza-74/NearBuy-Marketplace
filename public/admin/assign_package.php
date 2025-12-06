<?php
declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/admin$~', '', rtrim($scriptDir, '/'));

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// cek admin
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

// ambil input
$shopId = (int)($_POST['shop_id'] ?? 0);
$package = trim((string)($_POST['package'] ?? ''));
$amount = (float)($_POST['amount'] ?? 0.0);

if ($shopId <= 0 || ($package !== 'A' && $package !== 'B')) {
    header('Location: ' . $BASE . '/admin/index.php');
    exit;
}

try {
    // buat tabel jika belum ada (aman)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shop_packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT NOT NULL,
            package_code VARCHAR(8) NOT NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            status ENUM('pending','paid','canceled') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // simpan pilihan paket (status pending)
    $stmt = $pdo->prepare("INSERT INTO shop_packages (shop_id, package_code, amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$shopId, $package, $amount]);

    // redirect kembali ke admin dengan pesan
    $_SESSION['flash_admin'] = "Paket {$package} (Rp" . number_format($amount,0,',','.') . ") disimpan untuk toko ID {$shopId}. Status: pending.";
    header('Location: ' . $BASE . '/admin/index.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['flash_admin'] = "Gagal menyimpan paket: " . $e->getMessage();
    header('Location: ' . $BASE . '/admin/index.php');
    exit;
}
