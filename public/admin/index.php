<?php
declare(strict_types=1);

// ===============================================================
// NearBuy – Admin Dashboard
// ===============================================================

// BASE: otomatis, tapi buang "/admin" di ujung
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = preg_replace('~/admin$~', '', rtrim($scriptDir, '/'));

// includes
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// cek login admin
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
  header("Location: {$BASE}/login.php");
  exit;
}

// helper escape
if (!function_exists('e')) {
  function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

// ===============================================================
// PROSES POST: SETUJUI / TOLAK TOKO, PEMBAYARAN, PRODUK
// ===============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id     = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0; // dipakai generic untuk shop / product

  $allowedActions = [
    'approve_shop',
    'reject_shop',
    'approve_payment',
    'reject_payment',
    'approve_product',
    'reject_product',
  ];

  if ($id <= 0 || !in_array($action, $allowedActions, true)) {
    $_SESSION['flash_admin'] = 'Permintaan tidak valid (action="' . $action . '", id=' . $id . ').';
    header("Location: {$BASE}/admin/index.php");
    exit;
  }

  // ====== SETUJUI TOKO (Aktivasi) ======
  if ($action === 'approve_shop') {
    try {
      $pdo->beginTransaction();

      // 1) Toko diaktifkan (is_active = 1)
      $stmt = $pdo->prepare("UPDATE shops SET is_active = 1 WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);

      // 2) Role user pemilik toko jadi 'seller'
      $stmtU = $pdo->prepare("
        UPDATE users u
        JOIN shops s ON s.user_id = u.id
        SET u.role = 'seller'
        WHERE s.id = ?
        LIMIT 1
      ");
      $stmtU->execute([$id]);

      $pdo->commit();
      $_SESSION['flash_admin'] = "Toko ID {$id} berhasil disetujui. Seller sekarang ber-role 'seller'.";

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $_SESSION['flash_admin'] = "Gagal menyetujui toko: " . $e->getMessage();
    }

    header("Location: {$BASE}/admin/index.php");
    exit;
  }

  // ====== TOLAK TOKO ======
  if ($action === 'reject_shop') {
    try {
      // misal pakai is_active = 2 sebagai status "ditolak"
      $stmt = $pdo->prepare("UPDATE shops SET is_active = 2 WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);

      $_SESSION['flash_admin'] = "Toko ID {$id} telah ditandai sebagai ditolak.";
    } catch (Throwable $e) {
      $_SESSION['flash_admin'] = "Gagal menolak toko: " . $e->getMessage();
    }

    header("Location: {$BASE}/admin/index.php");
    exit;
  }

  // ====== SETUJUI PEMBAYARAN LANGGANAN (ACC) ======
  if ($action === 'approve_payment') {
    try {
      // 1. Ambil package_code yang tersimpan saat seller upload bukti
      $stmt = $pdo->prepare("SELECT package_code FROM shops WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);
      $shopData = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$shopData || empty($shopData['package_code'])) {
        $_SESSION['flash_admin'] = "Gagal menyetujui pembayaran: Data paket untuk Toko ID {$id} tidak ditemukan.";
        header("Location: {$BASE}/admin/index.php");
        exit;
      }

      $packageCode     = $shopData['package_code'];
      $newProductLimit = 0;

      // 2. Tentukan batas produk berdasarkan nama paket
      if (strcasecmp($packageCode, 'Paket Starter') === 0 || strcasecmp($packageCode, 'Paket A') === 0) {
        $newProductLimit = 15;     // Starter
      } elseif (strcasecmp($packageCode, 'Paket Premium') === 0 || strcasecmp($packageCode, 'Paket B') === 0) {
        $newProductLimit = 99999;  // Premium (praktis tanpa batas)
      }
      // Tambah mapping paket lain di sini jika ada

      // 3. Update status langganan dan product_limit
      $stmt = $pdo->prepare("
        UPDATE shops SET 
          subscription_status = 'active', 
          product_limit       = ?, 
          updated_at          = NOW() 
        WHERE id = ? 
        LIMIT 1
      ");
      $stmt->execute([$newProductLimit, $id]);

      $_SESSION['flash_admin'] = "Pembayaran Toko ID {$id} berhasil disetujui. Langganan {$packageCode} diaktifkan dengan batas {$newProductLimit} produk.";
    } catch (Throwable $e) {
      $_SESSION['flash_admin'] = "Gagal menyetujui pembayaran: " . $e->getMessage();
    }

    header("Location: {$BASE}/admin/index.php");
    exit;
  }

  // ====== TOLAK PEMBAYARAN LANGGANAN ======
  if ($action === 'reject_payment') {
    try {
      // Ubah status langganan kembali menjadi 'free', dan reset beberapa kolom
      $stmt = $pdo->prepare("
        UPDATE shops SET 
          subscription_status = 'free', 
          product_limit       = 0,
          last_payment_proof  = NULL, 
          package_code        = NULL,    
          updated_at          = NOW() 
        WHERE id = ? 
        LIMIT 1
      ");
      $stmt->execute([$id]);

      $_SESSION['flash_admin'] = "Pembayaran Toko ID {$id} ditolak. Status dikembalikan ke 'free'.";
    } catch (Throwable $e) {
      $_SESSION['flash_admin'] = "Gagal menolak pembayaran: " . $e->getMessage();
    }

    header("Location: {$BASE}/admin/index.php");
    exit;
  }

  // ====== SETUJUI PRODUK (AKTIFKAN) ======
  if ($action === 'approve_product') {
    try {
      $stmt = $pdo->prepare("
        UPDATE products
        SET is_active = 1, updated_at = NOW()
        WHERE id = ?
        LIMIT 1
      ");
      $stmt->execute([$id]);

      $_SESSION['flash_admin'] = "Produk ID {$id} berhasil disetujui dan kini aktif.";
    } catch (Throwable $e) {
      $_SESSION['flash_admin'] = "Gagal menyetujui produk: " . $e->getMessage();
    }

    header("Location: {$BASE}/admin/index.php");
    exit;
  }

  // ====== TOLAK / HAPUS PRODUK ======
  if ($action === 'reject_product') {
    try {
      // Jika ingin benar-benar menghapus produk (dan relasinya)
      $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? LIMIT 1");
      $stmt->execute([$id]);

      $_SESSION['flash_admin'] = "Produk ID {$id} telah dihapus / ditolak.";
    } catch (Throwable $e) {
      $_SESSION['flash_admin'] = "Gagal menolak produk: " . $e->getMessage();
    }

    header("Location: {$BASE}/admin/index.php");
    exit;
  }
}

// ===============================================================
// FLASH MESSAGE
// ===============================================================
$flash = $_SESSION['flash_admin'] ?? '';
unset($_SESSION['flash_admin']);

// ===============================================================
// STATISTIK
// ===============================================================
$totalProducts = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn() ?? 0);
$totalShops    = (int)($pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn() ?? 0);
$pendingShops  = (int)($pdo->query("SELECT COUNT(*) FROM shops WHERE is_active = 0")->fetchColumn() ?? 0);

// ===============================================================
// TOKO AKTIF (is_active = 1)
// ===============================================================
$activeShops = [];
try {
  $stmt = $pdo->prepare("
    SELECT 
     s.id, s.name, s.address, s.is_active, s.created_at, u.full_name, u.email,
     COUNT(p.id) AS total_products, SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END) AS active_products
    FROM shops s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN products p ON p.shop_id = s.id
    WHERE s.is_active = 1
    GROUP BY s.id, s.name, s.address, s.is_active, s.created_at, u.full_name, u.email
    ORDER BY s.created_at DESC
    LIMIT 50
  ");
  $stmt->execute();
  $activeShops = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $activeShops = [];
}

// ===============================================================
// PERMINTAAN BUKA TOKO (is_active = 0)
// ===============================================================
$requests = [];
try {
  $stmt = $pdo->prepare("
    SELECT 
     s.id, s.user_id, s.name, s.address, s.latitude, s.longitude, s.created_at, u.email, u.full_name
    FROM shops s
    JOIN users u ON u.id = s.user_id
    WHERE s.is_active = 0
    ORDER BY s.created_at ASC
    LIMIT 50
  ");
  $stmt->execute();
  $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $requests = [];
}

// ===============================================================
// VERIFIKASI PEMBAYARAN LANGGANAN (subscription_status = 'pending_payment')
// ===============================================================
$pendingPayments = [];
try {
  $stmt = $pdo->prepare("
    SELECT 
     s.id,
     s.name,
     s.subscription_status,
     s.last_payment_proof,   
     s.package_code,      
     s.updated_at,
     u.full_name,
     u.email
    FROM shops s
    JOIN users u ON u.id = s.user_id
    WHERE s.subscription_status = 'pending_payment'
    ORDER BY s.updated_at ASC
    LIMIT 50
  ");
  $stmt->execute();
  $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $pendingPayments = [];
}

// ===============================================================
// VERIFIKASI PRODUK (p.is_active = 0 & toko sudah aktif)
// ===============================================================
$pendingProducts = [];
try {
  $stmt = $pdo->prepare("
    SELECT
      p.id,
      p.title,
      p.price,
      p.stock,
      p.main_image,
      p.created_at,
      s.name      AS shop_name,
      u.full_name AS owner_name,
      u.email     AS owner_email
    FROM products p
    JOIN shops s ON s.id = p.shop_id
    JOIN users u ON u.id = s.user_id
    WHERE p.is_active = 0
      AND s.is_active = 1
    ORDER BY p.created_at ASC
    LIMIT 50
  ");
  $stmt->execute();
  $pendingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $pendingProducts = [];
}

// ===============================================================
// CATATAN / TRANSAKSI TERBARU
// ===============================================================
$notes = [];
try {
  $stmt = $pdo->prepare("
    SELECT id, user_id, grand_total, status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 12
  ");
  $stmt->execute();
  $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $notes = [];
}

// ===============================================================
// HEADER (pakai header global) + CSS khusus admin
// ===============================================================
$EXTRA_CSS = $EXTRA_CSS ?? [];
$EXTRA_CSS[] = 'admin/style-admin.css';

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <h1 class="admin-title">Halo Admin 👋</h1>
  <p class="admin-subtitle">
    Selamat datang di dashboard NearBuy. Di sini kamu bisa melihat toko aktif, permintaan buka toko,
    verifikasi pembayaran paket, dan verifikasi produk baru.
  </p>

  <?php if ($flash): ?>
   <div class="admin-flash">
    <?= e($flash) ?>
   </div>
  <?php endif; ?>

  <div class="admin-stats">
   <div class="admin-stat-card">
    <div class="stat-number"><?= (int)$totalProducts ?></div>
    <div class="stat-label">Produk aktif</div>
   </div>
   <div class="admin-stat-card">
    <div class="stat-number"><?= (int)$totalShops ?></div>
    <div class="stat-label">Total toko terdaftar</div>
   </div>
   <div class="admin-stat-card">
    <div class="stat-number"><?= (int)$pendingShops ?></div>
    <div class="stat-label">Permintaan buka toko</div>
   </div>
  </div>

  <!-- ======================================= -->
  <!-- VERIFIKASI PEMBAYARAN LANGGANAN -->
  <!-- ======================================= -->
  <section class="admin-section">
    <h2 class="section-title">Verifikasi Pembayaran Langganan</h2>
    <p class="section-subtitle">
      Periksa bukti transfer dan setujui untuk mengaktifkan paket langganan seller.
    </p>
    <?php if (empty($pendingPayments)): ?>
      <div class="section-empty">Tidak ada pembayaran langganan yang menunggu verifikasi.</div>
    <?php else: ?>
      <div class="payment-request-list">
      <?php foreach ($pendingPayments as $pay): ?>
        <div class="shop-request-card payment-card">
          <div class="shop-request-main">
            <div class="shop-name payment-info-title">
              Pembayaran Langganan: 
              <span style="color: #10b981; font-weight: 700;"><?= e($pay['package_code'] ?? 'TIDAK DIKETAHUI') ?></span>
            </div>
            
            <div class="shop-owner">
              Toko: <?= e($pay['name']) ?> (ID: <?= (int)$pay['id'] ?>)
            </div>
            <div class="shop-owner">
              Pemilik: <?= e($pay['full_name'] ?? '-') ?> · Email: <?= e($pay['email'] ?? '-') ?>
            </div>
            <div class="shop-coord">
              Diunggah pada: <?= e($pay['updated_at']) ?>
            </div>
            
            <?php if (!empty($pay['last_payment_proof'])): ?>
              <div style="margin-top: 10px;">
                <a href="<?= e($pay['last_payment_proof']) ?>" target="_blank" class="btn btn-view-proof">
                  Lihat Bukti Pembayaran
                </a>
              </div>
            <?php else: ?>
              <div style="margin-top: 10px; color: red;">
                [ERROR: Bukti pembayaran tidak tercatat di DB]
              </div>
            <?php endif; ?>
          </div>

          <div class="shop-request-actions">
            <form method="post" class="inline-form">
              <input type="hidden" name="shop_id" value="<?= (int)$pay['id'] ?>">
              <input type="hidden" name="action" value="approve_payment">
              <button type="submit" class="btn btn-approve">
                ACC Pembayaran
              </button>
            </form>

            <form method="post" class="inline-form">
              <input type="hidden" name="shop_id" value="<?= (int)$pay['id'] ?>">
              <input type="hidden" name="action" value="reject_payment">
              <button type="submit" class="btn btn-reject">
                Tolak Pembayaran
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ======================================= -->
  <!-- VERIFIKASI PRODUK BARU -->
  <!-- ======================================= -->
  <section class="admin-section">
    <h2 class="section-title">Verifikasi Produk Baru</h2>
    <p class="section-subtitle">
      Produk yang baru ditambahkan oleh seller dan masih berstatus pending (belum aktif).
    </p>
    <?php if (empty($pendingProducts)): ?>
      <div class="section-empty">Tidak ada produk yang menunggu verifikasi.</div>
    <?php else: ?>
      <div class="payment-request-list">
        <?php foreach ($pendingProducts as $p): ?>
          <div class="shop-request-card payment-card">
            <div class="shop-request-main">
              <div class="shop-name payment-info-title">
                Produk: <span style="font-weight:700;"><?= e($p['title']) ?></span>
              </div>
              <div class="shop-owner">
                Toko: <?= e($p['shop_name']) ?> · Harga: Rp<?= number_format((float)$p['price'], 0, ',', '.') ?> · Stok: <?= (int)$p['stock'] ?>
              </div>
              <div class="shop-owner">
                Pemilik: <?= e($p['owner_name'] ?? '-') ?> · Email: <?= e($p['owner_email'] ?? '-') ?>
              </div>
              <div class="shop-coord">
                Ditambahkan pada: <?= e($p['created_at']) ?>
              </div>

              <?php
                if (!empty($p['main_image'])) {
                  if (preg_match('~^https?://~i', (string)$p['main_image'])) {
                    $imgSrc = $p['main_image'];
                  } else {
                    $imgSrc = $BASE . '/uploads/' . ltrim($p['main_image'], '/');
                  }
                } else {
                  $imgSrc = 'https://via.placeholder.com/80?text=No+Img';
                }
              ?>
              <div style="margin-top:10px;">
                <img src="<?= e($imgSrc) ?>" alt="Produk" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              </div>
            </div>

            <div class="shop-request-actions">
              <form method="post" class="inline-form">
                <input type="hidden" name="shop_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="action" value="approve_product">
                <button type="submit" class="btn btn-approve">
                  ACC Produk
                </button>
              </form>

              <form method="post" class="inline-form" onsubmit="return confirm('Yakin ingin menolak / menghapus produk ini?')">
                <input type="hidden" name="shop_id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="action" value="reject_product">
                <button type="submit" class="btn btn-reject">
                  Tolak / Hapus
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ======================================= -->
  <!-- PERMINTAAN BUKA TOKO -->
  <!-- ======================================= -->
  <section class="admin-section">
    <h2 class="section-title">Permintaan Buka Toko</h2>
    <p class="section-subtitle">
      Setelah disetujui, toko akan masuk ke daftar pemilik toko aktif. Seller kemudian akan diminta memilih paket di sisi mereka.
    </p>
    <?php if (empty($requests)): ?>
      <div class="section-empty">Tidak ada permintaan toko baru.</div>
    <?php else: ?>
      <div class="shop-request-list">
      <?php foreach ($requests as $req): ?>
        <div class="shop-request-card">
          <div class="shop-request-main">
            <div class="shop-name">
              <?= e($req['name']) ?>
              <span class="shop-id">(ID: <?= (int)$req['id'] ?>)</span>
            </div>
            <div class="shop-owner">
              Pemilik: <?= e($req['full_name'] ?? '-') ?> · Email: <?= e($req['email'] ?? '-') ?>
            </div>
            <div class="shop-address">
              <?= nl2br(e($req['address'] ?: 'Alamat belum diisi')) ?>
            </div>
            <div class="shop-coord">
              Koordinat: <?= e((string)$req['latitude'] ?? '-') ?>, <?= e((string)$req['longitude'] ?? '-') ?> · Request: <?= e($req['created_at']) ?>
            </div>

            <div style="margin-top:6px;font-size:12px;color:#0f766e;">
              Debug shop_id: <?= (int)$req['id'] ?>
            </div>
          </div>

          <div class="shop-request-actions">
            <form method="post" class="inline-form">
              <input type="hidden" name="shop_id" value="<?= (int)$req['id'] ?>">
              <input type="hidden" name="action" value="approve_shop">
              <button type="submit" class="btn btn-approve">
                Setujui
              </button>
            </form>

            <form method="post" class="inline-form">
              <input type="hidden" name="shop_id" value="<?= (int)$req['id'] ?>">
              <input type="hidden" name="action" value="reject_shop">
              <button type="submit" class="btn btn-reject">
                Tolak
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  
  <!-- ======================================= -->
  <!-- CATATAN / TRANSAKSI TERBARU -->
  <!-- ======================================= -->
  <section class="admin-section">
   <h2 class="section-title">Catatan / Transaksi Terbaru</h2>
   <?php if (empty($notes)): ?>
    <div class="section-empty">Belum ada catatan transaksi.</div>
   <?php else: ?>
    <div class="note-list">
     <?php foreach ($notes as $n): ?>
      <div class="note-card">
       <div class="note-main">
        <div class="note-title">ORDER #<?= (int)$n['id'] ?></div>
        <div class="note-sub">
         User ID: <?= (int)$n['user_id'] ?> · Status: <?= e($n['status'] ?? '-') ?>
        </div>
       </div>
       <div class="note-amount">
        <div class="note-money">Rp<?= number_format((float)($n['grand_total'] ?? 0),0,',','.') ?></div>
        <div class="note-date"><?= e($n['created_at'] ?? '') ?></div>
       </div>
      </div>
     <?php endforeach; ?>
    </div>
   <?php endif; ?>
  </section>

</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
