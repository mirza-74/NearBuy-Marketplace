<?php
declare(strict_types=1);

// Admin Dashboard: public/admin/index.php
$BASE = '/NearBuy-marketplace/public';

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

// pastikan user login & admin
$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    header('Location: ' . $BASE . '/login.php');
    exit;
}

if (!function_exists('e')) {
    function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// proses POST: approve / reject
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve_shop' && !empty($_POST['shop_id'])) {
        $shopId = (int)$_POST['shop_id'];
        try {
            $stmt = $pdo->prepare("UPDATE shops SET is_active = 1 WHERE id = ? LIMIT 1");
            $stmt->execute([$shopId]);
            $flash = "Toko (ID: {$shopId}) telah disetujui (is_active=1). Silakan pilih paket untuk toko ini.";
        } catch (Throwable $e) {
            $flash = "Gagal menyetujui toko: " . $e->getMessage();
        }
    } elseif ($action === 'reject_shop' && !empty($_POST['shop_id'])) {
        $shopId = (int)$_POST['shop_id'];
        $reason = trim((string)($_POST['reason'] ?? ''));
        try {
            // sederhana: hapus atau tandai? kita tandai dengan is_active = 2 (ditolak)
            $stmt = $pdo->prepare("UPDATE shops SET is_active = 2 WHERE id = ? LIMIT 1");
            $stmt->execute([$shopId]);
            $flash = "Toko (ID: {$shopId}) ditandai ditolak.";
        } catch (Throwable $e) {
            $flash = "Gagal menolak toko: " . $e->getMessage();
        }
    }
}

// ambil ringkasan statistik (opsional)
$totalProducts = (int)($pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn() ?? 0);
$totalShops    = (int)($pdo->query("SELECT COUNT(*) FROM shops")->fetchColumn() ?? 0);
$pendingShops  = (int)($pdo->query("SELECT COUNT(*) FROM shops WHERE is_active = 0")->fetchColumn() ?? 0);

// ambil daftar produk aktif (terbaru 30)
$activeProducts = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.price, p.compare_price, p.stock, p.main_image, s.id AS shop_id, s.name AS shop_name
        FROM products p
        JOIN shops s ON s.id = p.shop_id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT 30
    ");
    $stmt->execute();
    $activeProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $activeProducts = [];
}

// ambil daftar permintaan buka toko (is_active = 0)
$requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.user_id, s.name, s.address, s.latitude, s.longitude, s.description, s.created_at, u.email, u.full_name
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

