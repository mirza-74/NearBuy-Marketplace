<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

$BASE = '/NearBuy-Marketplace/public';

// kalau mau pakai style khusus seller (opsional)
$EXTRA_CSS = ['seller/style-toko.css'];

if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="nb-shell">
    <section class="nb-card nb-main-card">
        <h1 class="nb-title">Peraturan & Ketentuan Buka Toko NearBuy</h1>
        <p class="nb-sub">
            Halaman ini berisi kesepakatan antara pemilik toko (seller) dan NearBuy. 
            Dengan mendaftar dan mengaktifkan toko, kamu dianggap telah membaca, memahami, 
            dan menyetujui seluruh ketentuan di bawah ini.
        </p>

        <div class="nb-field">
            <div class="nb-label">1. Definisi Pihak</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li><b>NearBuy</b> adalah platform marketplace berbasis lokasi yang mempertemukan pembeli dan penjual di area terdekat.</li>
                    <li><b>Seller / Pemilik Toko</b> adalah pengguna yang mendaftarkan toko dan menjual produk melalui NearBuy.</li>
                    <li><b>Pembeli</b> adalah pengguna yang mencari dan membeli produk melalui NearBuy.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">2. Ruang Lingkup Layanan NearBuy</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>NearBuy hanya menyediakan <b>layanan pencarian lokasi toko, tampilan produk, dan informasi kontak</b>.</li>
                    <li>NearBuy <b>tidak</b> menyimpan dana transaksi dan <b>bukan perantara pembayaran</b> antara pembeli dan penjual.</li>
                    <li>Transaksi dan kesepakatan harga terjadi langsung antara pembeli dan penjual.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">3. Biaya Langganan & Potongan Admin</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>Model bisnis NearBuy menggunakan <b>sistem langganan bulanan</b>, bukan komisi per transaksi.</li>
                    <li>Paket langganan yang berlaku:
                        <ul class="nb-step-list">
                            <li>Paket A: <b>Rp20.000/bulan</b> – maksimal 15 produk aktif.</li>
                            <li>Paket B: <b>Rp35.000/bulan</b> – jumlah produk <b>tanpa batas</b>.</li>
                        </ul>
                    </li>
                    <li><b>Tidak ada potongan admin</b> dari nilai transaksi pembelian. Seluruh pembayaran dari pembeli masuk langsung ke akun penjual.</li>
                    <li>Biaya langganan dapat berubah di masa depan dengan pemberitahuan terlebih dahulu kepada seller.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">4. Kewajiban QRIS & Metode Pembayaran</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>Setiap toko <b>wajib memiliki minimal 1 metode pembayaran digital</b> yang sah, seperti:
                        <ul class="nb-step-list">
                            <li>QRIS milik toko / pemilik usaha, dan/atau</li>
                            <li>e-wallet (OVO, Dana, GoPay, dsb), dan/atau</li>
                            <li>Rekening bank atas nama pemilik usaha.</li>
                        </ul>
                    </li>
                    <li>Pembayaran dilakukan <b>langsung</b> dari pembeli ke penjual melalui QRIS / e-wallet / transfer yang ditampilkan di halaman toko.</li>
                    <li>NearBuy <b>tidak menerima, menyimpan, atau menahan dana</b> dari transaksi pembelian.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">5. Tanggung Jawab Transaksi</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>Segala bentuk transaksi, pengiriman barang, dan komunikasi lanjutan setelah pembeli menghubungi penjual adalah <b>di luar tanggung jawab NearBuy</b>.</li>
                    <li>NearBuy tidak bertanggung jawab atas:
                        <ul class="nb-step-list">
                            <li>Keterlambatan pengiriman atau barang tidak sampai.</li>
                            <li>Perbedaan kualitas, jumlah, atau kondisi barang dengan deskripsi.</li>
                            <li>Sengketa harga atau pengembalian dana antara pembeli dan penjual.</li>
                        </ul>
                    </li>
                    <li>Pembeli dan penjual wajib menyelesaikan masalah transaksi secara langsung dan kekeluargaan.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">6. Syarat & Kewajiban Pemilik Toko</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>Memberikan data toko yang benar dan dapat dipertanggungjawabkan (nama usaha, alamat, titik lokasi, kontak).</li>
                    <li>Hanya menjual produk yang sesuai kategori NearBuy (kebutuhan harian: gas, air galon, bahan pokok, kebutuhan rumah tangga kecil, dll.).</li>
                    <li>Tidak menjual barang terlarang, berbahaya, atau melanggar hukum.</li>
                    <li>Memperbarui stok, harga, dan informasi produk secara berkala.</li>
                    <li>Menjaga kualitas layanan kepada pembeli dan merespon pesan dengan sopan.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">7. Penghentian & Penonaktifan Toko</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>Toko dapat dinonaktifkan sementara atau permanen oleh NearBuy apabila:
                        <ul class="nb-step-list">
                            <li>Melanggar peraturan ini atau hukum yang berlaku.</li>
                            <li>Melakukan penipuan atau merugikan pembeli.</li>
                            <li>Mengunggah konten yang tidak pantas.</li>
                        </ul>
                    </li>
                    <li>Jika masa langganan habis dan tidak diperpanjang, toko akan otomatis <b>non-aktif</b> sampai pembayaran langganan berikutnya dikonfirmasi admin.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">8. Data & Privasi</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>NearBuy menyimpan data toko dan lokasi untuk keperluan penampilan di peta dan rekomendasi toko terdekat.</li>
                    <li>NearBuy tidak menyimpan data keuangan dari transaksi pembelian antara pembeli dan penjual.</li>
                    <li>NearBuy berhak menggunakan data agregat (tanpa identitas pribadi) untuk analisis dan pengembangan layanan.</li>
                </ul>
            </div>
        </div>

        <div class="nb-field">
            <div class="nb-label">9. Perubahan Ketentuan</div>
            <div class="nb-value">
                <ul class="nb-step-list">
                    <li>NearBuy berhak mengubah isi peraturan ini sewaktu-waktu untuk menyesuaikan kebijakan dan kebutuhan operasional.</li>
                    <li>Perubahan penting akan diinformasikan melalui pengumuman di aplikasi/website atau melalui kontak yang terdaftar.</li>
                </ul>
            </div>
        </div>

        <div class="nb-actions nb-actions-top">
            <a href="<?= e($BASE) ?>/seller/register_toko.php" class="nb-btn nb-btn-primary">
                Saya Setuju & Buka Toko
            </a>
            <a href="<?= e($BASE) ?>/seller/toko.php" class="nb-btn">
                Kembali ke Halaman Toko
            </a>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</main>
</body>
</html>
