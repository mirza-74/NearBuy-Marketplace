<?php
// ===============================================================
// NearBuy – Footer
// ===============================================================
declare(strict_types=1);

// helper escape
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// pastikan BASE terisi sama dengan file public yang sedang jalan
if (!isset($BASE) || !$BASE) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $BASE = rtrim($scriptDir, '/');
}
?>
<footer class="footer-market">
  <div class="footer-top">
    <div class="footer-col about">
      <img src="<?= e($BASE) ?>/assets/logo_nearbuy.png" alt="NearBuy" class="footer-logo">
      <p>
        <b>NearBuy Marketplace</b> adalah platform untuk menemukan kebutuhan harian 
        dari penjual terdekat di sekitar kamu. Admin hanya mengelola sistem sedangkan
        seluruh produk dijual oleh seller lokal.
      </p>
    </div>

    <div class="footer-col">
      <h4>Kategori Kebutuhan</h4>
      <ul>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=makanan-minuman">Makanan dan Minuman</a></li>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=bahan-pokok">Bahan Pokok</a></li>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=air-galon">Air Galon</a></li>
        <li><a href="<?= e($BASE) ?>/kategori.php?k=gas-elpiji">Gas Elpiji</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Bantuan</h4>
      <ul>
        <li><a href="<?= e($BASE) ?>/bantuan.php">Pusat Bantuan</a></li>
        <li><a href="<?= e($BASE) ?>/cara-belanja.php">Cara Belanja di NearBuy</a></li>
        <li><a href="<?= e($BASE) ?>/pembayaran.php">Metode Pembayaran</a></li>
        <li><a href="<?= e($BASE) ?>/pengiriman.php">Pengiriman dan Retur</a></li>
        <li><a href="<?= e($BASE) ?>/kebijakan.php">Kebijakan Privasi</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Hubungi NearBuy</h4>
      <ul>
        <li>Email: <a href="mailto:support@nearbuy.com">support@nearbuy.com</a></li>
        <li>Telepon: +62 812 3456 7890</li>
        <li>Lokasi: Bangka Belitung, Indonesia</li>
      </ul>
      <div class="social-links">
        <a href="#"><img src="<?= e($BASE) ?>/assets/icon-fb.svg" alt="Facebook"></a>
        <a href="#"><img src="<?= e($BASE) ?>/assets/icon-ig.svg" alt="Instagram"></a>
        <a href="#"><img src="<?= e($BASE) ?>/assets/icon-x.svg" alt="X"></a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© <?= date('Y') ?> NearBuy Marketplace • All Rights Reserved.</p>
  </div>
</footer>