// ambil daftar "transferan / catatan" — kita gunakan tabel transactions & orders sebagai contoh ringkasan
$notes = [];
try {
    // contoh gabungan sederhana dari tabel transactions (jika ada) dan orders terbaru
    $stmt = $pdo->prepare("
        SELECT 'order' AS type, o.id AS id, o.user_id AS user_id, o.grand_total AS amount, o.status, o.created_at
        FROM orders o
        ORDER BY o.created_at DESC
        LIMIT 12
    ");
    $stmt->execute();
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $notes = [];
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell" style="max-width:1100px;margin:22px auto;padding:0 16px;">
    <h1 style="margin-bottom:8px;">Halo Admin — Dashboard</h1>
    <p style="color:#6b7280;margin-top:0;margin-bottom:18px;">Ringkasan cepat: produk aktif, permintaan buka toko, dan catatan transaksi.</p>

    <?php if ($flash): ?>
      <div style="padding:10px;border-radius:8px;background:#f0fdf4;color:#065f46;margin-bottom:14px;">
        <?= e($flash) ?>
      </div>
    <?php endif; ?>

    <div style="display:flex;gap:14px;margin-bottom:18px;flex-wrap:wrap;">
      <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #e6e9ef;min-width:160px;">
        <div style="font-weight:700;font-size:18px;"><?= (int)$totalProducts ?></div>
        <div style="color:#6b7280;font-size:13px;">Produk aktif</div>
      </div>
      <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #e6e9ef;min-width:160px;">
        <div style="font-weight:700;font-size:18px;"><?= (int)$totalShops ?></div>
        <div style="color:#6b7280;font-size:13px;">Total toko</div>
      </div>
      <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid #e6e9ef;min-width:160px;">
        <div style="font-weight:700;font-size:18px;"><?= (int)$pendingShops ?></div>
        <div style="color:#6b7280;font-size:13px;">Permintaan buka toko</div>
      </div>
    </div>

    <!-- PRODUK AKTIF -->
    <section style="margin-bottom:22px;">
      <h2 style="margin-bottom:8px;">Produk Aktif (Terbaru)</h2>
      <?php if (empty($activeProducts)): ?>
        <div style="color:#64748b;">Belum ada produk aktif.</div>
      <?php else: ?>
        <div style="display:grid;gap:10px;">
          <?php foreach ($activeProducts as $ap): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px;border-radius:10px;border:1px solid #eef2ff;background:#fff;">
              <div style="width:84px;height:64px;flex:0 0 84px;overflow:hidden;border-radius:8px;background:#f8fafc;">
                <img src="<?= e($ap['main_image'] ? $ap['main_image'] : '/assets/noimg.png') ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
              </div>
              <div style="flex:1;">
                <div style="font-weight:700;"><?= e($ap['title']) ?></div>
                <div style="font-size:13px;color:#6b7280;">Toko: <?= e($ap['shop_name'] ?? '-') ?> · Harga: Rp<?= number_format((float)$ap['price'],0,',','.') ?></div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:13px;color:#6b7280;">Stok: <?= (int)$ap['stock'] ?></div>
                <a href="<?= e($BASE) . '/admin/product_edit.php?id=' . (int)$ap['id'] ?>" style="display:inline-block;margin-top:8px;padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;text-decoration:none;background:#f8fafc;color:#0f172a;font-size:13px;">Kelola</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- PERMINTAAN BUKA TOKO -->
    <section style="margin-bottom:22px;">
      <h2 style="margin-bottom:8px;">Permintaan Buka Toko</h2>
      <?php if (empty($requests)): ?>
        <div style="color:#64748b;">Tidak ada permintaan toko baru.</div>
      <?php else: ?>
        <div style="display:grid;gap:10px;">
          <?php foreach ($requests as $req): ?>
            <div style="padding:12px;border-radius:10px;border:1px solid #e6e9ef;background:#fff;display:flex;gap:12px;align-items:flex-start;">
              <div style="flex:1;">
                <div style="font-weight:700;"><?= e($req['name']) ?> <span style="font-weight:600;color:#6b7280;font-size:13px;">(ID: <?= (int)$req['id'] ?>)</span></div>
                <div style="font-size:13px;color:#6b7280;margin-top:6px;">
                  Pemilik: <?= e($req['full_name'] ?? '-') ?> · Email: <?= e($req['email'] ?? '-') ?>
                </div>
                <div style="margin-top:8px;color:#374151;font-size:14px;">
                  <?= nl2br(e($req['address'] ?: 'Alamat belum diisi')) ?>
                </div>
                <div style="margin-top:8px;color:#6b7280;font-size:13px;">
                  Koordinat: <?= e($req['latitude'] ?? '-') ?>, <?= e($req['longitude'] ?? '-') ?> · Request: <?= e($req['created_at']) ?>
                </div>
              </div>

              <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                <!-- tombol lihat detail (modal) -->
                <button class="btn" onclick="openShopModal(<?= (int)$req['id'] ?>)">Lihat Detail</button>

                <!-- form approve sederhana -->
                <form method="post" style="display:inline-block;margin-top:6px;">
                  <input type="hidden" name="shop_id" value="<?= (int)$req['id'] ?>">
                  <input type="hidden" name="action" value="approve_shop">
                  <button type="submit" style="padding:8px 12px;border-radius:8px;background:linear-gradient(90deg,#1d4ed8,#2563eb);color:#fff;border:none;cursor:pointer;">Setujui</button>
                </form>

                <!-- tombol reject -->
                <form method="post" style="display:inline-block;margin-top:6px;">
                  <input type="hidden" name="shop_id" value="<?= (int)$req['id'] ?>">
                  <input type="hidden" name="action" value="reject_shop">
                  <button type="submit" style="padding:8px 12px;border-radius:8px;background:#fff;border:1px solid #f87171;color:#b91c1c;cursor:pointer;">Tolak</button>
                </form>

                <!-- tombol assign paket (munculkan modal paket) -->
                <button class="btn" onclick="openPackageModal(<?= (int)$req['id'] ?>)">Pilih Paket</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- CATATAN / TRANSFERAN (ringkasan) -->
    <section style="margin-bottom:22px;">
      <h2 style="margin-bottom:8px;">Catatan / Transaksi Terbaru</h2>
      <?php if (empty($notes)): ?>
        <div style="color:#64748b;">Belum ada catatan transaksi.</div>
      <?php else: ?>
        <div style="display:grid;gap:8px;">
          <?php foreach ($notes as $n): ?>
            <div style="padding:10px;border-radius:10px;border:1px solid #eef2ff;background:#fff;display:flex;justify-content:space-between;align-items:center;">
              <div>
                <div style="font-weight:700;"><?= e(strtoupper($n['type'])) ?> #<?= (int)$n['id'] ?></div>
                <div style="font-size:13px;color:#6b7280;">User ID: <?= (int)$n['user_id'] ?> · Status: <?= e($n['status'] ?? '-') ?></div>
              </div>
              <div style="text-align:right;">
                <div style="font-weight:700;">Rp<?= number_format((float)($n['amount'] ?? 0),0,',','.') ?></div>
                <div style="font-size:12px;color:#6b7280;"><?= e($n['created_at'] ?? '') ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

</div>

<!-- MODAL: Tampilkan detail toko (ajax sederhana) -->
<div id="shopModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:9999;">
  <div style="width:min(920px,95%);background:#fff;border-radius:12px;padding:18px;max-height:85vh;overflow:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <h3 id="shopModalTitle">Detail Toko</h3>
      <button onclick="closeShopModal()" style="background:#efefef;border:none;padding:6px 10px;border-radius:8px;cursor:pointer;">Tutup</button>
    </div>
    <div id="shopModalBody">Memuat…</div>
  </div>
</div>

<!-- MODAL: Pilih Paket -->
<div id="packageModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:10000;">
  <div style="width:min(560px,95%);background:#fff;border-radius:12px;padding:18px;max-height:85vh;overflow:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <h3>Pilih Paket untuk Toko</h3>
      <button onclick="closePackageModal()" style="background:#efefef;border:none;padding:6px 10px;border-radius:8px;cursor:pointer;">Tutup</button>
    </div>

    <div id="packageBody">
      <p style="color:#6b7280;">Pilih Paket A atau B — jumlah pembayaran akan muncul di bawah.</p>
      <input type="hidden" id="pkg_shop_id" value="">
      <div style="display:flex;gap:10px;margin-top:10px;">
        <button onclick="selectPackage('A')" style="padding:10px 12px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;">Paket A</button>
        <button onclick="selectPackage('B')" style="padding:10px 12px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;">Paket B</button>
      </div>

      <div id="packageAmount" style="margin-top:12px;font-weight:700;"></div>

      <div style="margin-top:12px;">
        <form id="assignPackageForm" method="post" action="<?= e($BASE) ?>/admin/assign_package.php">
          <input type="hidden" name="shop_id" id="form_shop_id" value="">
          <input type="hidden" name="package" id="form_package" value="">
          <input type="hidden" name="amount" id="form_amount" value="">
          <button type="submit" style="padding:10px 14px;border-radius:8px;background:linear-gradient(90deg,#10b981,#059669);color:#fff;border:none;cursor:pointer;margin-top:12px;">Simpan Paket</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openShopModal(id) {
  const modal = document.getElementById('shopModal');
  const body = document.getElementById('shopModalBody');
  modal.style.display = 'flex';
  body.innerHTML = 'Memuat detail...';

  fetch('<?= e($BASE) ?>/admin/shop_detail_ajax.php?id=' + encodeURIComponent(id))
    .then(r => r.text())
    .then(txt => { body.innerHTML = txt; })
    .catch(err => { body.innerHTML = 'Gagal memuat detail.'; });
}
function closeShopModal(){ document.getElementById('shopModal').style.display='none'; }

function openPackageModal(shopId) {
  document.getElementById('pkg_shop_id').value = shopId;
  document.getElementById('form_shop_id').value = shopId;
  document.getElementById('packageAmount').textContent = '';
  document.getElementById('form_package').value = '';
  document.getElementById('form_amount').value = '';
  document.getElementById('packageModal').style.display = 'flex';
}
function closePackageModal(){ document.getElementById('packageModal').style.display='none'; }

function selectPackage(pkg) {
  // atur nominal: sesuaikan kebutuhanmu
  const amounts = { A: 20000, B: 35000 };
  const amount = amounts[pkg] || 0;
  document.getElementById('packageAmount').textContent = 'Jumlah pembayaran: Rp' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  document.getElementById('form_package').value = pkg;
  document.getElementById('form_amount').value = amount;
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
</body>
</html>
