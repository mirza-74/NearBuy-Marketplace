<?php
// ===============================================================
// NearBuy – Set Lokasi (Session Based)
// ===============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE = rtrim($scriptDir, '/');

if (!function_exists('e')) {
function e(string $s): string {
return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
}

// ambil lokasi dari session jika ada
$userLat = $_SESSION['user_lat'] ?? -2.5489;
$userLng = $_SESSION['user_lng'] ?? 118.0149;

require_once __DIR__ . '/../includes/header.php';
?>

<style>

.lokasi-wrapper{
max-width:900px;
margin:20px auto;
background:#ffffff;
border-radius:16px;
padding:20px;
box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

#map{
width:100%;
height:380px;
border-radius:14px;
margin-top:12px;
}

.lokasi-actions{
margin-top:15px;
display:flex;
justify-content:space-between;
align-items:center;
gap:10px;
flex-wrap:wrap;
}

.btn-primary{
background:#3555ff;
color:#fff;
border:none;
border-radius:999px;
padding:10px 18px;
cursor:pointer;
font-weight:600;
}

.btn-outline{
background:#f9fafb;
border:1px solid #e5e7eb;
border-radius:999px;
padding:8px 14px;
cursor:pointer;
}

.coord-text{
font-size:13px;
color:#4b5563;
margin-top:6px;
}

</style>


<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<div class="page-wrap">
<div class="content">

<div class="lokasi-wrapper">

<h2>Atur Lokasi Saya</h2>

<p style="font-size:13px;color:#6b7280;">
Lokasi ini digunakan NearBuy untuk menampilkan produk dari penjual
yang berada paling dekat dengan kamu.
</p>

<div id="map"></div>

<div class="lokasi-actions">

<div>

<button class="btn-outline" id="btnMyLocation">
Gunakan lokasi saya
</button>

<div class="coord-text">
Lat: <span id="latText"><?= e((string)$userLat) ?></span> ,
Lng: <span id="lngText"><?= e((string)$userLng) ?></span>
</div>

</div>

<div>
<button class="btn-primary" id="btnSave">
Simpan Lokasi
</button>
</div>

</div>

<input type="hidden" id="latInput" value="<?= e((string)$userLat) ?>">
<input type="hidden" id="lngInput" value="<?= e((string)$userLng) ?>">

</div>

</div>
</div>


<script>

const baseUrl = "<?= e($BASE) ?>";

const startLat = parseFloat(document.getElementById("latInput").value);
const startLng = parseFloat(document.getElementById("lngInput").value);

const map = L.map("map").setView([startLat,startLng],13);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{
maxZoom:19,
attribution:"© OpenStreetMap"
}).addTo(map);

const marker = L.marker([startLat,startLng],{draggable:true}).addTo(map);

function updateCoord(lat,lng){

document.getElementById("latInput").value = lat;
document.getElementById("lngInput").value = lng;

document.getElementById("latText").textContent = lat.toFixed(6);
document.getElementById("lngText").textContent = lng.toFixed(6);

}

marker.on("dragend",function(){

const pos = marker.getLatLng();

updateCoord(pos.lat,pos.lng);

});


document.getElementById("btnMyLocation").addEventListener("click",function(){

if(!navigator.geolocation){
alert("Browser tidak mendukung lokasi");
return;
}

navigator.geolocation.getCurrentPosition(function(pos){

const lat = pos.coords.latitude;
const lng = pos.coords.longitude;

marker.setLatLng([lat,lng]);
map.setView([lat,lng],15);

updateCoord(lat,lng);

},function(){

alert("Gagal mengambil lokasi");

});

});


document.getElementById("btnSave").addEventListener("click",function(){

const lat = document.getElementById("latInput").value;
const lng = document.getElementById("lngInput").value;

const formData = new FormData();

formData.append("lat",lat);
formData.append("lng",lng);

fetch(baseUrl + "/save_location.php",{
method:"POST",
body:formData
})
.then(r=>r.json())
.then(data=>{

if(data.status==="ok"){

alert("Lokasi berhasil disimpan");

window.location.href = baseUrl + "/index.php";

}else{

alert("Gagal menyimpan lokasi");

}

});

});

</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

</main>
</body>
</html>