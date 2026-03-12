<?php
declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/header.php';

$userLat = $_SESSION['user_lat'] ?? null;
$userLng = $_SESSION['user_lng'] ?? null;


/* ================= KATEGORI ================= */

$categories=[];

try{

$stmt=$pdo->prepare("
SELECT id,name,slug
FROM categories
ORDER BY name ASC
");

$stmt->execute();
$categories=$stmt->fetchAll(PDO::FETCH_ASSOC);

}catch(Throwable $e){
$categories=[];
}


/* ================= SEARCH ================= */

$searchQRaw=isset($_GET['q'])?trim((string)$_GET['q']):'';
$searchQ=mb_substr($searchQRaw,0,80);

$whereParts=[
"p.is_active=1",
"p.stock>0",
"u.role='seller'",
"s.is_active=1"
];

$params=[];

if($searchQ!==''){
$whereParts[]="(p.title LIKE ? OR p.description LIKE ?)";
$params[]="%{$searchQ}%";
$params[]="%{$searchQ}%";
}

$whereSql=implode(' AND ',$whereParts);


/* ================= PRODUK TERDEKAT ================= */

$rekom=[];

try{

if($userLat && $userLng){

$radiusKm=5;

$sql="

SELECT
p.id,
p.slug,
p.title,
p.price,
p.compare_price,
p.main_image,
s.name AS shop_name,

(
6371 * ACOS(
COS(RADIANS(:lat))
* COS(RADIANS(s.latitude))
* COS(RADIANS(s.longitude)-RADIANS(:lng))
+ SIN(RADIANS(:lat))
* SIN(RADIANS(s.latitude))
)
) AS distance_km

FROM products p
JOIN shops s ON s.id=p.shop_id
JOIN users u ON u.id=s.user_id

WHERE {$whereSql}

HAVING distance_km<=:radius

ORDER BY distance_km ASC
LIMIT 8
";

$stmt=$pdo->prepare($sql);

$stmt->bindValue(':lat',$userLat);
$stmt->bindValue(':lng',$userLng);
$stmt->bindValue(':radius',$radiusKm);

$idx=1;
foreach($params as $pVal){
$stmt->bindValue($idx,$pVal);
$idx++;
}

$stmt->execute();
$rekom=$stmt->fetchAll(PDO::FETCH_ASSOC);

}

}catch(Throwable $e){
$rekom=[];
}


/* ================= SEMUA PRODUK ================= */

$produk=[];

try{

$sql="

SELECT
p.id,
p.slug,
p.title,
p.price,
p.compare_price,
p.main_image,
s.name AS shop_name

FROM products p
JOIN shops s ON s.id=p.shop_id
JOIN users u ON u.id=s.user_id

WHERE {$whereSql}

ORDER BY p.created_at DESC
LIMIT 12
";

$stmt=$pdo->prepare($sql);
$stmt->execute($params);
$produk=$stmt->fetchAll(PDO::FETCH_ASSOC);

}catch(Throwable $e){
$produk=[];
}


/* ================= HELPER GAMBAR ================= */

function image_url($img,$BASE){

if(!$img){
return "https://via.placeholder.com/400x300?text=Produk";
}

if(str_starts_with($img,'http')){
return $img;
}

return $BASE."/uploads/".$img;

}

?>

<link rel="stylesheet" href="<?= e($BASE) ?>/location-ui.css">


<div class="page-wrap">

<div class="content">


<section class="search">

<form method="get" action="<?= e($BASE) ?>/index.php">

<input
type="text"
name="q"
value="<?= e($searchQ) ?>"
placeholder="Cari kebutuhan harian di sekitarmu">

<button type="submit">
Cari
</button>

</form>

</section>



<section class="produk-section">

<h2>Produk Terdekat</h2>


<?php if(!$userLat): ?>

<div class="location-box">

<p>
📍 NearBuy membutuhkan lokasi kamu untuk menampilkan produk terdekat.
</p>

<button id="btnLocation" class="btn-lokasi">
Gunakan Lokasi Saya
</button>

</div>

<?php else: ?>

<div class="location-active">

📍 Lokasi kamu aktif

<a href="<?= e($BASE) ?>/set_lokasi.php">
Ubah lokasi
</a>

</div>

<?php endif; ?>


<div class="produk-grid">

<?php if(!empty($rekom)): ?>

<?php foreach($rekom as $r):

$img=image_url($r['main_image'],$BASE);
$detailUrl=$BASE.'/detail_produk.php?slug='.urlencode($r['slug']);

?>

<div class="produk-card">

<a class="produk-link" href="<?= e($detailUrl) ?>">

<div class="img-wrap">
<img src="<?= e($img) ?>" loading="lazy">
</div>

<h3 class="judul"><?= e($r['title']) ?></h3>

<p class="subtext">
Toko: <?= e($r['shop_name']) ?>
</p>

<?php if(isset($r['distance_km'])): ?>

<p class="distance">
📍 <?= number_format($r['distance_km'],1) ?> km dari kamu
</p>

<?php endif; ?>

<p class="harga">

<?php if(!empty($r['compare_price']) && $r['compare_price']>$r['price']): ?>

<del>
Rp<?= number_format((float)$r['compare_price'],0,',','.') ?>
</del>
<br>

<?php endif; ?>

<b>
Rp<?= number_format((float)$r['price'],0,',','.') ?>
</b>

</p>

<p class="payment-info">
QRIS / COD
</p>

</a>

</div>

<?php endforeach; ?>

<?php else: ?>

<p>Belum ada produk di sekitar lokasi kamu.</p>

<?php endif; ?>

</div>

</section>



<section class="produk-section">

<h2>Semua Produk</h2>

<div class="produk-grid">

<?php foreach($produk as $p):

$img=image_url($p['main_image'],$BASE);
$detailUrl=$BASE.'/detail_produk.php?slug='.urlencode($p['slug']);

?>

<div class="produk-card">

<a class="produk-link" href="<?= e($detailUrl) ?>">

<div class="img-wrap">
<img src="<?= e($img) ?>" loading="lazy">
</div>

<h3 class="judul"><?= e($p['title']) ?></h3>

<p class="subtext">
Toko: <?= e($p['shop_name']) ?>
</p>

<p class="harga">

<?php if(!empty($p['compare_price']) && $p['compare_price']>$p['price']): ?>

<del>
Rp<?= number_format((float)$p['compare_price'],0,',','.') ?>
</del>
<br>

<?php endif; ?>

<b>
Rp<?= number_format((float)$p['price'],0,',','.') ?>
</b>

</p>

</a>

</div>

<?php endforeach; ?>

</div>

</section>

</div>



<aside class="sidebar">

<div class="card">

<h4>Kategori Produk</h4>

<ul>

<li>
<a href="<?= e($BASE) ?>/index.php">
Semua Produk
</a>
</li>

<?php foreach($categories as $cat): ?>

<li>
<a href="<?= e($BASE) ?>/kategori.php?slug=<?= urlencode($cat['slug']) ?>">
<?= e($cat['name']) ?>
</a>
</li>

<?php endforeach; ?>

</ul>

</div>

</aside>

</div>



<script>

const baseUrl="<?= e($BASE) ?>";

document.getElementById("btnLocation")?.addEventListener("click",function(){

if(!navigator.geolocation){
alert("Browser tidak mendukung lokasi");
return;
}

navigator.geolocation.getCurrentPosition(function(pos){

const lat=pos.coords.latitude;
const lng=pos.coords.longitude;

const formData=new FormData();
formData.append("lat",lat);
formData.append("lng",lng);

fetch(baseUrl+"/save_location.php",{
method:"POST",
body:formData
})
.then(r=>r.json())
.then(data=>{

if(data.status==="ok"){
location.reload();
}else{
alert("Gagal menyimpan lokasi");
}

});

},function(){

alert("Izin lokasi diperlukan untuk menampilkan produk terdekat");

});

});

</script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</main>
</body>
</html>