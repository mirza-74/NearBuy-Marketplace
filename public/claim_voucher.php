<?php
// ===============================================================
// SellExa – Klaim Voucher (Pembeli)
// ===============================================================
declare(strict_types=1);

$BASE = '/Marketplace_SellExa/public';

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

// pastikan user login
$user = $_SESSION['user'] ?? null;
if (!$user || !in_array(($user['role'] ?? 'guest'), ['pengguna', 'admin'], true)) {
    header('Location: ' . $BASE . '/login.php');
    exit;
}
$userId = (int)$user['id'];

// hanya boleh POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// cek CSRF
if (!function_exists('csrf_verify') || !csrf_verify($_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = 'Sesi berakhir atau token tidak valid. Silakan coba lagi.';
    header('Location: ' . $BASE . '/index.php');
    exit;
}

$voucherId = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
if ($voucherId <= 0) {
    $_SESSION['flash'] = 'Voucher tidak valid.';
    header('Location: ' . $BASE . '/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // ambil data voucher
    $stmtV = $pdo->prepare("
        SELECT *
        FROM vouchers
        WHERE id = ? AND is_active = 1
        LIMIT 1
        FOR UPDATE
    ");
    $stmtV->execute([$voucherId]);
    $voucher = $stmtV->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        throw new RuntimeException('Voucher tidak ditemukan atau sudah nonaktif.');
    }

    // cek apakah sudah pernah diklaim user ini
    $stmtCheck = $pdo->prepare("
        SELECT id
        FROM voucher_claims
        WHERE user_id = ? AND voucher_id = ?
        LIMIT 1
    ");
    $stmtCheck->execute([$userId, $voucherId]);
    if ($stmtCheck->fetch()) {
        throw new RuntimeException('Kamu sudah pernah klaim voucher ini.');
    }

    // ambil poin user (lock row)
    $stmtU = $pdo->prepare("
        SELECT id, points
        FROM users
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmtU->execute([$userId]);
    $userRow = $stmtU->fetch(PDO::FETCH_ASSOC);
    if (!$userRow) {
        throw new RuntimeException('User tidak ditemukan.');
    }

    $currentPoints = (int)$userRow['points'];
    $minPoints     = (int)$voucher['min_points'];

    if ($currentPoints < $minPoints) {
        throw new RuntimeException('Poin kamu belum cukup untuk klaim voucher ini.');
    }

    // kurangi poin user
    $newPoints = $currentPoints - $minPoints;
    $stmtUpd = $pdo->prepare("UPDATE users SET points = ? WHERE id = ?");
    $stmtUpd->execute([$newPoints, $userId]);

    // catat klaim
    $stmtIns = $pdo->prepare("
        INSERT INTO voucher_claims (user_id, voucher_id, claimed_at)
        VALUES (?, ?, NOW())
    ");
    $stmtIns->execute([$userId, $voucherId]);

    $pdo->commit();

    $_SESSION['flash'] = 'Voucher ' . htmlspecialchars($voucher['code']) . ' berhasil diklaim! '
        . 'Gunakan saat checkout di kolom kode voucher.';
    header('Location: ' . $BASE . '/index.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // pesan error untuk user
    $_SESSION['flash'] = 'Gagal klaim voucher: ' . $e->getMessage();
    header('Location: ' . $BASE . '/index.php');
    exit;
}
