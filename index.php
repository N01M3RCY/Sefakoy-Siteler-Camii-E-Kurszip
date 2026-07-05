<?php
require_once 'config/db.php';
session_start_safe();

if (isset($_SESSION['admin_id']))   redirect('admin/index.php');
if (isset($_SESSION['mosque_id']))  redirect('mosque/index.php');
if (isset($_SESSION['parent_id']))  redirect('parent/index.php');
if (isset($_SESSION['teacher_id'])) redirect('teacher/index.php');

$ayetler = [
  ["metin" => "Şüphesiz Allah adaleti, iyilik yapmayı ve akrabaya yardım etmeyi emreder.", "kaynak" => "Nahl, 90"],
  ["metin" => "Allah sizinle beraber olanlarla beraberdir. O, amellerinizi asla eksiltmez.", "kaynak" => "Muhammed, 35"],
  ["metin" => "Rabbiniz şöyle buyurdu: Bana dua edin, duanıza icabet edeyim.", "kaynak" => "Mümin, 60"],
  ["metin" => "Kim bir iyilik yaparsa ona on katı vardır.", "kaynak" => "En'am, 160"],
  ["metin" => "İnananlar ancak kardeştirler.", "kaynak" => "Hucurat, 10"],
  ["metin" => "Siz Allah'a yardım ederseniz O da size yardım eder, ayaklarınızı sabit kılar.", "kaynak" => "Muhammed, 7"],
  ["metin" => "Güçlük içinde kolaylık mutlaka vardır.", "kaynak" => "İnşirah, 6"],
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

$ayet  = $ayetler[(int)date('d') % count($ayetler)];
$hadis = $hadisler[(int)date('N') % count($hadisler)];

$gunler = ['','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
$aylar  = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$gun = $gunler[(int)date('N')];
$ay  = $aylar[(int)date('n')];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Cami Öğrenci Yönetim Sistemi</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
  background:#f0f2f5;
  color:#1a1a2e;
  min-height:100vh;
}

/* ══ HERO BANNER ══════════════════════════════ */
.hero{
  background: linear-gradient(145deg,#0d5c2e 0%,#1a7a40 60%,#0a4a24 100%);
  color:#fff;
  text-align:center;
  padding:36px 20px 56px;
  position:relative;
  overflow:hidden;
}
.hero::before{
  content:'';
  position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-bismillah{
  font-size:22px;
  color:#f0d060;
  letter-spacing:1px;
  margin-bottom:14px;
  text-shadow:0 2px 8px rgba(0,0,0,.3);
}
.hero-logo{font-size:52px;line-height:1;margin-bottom:10px}
.hero-title{
  font-size:clamp(20px,5vw,28px);
  font-weight:800;
  letter-spacing:-.3px;
  margin-bottom:6px;
}
.hero-sub{
  font-size:11px;
  letter-spacing:2px;
  text-transform:uppercase;
  color:rgba(255,255,255,.5);
}
.hero-line{
  width:50px;height:2px;
  background:linear-gradient(90deg,transparent,#c9a227,transparent);
  margin:16px auto 0;
  border-radius:99px;
}

/* ══ CONTAINER ════════════════════════════════ */
.container{
  max-width:520px;
  margin:0 auto;
  padding:0 16px 32px;
  position:relative;
  top:-20px;
}

/* ══ SAAT KARTI ═══════════════════════════════ */
.clock-card{
  background:#fff;
  border-radius:18px;
  padding:22px 24px;
  box-shadow:0 4px 24px rgba(0,0,0,.09);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
}
.clock-left{}
.clock-time{
  font-size:42px;
  font-weight:800;
  color:#0d5c2e;
  font-variant-numeric:tabular-nums;
  line-height:1;
  letter-spacing:-1px;
}
.clock-date{
  font-size:13px;
  color:#64748b;
  margin-top:4px;
}
.clock-hijri{
  font-size:12px;
  color:#c9a227;
  font-weight:700;
  margin-top:2px;
}
.clock-right{
  text-align:center;
  background:#f8f9fa;
  border-radius:12px;
  padding:12px 16px;
  min-width:90px;
}
.clock-right .day-num{
  font-size:36px;
  font-weight:900;
  color:#0d5c2e;
  line-height:1;
}
.clock-right .day-name{
  font-size:11px;
  color:#94a3b8;
  font-weight:600;
  text-transform:uppercase;
  letter-spacing:1px;
}
.clock-right .month-name{
  font-size:13px;
  color:#475569;
  font-weight:600;
  margin-top:2px;
}

/* ══ NAMAZ VT ═════════════════════════════════ */
.vakitler-card{
  background:#fff;
  border-radius:18px;
  padding:18px 20px;
  box-shadow:0 4px 24px rgba(0,0,0,.09);
  margin-bottom:14px;
}
.card-label{
  font-size:10px;
  font-weight:800;
  letter-spacing:2px;
  text-transform:uppercase;
  color:#94a3b8;
  margin-bottom:14px;
  display:flex;
  align-items:center;
  gap:8px;
}
.card-label span{font-size:11px;color:#c9a227;font-weight:600;text-transform:none;letter-spacing:0;margin-left:auto}
.vakitler-row{
  display:grid;
  grid-template-columns:repeat(6,1fr);
  gap:6px;
}
.vakit-item{
  text-align:center;
  padding:10px 4px;
  border-radius:12px;
  background:#f8fafc;
  transition:background .2s;
}
.vakit-item.aktif{
  background:linear-gradient(135deg,#0d5c2e,#1a7a40);
  box-shadow:0 4px 12px rgba(13,92,46,.3);
}
.vakit-item .v-name{
  font-size:9px;
  font-weight:700;
  letter-spacing:.5px;
  color:#94a3b8;
  text-transform:uppercase;
  margin-bottom:5px;
}
.vakit-item.aktif .v-name{color:rgba(255,255,255,.7)}
.vakit-item .v-time{
  font-size:14px;
  font-weight:800;
  color:#1e293b;
  font-variant-numeric:tabular-nums;
}
.vakit-item.aktif .v-time{color:#fff}

/* ══ İÇERİK KARTLARI ═════════════════════════ */
.content-card{
  background:#fff;
  border-radius:18px;
  padding:22px 22px;
  box-shadow:0 4px 24px rgba(0,0,0,.09);
  margin-bottom:14px;
}
.content-card .badge{
  display:inline-flex;
  align-items:center;
  gap:5px;
  background:#f0fdf4;
  color:#0d5c2e;
  font-size:11px;
  font-weight:700;
  padding:4px 10px;
  border-radius:999px;
  border:1px solid #bbf7d0;
  margin-bottom:12px;
  letter-spacing:.3px;
}
.content-card .badge.gold{
  background:#fffbeb;
  color:#92400e;
  border-color:#fde68a;
}
.ayet-metin{
  font-size:16px;
  line-height:1.75;
  color:#1e293b;
  font-style:italic;
  margin-bottom:10px;
}
.ayet-kaynak{
  font-size:12px;
  color:#0d5c2e;
  font-weight:700;
}
.hadis-metin{
  font-size:15px;
  line-height:1.7;
  color:#334155;
  margin-bottom:10px;
}
.hadis-kaynak{
  font-size:12px;
  color:#c9a227;
  font-weight:700;
}

/* ══ BUTONLAR ════════════════════════════════ */
.cta-section{
  display:flex;
  flex-direction:column;
  gap:10px;
  margin-bottom:14px;
}
.btn-veli{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  background:linear-gradient(135deg,#0d5c2e,#1a7a40);
  color:#fff;
  font-weight:700;
  font-size:15px;
  padding:16px 24px;
  border-radius:14px;
  text-decoration:none;
  box-shadow:0 6px 20px rgba(13,92,46,.3);
  transition:transform .2s,box-shadow .2s;
}
.btn-veli:hover{transform:translateY(-1px);box-shadow:0 10px 28px rgba(13,92,46,.4)}
.btn-register{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  background:#fff;
  color:#0d5c2e;
  font-weight:600;
  font-size:14px;
  padding:14px 24px;
  border-radius:14px;
  text-decoration:none;
  border:2px solid #bbf7d0;
  transition:border-color .2s,background .2s;
}
.btn-register:hover{background:#f0fdf4;border-color:#0d5c2e}

/* ══ FOOTER ══════════════════════════════════ */
.site-footer{
  background:#fff;
  border-radius:18px;
  padding:18px 20px;
  box-shadow:0 4px 24px rgba(0,0,0,.09);
  text-align:center;
}
.footer-links{
  display:flex;
  align-items:center;
  justify-content:center;
  flex-wrap:wrap;
  gap:4px 16px;
  margin-bottom:12px;
}
.footer-links a{
  font-size:12px;
  color:#94a3b8;
  text-decoration:none;
  transition:color .2s;
}
.footer-links a:hover{color:#0d5c2e}
.footer-sep{color:#e2e8f0}
.footer-copy{
  font-size:11px;
  color:#cbd5e1;
}
</style>
</head>
<body>

<!-- HERO -->
<div class="hero">
  <div class="hero-bismillah">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ</div>
  <div class="hero-logo">🕌</div>
  <h1 class="hero-title">Cami Öğrenci Yönetim Sistemi</h1>
  <p class="hero-sub">Eğitim · Takip · Yönetim</p>
  <div class="hero-line"></div>
</div>

<div class="container">

  <!-- SAAT -->
  <div class="clock-card">
    <div class="clock-left">
      <div class="clock-time" id="saat">--:--</div>
      <div class="clock-date"><?= $gun ?></div>
      <div class="clock-hijri" id="hijri">— H</div>
    </div>
    <div class="clock-right">
      <div class="day-num"><?= date('d') ?></div>
      <div class="month-name"><?= $ay ?></div>
      <div class="day-name"><?= date('Y') ?></div>
    </div>
  </div>

  <!-- NAMAZ VAKİTLERİ -->
  <div class="vakitler-card">
    <div class="card-label">
      🕐 Namaz Vakitleri
      <span id="vakit-sehir">📍 yükleniyor...</span>
    </div>
    <div class="vakitler-row">
      <?php
      $vakitler = ['İmsak','Güneş','Öğle','İkindi','Akşam','Yatsı'];
      foreach ($vakitler as $v): ?>
      <div class="vakit-item" id="vi_<?= $v ?>">
        <div class="v-name"><?= $v ?></div>
        <div class="v-time" id="vt_<?= $v ?>">--:--</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- GÜNÜN AYETİ -->
  <div class="content-card">
    <div class="badge">📖 Günün Ayeti</div>
    <div class="ayet-metin">"<?= htmlspecialchars($ayet['metin']) ?>"</div>
    <div class="ayet-kaynak">— <?= htmlspecialchars($ayet['kaynak']) ?></div>
  </div>

  <!-- GÜNÜN HADİSİ -->
  <div class="content-card">
    <div class="badge gold">🌿 Günün Hadisi</div>
    <div class="hadis-metin">"<?= htmlspecialchars($hadis['metin']) ?>"</div>
    <div class="hadis-kaynak">— <?= htmlspecialchars($hadis['kaynak']) ?></div>
  </div>

  <!-- CTA -->
  <div class="cta-section">
    <a href="parent/login.php" class="btn-veli">
      👨‍👩‍👧 Veli Paneline Giriş
    </a>
    <a href="register.php" class="btn-register">
      📋 Çocuğumu Kaydet
    </a>
  </div>

  <!-- FOOTER -->
  <div class="site-footer">
    <div class="footer-links">
      <a href="admin/login.php">⚙️ Yönetici Girişi</a>
      <span class="footer-sep">|</span>
      <a href="mosque/login.php">🕌 Cami Personeli</a>
      <span class="footer-sep">|</span>
      <a href="teacher/login.php">👨‍🏫 Hoca Girişi</a>
      <span class="footer-sep">|</span>
      <a href="mosque/register.php">+ Cami Kaydı</a>
    </div>
    <div class="footer-copy">Cami Öğrenci Yönetim Sistemi © <?= date('Y') ?></div>
  </div>

</div>

<script>
// Canlı saat
function tick(){
  const n=new Date();
  document.getElementById('saat').textContent=
    String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
}
tick(); setInterval(tick,1000);

// Hicri tarih
const td=new Date();
const dstr=String(td.getDate()).padStart(2,0)+'-'+String(td.getMonth()+1).padStart(2,0)+'-'+td.getFullYear();
fetch('https://api.aladhan.com/v1/gToH/'+dstr)
  .then(r=>r.json()).then(d=>{
    const h=d.data.hijri;
    document.getElementById('hijri').textContent=h.day+' '+h.month.ar+' '+h.year+' H';
  }).catch(()=>{});

// Namaz vakitleri
const VK=['İmsak','Güneş','Öğle','İkindi','Akşam','Yatsı'];
const AK=['Fajr','Sunrise','Dhuhr','Asr','Maghrib','Isha'];

function yukleVakitler(lat,lon,sehir){
  const n=new Date();
  const ds=String(n.getDate()).padStart(2,0)+'-'+String(n.getMonth()+1).padStart(2,0)+'-'+n.getFullYear();
  fetch('https://api.aladhan.com/v1/timings/'+ds+'?latitude='+lat+'&longitude='+lon+'&method=13')
    .then(r=>r.json()).then(d=>{
      const t=d.data.timings;
      if(sehir) document.getElementById('vakit-sehir').textContent='📍 '+sehir;
      const sim=n.getHours()*60+n.getMinutes();
      let aktif=0;
      AK.forEach((k,i)=>{
        if(!t[k])return;
        const p=t[k].split(':');
        if(parseInt(p[0])*60+parseInt(p[1])<=sim) aktif=i;
      });
      AK.forEach((k,i)=>{
        const el=document.getElementById('vt_'+VK[i]);
        if(el) el.textContent=t[k]?t[k].substring(0,5):'--:--';
        const vi=document.getElementById('vi_'+VK[i]);
        if(vi) vi.classList.toggle('aktif',i===aktif);
      });
    }).catch(()=>{
      document.getElementById('vakit-sehir').textContent='bağlanılamadı';
    });
}

if(navigator.geolocation){
  navigator.geolocation.getCurrentPosition(
    p=>{
      fetch('https://nominatim.openstreetmap.org/reverse?lat='+p.coords.latitude+'&lon='+p.coords.longitude+'&format=json')
        .then(r=>r.json()).then(d=>{
          const s=d.address.city||d.address.town||d.address.county||'';
          yukleVakitler(p.coords.latitude,p.coords.longitude,s);
        }).catch(()=>yukleVakitler(p.coords.latitude,p.coords.longitude,''));
    },
    ()=>yukleVakitler(41.0082,28.9784,'İstanbul'),
    {timeout:6000}
  );
} else {
  yukleVakitler(41.0082,28.9784,'İstanbul');
}
</script>
</body>
</html>
