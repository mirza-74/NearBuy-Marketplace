<?php
// ===============================================================
// kelola_pengguna.php - Halaman Kelola Pengguna (Mode Hapus Saja)
// ===============================================================
declare(strict_types=1);

// PASTIKAN JALUR INI BENAR:
require_once __DIR__ . '/../../includes/session.php'; 
require_once __DIR__ . '/../../includes/db.php'; 
require_once __DIR__ . '/../../includes/header.php'; 

// -------------------------------------------------------------
// CEK HAK AKSES (Hanya Admin)
// -------------------------------------------------------------
$current_user_role = $_SESSION['user']['role'] ?? 'guest';

if ($current_user_role !== 'admin') { 
    header('Location: ../index.php'); 
    exit;
}

// -------------------------------------------------------------
// LOGIKA HAPUS LANGSUNG (CRUD - DELETE)
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Pastikan admin tidak menghapus akun yang sedang digunakan
    if (isset($_SESSION['user']['id']) && $user_id === $_SESSION['user']['id']) {
        $_SESSION['error_message'] = "Error: Anda tidak bisa menghapus akun Anda sendiri.";
    } else {
        try {
            // Hapus data pengguna dari tabel users
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Pengguna berhasil dihapus!";
            } else {
                $_SESSION['error_message'] = "Error: Pengguna tidak ditemukan atau tidak dapat dihapus.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database Error: Gagal menghapus pengguna. Mungkin pengguna ini masih memiliki data terkait (seperti produk atau toko) yang mencegah penghapusan.";
        }
    }
    
    header('Location: kelola_pengguna.php'); 
    exit;
}

// -------------------------------------------------------------
// FUNGSI READ (Membaca Semua Data Pengguna)
// -------------------------------------------------------------
function getAllUsers($pdo) {
    try {
        $sql = "
            SELECT 
                u.id, 
                u.full_name, 
                u.email, 
                u.phone, 
                u.role, 
                s.name AS shop_name 
            FROM 
                users u
            LEFT JOIN 
                shops s ON u.id = s.user_id 
            ORDER BY 
                u.id DESC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return []; 
    }
}

$users = getAllUsers($pdo); 
?>

<style>
/* Styling default */
.alert-success {
    background-color: #d1e7dd;
    color: #0f5132;
    padding: 10px;
    border: 1px solid #badbcc;
    border-radius: 6px;
    margin-bottom: 20px;
}
.alert-error {
    background-color: #f8d7da;
    color: #842029;
    padding: 10px;
    border: 1px solid #f5c2c7;
    border-radius: 6px;
    margin-bottom: 20px;
}
/* Styling Tombol Hapus Baru */
.btn-delete {
    padding: 5px 10px;
    background-color: #ef4444; /* Warna merah */
    color: white !important; /* Penting untuk override style default anchor */
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.85rem;
    display: inline-block;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}
.btn-delete:hover {
    background-color: #dc2626; /* Warna merah lebih gelap saat hover */
}
</style>

<div style="max-width: 1100px; margin: 0 auto; padding: 20px;">

    <?php 
    if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); 
    endif; 
    if (isset($_SESSION['error_message'])): ?>
        <div class="alert-error"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); 
    endif;
    ?>
    
    <div class="page-header">
        <h2 style="font-size: 2rem; color: #1f2937;">Kelola Pengguna</h2>
        <p style="color: #4b5563;">Daftar semua pengguna yang terdaftar pada sistem.</p>
    </div>

    <div class="user-management-table" style="overflow-x: auto; margin-top: 20px;">
        <table border="1" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 12px; min-width: 30px;">ID</th>
                    <th style="padding: 12px; min-width: 150px;">Nama Lengkap</th>
                    <th style="padding: 12px; min-width: 200px;">Email</th>
                    <th style="padding: 12px; min-width: 100px;">Telepon</th>
                    <th style="padding: 12px; min-width: 80px;">Peran</th>
                    <th style="padding: 12px; min-width: 150px;">Nama Toko</th>
                    <th style="padding: 12px; min-width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td style="padding: 8px; text-align: center;"><?= htmlspecialchars((string)$user['id']) ?></td>
                            <td style="padding: 8px;"><?= htmlspecialchars($user['full_name']) ?></td>
                            <td style="padding: 8px;"><?= htmlspecialchars($user['email']) ?></td>
                            <td style="padding: 8px;"><?= htmlspecialchars((string)($user['phone'] ?? '-')) ?></td>
                            <td style="padding: 8px; text-align: center; font-weight: bold; color: <?= $user['role'] == 'admin' ? '#ef4444' : ($user['role'] == 'seller' ? '#10b981' : '#3b82f6') ?>;">
                                <?= htmlspecialchars(ucfirst($user['role'])) ?>
                            </td>
                            <td style="padding: 8px; text-align: center;">
                                <?= htmlspecialchars($user['shop_name'] ?? '-') ?>
                            </td>
                            <td style="padding: 8px; text-align: center; white-space: nowrap;">
                                <a href="kelola_pengguna.php?action=delete&id=<?= $user['id'] ?>" 
                                    class="btn-delete"
                                    onclick="return confirm('Yakin ingin menghapus pengguna <?= htmlspecialchars($user['full_name']) ?>?');">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 15px; text-align: center;">Belum ada data pengguna yang terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>