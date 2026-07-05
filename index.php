<?php
require_once 'config/db.php';
session_start_safe();

if (isset($_SESSION['admin_id'])) redirect('admin/index.php');
if (isset($_SESSION['mosque_id'])) redirect('mosque/index.php');
if (isset($_SESSION['parent_id'])) redirect('parent/index.php');

// Günlük ayet & hadis (gün sayısına göre döngü)
$ayetler = [
  ["metin" => "Şüphesiz Allah adaleti, iyilik yapmayı ve akrabaya yardım etmeyi emreder.", "kaynak" => "Nahl Suresi, 90. Ayet"],
  ["metin" => "Allah sizinle beraber olanlarla beraberdir. O, amellerinizi asla eksiltmez.", "kaynak" => "Muhammed Suresi, 35. Ayet"],
  ["metin" => "Rabbiniz şöyle buyurdu: Bana dua edin, duanıza icabet edeyim.", "kaynak" => "Mümin Suresi, 60. Ayet"],
  ["metin" => "Kim bir iyilik yaparsa ona on katı vardır.", "kaynak" => "En'am Suresi, 160. Ayet"],
  ["metin" => "İnananlar ancak kardeştirler.", "kaynak" => "Hucurat Suresi, 10. Ayet"],
  ["metin" => "Siz Allah'a yardım ederseniz O da size yardım eder, ayaklarınızı sabit kılar.", "kaynak" => "Muhammed Suresi, 7. Ayet"],
  ["metin" => "Güçlük içinde kolaylık mutlaka vardır.", "kaynak" => "İnşirah Suresi, 6. Ayet"],
];

$hadisler = [
  ["metin" => "Çocuklarınıza ikramda bulunun ve güzel terbiye edin.", "kaynak" => "İbn Mace"],
  ["metin" => "Komşusu açken tok yatan bizden değildir.", "kaynak" => "Hakim"],
  ["metin" => "İlim öğrenmek her Müslüman'a farzdır.", "kaynak" => "İbn Mace"],
  ["metin" => "Sizin en hayırlınız Kur'an'ı öğrenen ve öğretendir.", "kaynak" => "Buhari"],
  ["metin" => "Mümin aynı delikten iki defa ısırılmaz.", "kaynak" => "Buhari, Müslim"],
  ["metin" => "Güzel ahlak en ağır basandır.", "kaynak" => "Tirmizi"],
  ["metin" => "Kolaylaştırınız, güçleştirmeyiniz; müjdeleyiniz, nefret ettirmeyiniz.", "kaynak" => "Buhari"],
];

$idx   = (int)date('d') % count($ayetler);
$hidx  = (int)date('N') % count($hadisler);
$ayet  = $ayetler[$idx];
$hadis = $hadisler[$hidx];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cami Öğrenci Yönetim Sistemi</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* ── Genel ── */
.home-page {
  min-height: 100vh;
  background: linear-gradient(160deg, #0a4a24 0%, #0d5c2e 50%, #0a3d1e 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  font-family: 'Segoe UI', sans-serif;
  color: #fff;
}

/* ── Header ── */
.home-header {
  width: 100%;
  max-width: 860px;
  text-align: center;
  padding: 52px 24px 32px;
}
.home-bismillah {
  font-size: 28px;
  color: #f0d060;
  letter-spacing: 2px;
  margin-bottom: 12px;
  text-shadow: 0 2px 8px rgba(0,0,0,.4);
}
.home-logo {
  font-size: 56px;
  line-height: 1;
  margin-bottom: 10px;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,.4));
}
.home-title {
  font-size: clamp(22px, 4vw, 34px);
  font-weight: 800;
  margin: 0 0 6px;
  letter-spacing: -0.5px;
}
.home-sub {
  color: rgba(255,255,255,.55);
  font-size: 14px;
  letter-spacing: 1px;
}
.home-divider {
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, transparent, #c9a227, transparent);
  margin: 18px auto 0;
  border-radius: 99px;
}

/* ── Ana içerik grid ── */
.home-content {
  width: 100%;
  max-width: 860px;
  padding: 0 20px 40px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 640px) {
  .home-content { grid-template-columns: 1fr; }
}

/* ── Kartlar ── */
.home-card {
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 16px;
  padding: 26px 24px;
  backdrop-filter: blur(6px);
  transition: transform .2s, background .2s;
}
.home-card:hover { background: rgba(255,255,255,.11); transform: translateY(-2px); }
.home-card-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: #c9a227;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.home-card-label::after {
  content:'';
  flex:1;
  height:1px;
  background: rgba(201,162,39,.3);
}

/* Ayet kartı */
.ayet-text {
  font-size: 17px;
  line-height: 1.7;
  color: #fff;
  margin-bottom: 10px;
  font-style: italic;
}
.ayet-source {
  font-size: 12px;
  color: #c9a227;
  font-weight: 600;
}

/* Hadis kartı */
.hadis-text {
  font-size: 15px;
  line-height: 1.7;
  color: rgba(255,255,255,.9);
  margin-bottom: 10px;
}
.hadis-source { font-size: 12px; color: #c9a227; font-weight: 600; }

/* Namaz vakitleri */
.vakitler-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-top: 4px;
}
.vakit-item {
  background: rgba(255,255,255,.06);
  border-radius: 10px;
  padding: 10px 6px;
  text-align: center;
}
.vakit-name {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #c9a227;
  text-transform: uppercase;
  margin-bottom: 4px;
}
.vakit-time {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  font-variant-numeric: tabular-nums;
}
.vakit-item.aktif {
  background: rgba(201,162,39,.2);
  border: 1px solid rgba(201,162,39,.4);
}

/* Saat widget */
.clock-card {
  grid-column: span 2;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 20px 24px;
}
@media (max-width: 640px) { .clock-card { grid-column: span 1; } }
.clock-left { display: flex; flex-direction: column; gap: 2px; }
.clock-time {
  font-size: 40px;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  letter-spacing: 2px;
  line-height: 1;
}
.clock-date { color: rgba(255,255,255,.55); font-size: 14px; }
.clock-hijri { color: #c9a227; font-size: 12px; font-weight: 600; margin-top: 4px; }
.clock-quote {
  max-width: 340px;
  font-size: 13px;
  color: rgba(255,255,255,.6);
  line-height: 1.6;
  text-align: right;
}

/* ── Veli Kayıt Butonu ── */
.home-cta {
  width: 100%;
  max-width: 860px;
  padding: 0 20px 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.btn-veli {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #c9a227, #e8bc30);
  color: #0a3d1e;
  font-weight: 800;
  font-size: 16px;
  padding: 14px 36px;
  border-radius: 999px;
  text-decoration: none;
  box-shadow: 0 6px 24px rgba(201,162,39,.4);
  transition: transform .2s, box-shadow .2s;
}
.btn-veli:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(201,162,39,.5); }
.btn-register {
  color: rgba(255,255,255,.55);
  font-size: 13px;
  text-decoration: none;
  transition: color .2s;
}
.btn-register:hover { color: #c9a227; }

/* ── Alt çizgi linkler (admin/cami) ── */
.home-footer {
  width: 100%;
  border-top: 1px solid rgba(255,255,255,.07);
  margin-top: auto;
  padding: 22px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 6px 24px;
}
.home-footer a {
  color: rgba(255,255,255,.3);
  font-size: 12px;
  text-decoration: none;
  transition: color .2s;
}
.home-footer a:hover { color: rgba(255,255,255,.65); }
.home-footer .sep { color: rgba(255,255,255,.15); }
.home-copyright {
  width: 100%;
  text-align: center;
  font-size: 11px;
  color: rgba(255,255,255,.2);
  padding-bottom: 16px;
}
</style>
</head>
<body>
<div class="home-page">

  <!-- HEADER -->
  <div class="home-header">
    <div class="home-bismillah">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</div>
    <div class="home-logo">🕌</div>
    <h1 class="home-title">Cami Öğrenci Yönetim Sistemi</h1>
    <p class="home-sub">DİYANET İŞLERİ BAŞKANLIĞI &middot; MÜFTÜLÜK HİZMETLERİ</p>
    <div class="home-divider"></div>
  </div>

  <!-- ANA İÇERİK -->
  <div class="home-content">

    <!-- Saat & Tarih -->
    <div class="home-card clock-card">
      <div class="clock-left">
        <div class="clock-time" id="saat">--:--:--</div>
        <div class="clock-date" id="tarih"><?= strftime_tr(date('l'), date('N')) ?>, <?= date('d') ?> <?= ay_tr(date('n')) ?> <?= date('Y') ?></div>
        <div class="clock-hijri" id="hijri">Hicri takvim yükleniyor...</div>
      </div>
      <div class="clock-quote">
        "Çocuklarınıza üç şey öğretin:<br>Peygamberinizin sevgisi, Ehl-i Beyt'in sevgisi ve Kur'an okumak."
        <br><span style="color:#c9a227;font-size:11px">— Hadis-i Şerif</span>
      </div>
    </div>

    <!-- Günün Ayeti -->
    <div class="home-card">
      <div class="home-card-label">📖 Günün Ayeti</div>
      <div class="ayet-text">"<?= htmlspecialchars($ayet['metin']) ?>"</div>
      <div class="ayet-source">— <?= htmlspecialchars($ayet['kaynak']) ?></div>
    </div>

    <!-- Günün Hadisi -->
    <div class="home-card">
      <div class="home-card-label">🌿 Günün Hadisi</div>
      <div class="hadis-text">"<?= htmlspecialchars($hadis['metin']) ?>"</div>
      <div class="hadis-source">— <?= htmlspecialchars($hadis['kaynak']) ?></div>
    </div>

    <!-- Namaz Vakitleri -->
    <div class="home-card" style="grid-column: span 2" id="vakitlerCard">
      <div class="home-card-label">🕐 Namaz Vakitleri <span id="vakit-sehir" style="font-weight:400;color:rgba(255,255,255,.4);font-size:10px;letter-spacing:0;text-transform:none;margin-left:6px">konum alınıyor...</span></div>
      <div class="vakitler-grid" id="vakitlerGrid">
        <?php
        $vakit_isimleri = ['İmsak','Güneş','Öğle','İkindi','Akşam','Yatsı'];
        foreach ($vakit_isimleri as $v): ?>
        <div class="vakit-item">
          <div class="vakit-name"><?= $v ?></div>
          <div class="vakit-time" id="v<?= $v ?>">--:--</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- CTA BUTONLAR -->
  <div class="home-cta">
    <a href="parent/login.php" class="btn-veli">👨‍👩‍👧 Veli Paneline Giriş</a>
    <a href="register.php" class="btn-register">📋 Çocuğunuzu kaydetmek ister misiniz? → Veli Kayıt Formu</a>
  </div>

  <!-- FOOTER (admin/cami linkleri) -->
  <div class="home-footer">
    <a href="admin/login.php">⚙️ Yönetici Girişi</a>
    <span class="sep">|</span>
    <a href="mosque/login.php">🕌 Cami Personel Girişi</a>
    <span class="sep">|</span>
    <a href="mosque/register.php">+ Cami Kaydı</a>
  </div>
  <div class="home-copyright">
    Cami Öğrenci Yönetim Sistemi &copy; <?= date('Y') ?> &middot; Tüm hakları saklıdır
  </div>

</div>

<script>
// ── Saat ──
function saatiGuncelle() {
  const now = new Date();
  document.getElementById('saat').textContent =
    String(now.getHours()).padStart(2,'0') + ':' +
    String(now.getMinutes()).padStart(2,'0') + ':' +
    String(now.getSeconds()).padStart(2,'0');
}
saatiGuncelle();
setInterval(saatiGuncelle, 1000);

// ── Hicri Takvim ──
fetch('https://api.aladhan.com/v1/gToH/' + new Date().toLocaleDateString('en-GB').replace(/\//g,'-'))
  .then(r => r.json())
  .then(d => {
    const h = d.data.hijri;
    document.getElementById('hijri').textContent =
      h.day + ' ' + h.month.en + ' ' + h.year + ' H';
  }).catch(() => { document.getElementById('hijri').textContent = ''; });

// ── Namaz Vakitleri (konum tabanlı) ──
const vakit_isimleri = ['İmsak','Güneş','Öğle','İkindi','Akşam','Yatsı'];
const aladhan_keys  = ['Fajr','Sunrise','Dhuhr','Asr','Maghrib','Isha'];

function vakitleriYukle(lat, lon, sehir) {
  const today = new Date();
  const dd = String(today.getDate()).padStart(2,'0');
  const mm = String(today.getMonth()+1).padStart(2,'0');
  const yyyy = today.getFullYear();
  fetch(`https://api.aladhan.com/v1/timings/${dd}-${mm}-${yyyy}?latitude=${lat}&longitude=${lon}&method=13`)
    .then(r => r.json())
    .then(d => {
      const t = d.data.timings;
      if (sehir) document.getElementById('vakit-sehir').textContent = '📍 ' + sehir;
      aladhan_keys.forEach((k, i) => {
        const el = document.getElementById('v' + vakit_isimleri[i]);
        if (el) el.textContent = t[k] ? t[k].substring(0,5) : '--:--';
      });
      // Aktif vakti vurgula
      const simdi = today.getHours() * 60 + today.getMinutes();
      let aktifIdx = 0;
      aladhan_keys.forEach((k, i) => {
        if (!t[k]) return;
        const [h, m] = t[k].split(':').map(Number);
        if (h * 60 + m <= simdi) aktifIdx = i;
      });
      document.querySelectorAll('.vakit-item').forEach((el, i) => {
        el.classList.toggle('aktif', i === aktifIdx);
      });
    }).catch(() => {
      vakit_isimleri.forEach(v => {
        const el = document.getElementById('v' + v);
        if (el) el.textContent = '—';
      });
      document.getElementById('vakit-sehir').textContent = 'bağlanılamadı';
    });
}

if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(
    pos => {
      // Ters geocode ile şehir adı al
      fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`)
        .then(r => r.json())
        .then(d => {
          const sehir = d.address.city || d.address.town || d.address.county || '';
          vakitleriYukle(pos.coords.latitude, pos.coords.longitude, sehir);
        }).catch(() => vakitleriYukle(pos.coords.latitude, pos.coords.longitude, ''));
    },
    () => {
      // Konum reddedilirse İstanbul varsayılan
      vakitleriYukle(41.0082, 28.9784, 'İstanbul');
    },
    { timeout: 6000 }
  );
} else {
  vakitleriYukle(41.0082, 28.9784, 'İstanbul');
}
</script>
</body>
</html>
<?php
function strftime_tr($day_en, $dow) {
  $tr = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
  return $tr[$dow - 1] ?? $day_en;
}
function ay_tr($n) {
  $a = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
  return $a[(int)$n] ?? '';
}
?>
