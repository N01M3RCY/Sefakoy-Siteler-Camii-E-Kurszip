<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

$course = $db->prepare("SELECT * FROM courses WHERE teacher_id=? AND mosque_id=? LIMIT 1");
$course->execute([$tid, $mid]); $course = $course->fetch();
$cid = $course['id'] ?? null;

$success = $error = '';

// QR Tarama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_qr'])) {
    $code = trim(strtoupper($_POST['qr_code'] ?? ''));
    if ($code) {
        $cond = $cid ? "AND s.course_id=$cid" : "AND 1=0";
        $stmt = $db->prepare("SELECT s.* FROM students s WHERE s.qr_code=? AND s.mosque_id=? $cond AND s.status='active'");
        $stmt->execute([$code, $mid]);
        $student = $stmt->fetch();
        if ($student) {
            $chk = $db->prepare("SELECT id FROM attendance WHERE student_id=? AND mosque_id=? AND scan_date=CURDATE()");
            $chk->execute([$student['id'], $mid]);
            if ($chk->fetch()) {
                $success = '⚠️ '.sanitize($student['name'].' '.$student['surname']).' bugün zaten işaretlendi.';
            } else {
                $db->prepare("INSERT INTO attendance (student_id,mosque_id,scan_date,scan_time) VALUES (?,?,CURDATE(),CURTIME())")->execute([$student['id'], $mid]);
                $success = '✅ '.sanitize($student['name'].' '.$student['surname']).' yoklamaya eklendi!';
            }
        } else { $error = 'QR kod bulunamadı veya bu kursa ait değil.'; }
    }
}

// Manuel toplu yoklama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_attendance'])) {
    $selected = $_POST['student_ids'] ?? [];
    $raw_date = $_POST['att_date'] ?? date('Y-m-d');
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_date) ? $raw_date : date('Y-m-d');
    $count = 0;
    foreach ($selected as $sid) {
        $sid = (int)$sid;
        $chk = $db->prepare("SELECT id FROM students WHERE id=? AND mosque_id=? AND status='active'" . ($cid ? " AND course_id=$cid" : " AND 1=0"));
        $chk->execute([$sid, $mid]);
        if (!$chk->fetch()) continue;
        $dup = $db->prepare("SELECT id FROM attendance WHERE student_id=? AND mosque_id=? AND scan_date=?");
        $dup->execute([$sid, $mid, $date]);
        if ($dup->fetch()) continue;
        $db->prepare("INSERT INTO attendance (student_id,mosque_id,scan_date,scan_time) VALUES (?,?,?,?)")->execute([$sid, $mid, $date, date('H:i:s')]);
        $count++;
    }
    $success = "✅ $count öğrenci ".sanitize($date)." tarihinde yoklamaya eklendi.";
}

$date = $_GET['date'] ?? date('Y-m-d');
$cond = $cid ? "AND s.course_id=$cid" : "AND 1=0";
$records = $db->prepare("SELECT a.*, s.name, s.surname, s.gender, s.age, s.qr_code FROM attendance a JOIN students s ON a.student_id=s.id WHERE a.mosque_id=? AND a.scan_date=? $cond ORDER BY a.scan_time DESC");
$records->execute([$mid, $date]); $records = $records->fetchAll();

$todayCount = count($records);
$allStudents = $cid ? $db->query("SELECT id,name,surname,age,gender FROM students WHERE mosque_id=$mid AND course_id=$cid AND status='active' ORDER BY name")->fetchAll() : [];
$todayIds = array_column($records, 'student_id');

$page_title = 'Yoklama Al';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
<?php if (!$cid): ?><div class="alert alert-info">⚠️ Kursa atanmadınız. Yoklama alamazsınız.</div><?php else: ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <div class="card">
    <div class="card-header"><span class="card-title">📷 QR Kod ile Yoklama</span></div>
    <div class="card-body">
      <form method="post" id="attendanceForm">
        <div class="form-group">
          <input type="text" name="qr_code" id="qrInput" class="form-control" placeholder="QR kodu girin..." autofocus style="font-family:monospace;font-size:16px;text-transform:uppercase" required>
        </div>
        <div style="display:flex;gap:10px">
          <button name="scan_qr" class="btn btn-primary btn-lg" style="flex:1">✅ Ekle</button>
          <button type="button" onclick="openCamera()" class="btn btn-gold btn-lg">📷</button>
        </div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">📊 Bugün Özeti</span></div>
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:56px;font-weight:800;color:#6d28d9"><?= $todayCount ?></div>
      <div style="color:#64748b">öğrenci bugün geldi</div>
      <?php $pct = count($allStudents)>0?round($todayCount/count($allStudents)*100):0; ?>
      <div style="background:#e2e8f0;border-radius:999px;height:10px;margin-top:12px">
        <div style="background:linear-gradient(90deg,#6d28d9,#8b5cf6);height:10px;border-radius:999px;width:<?= $pct ?>%"></div>
      </div>
      <div style="font-size:13px;font-weight:700;color:#6d28d9;margin-top:6px">%<?= $pct ?> katılım</div>
    </div>
  </div>
</div>

<!-- Manuel Toplu Yoklama -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">📋 Manuel Toplu Yoklama</span></div>
  <div class="card-body">
    <form method="post">
      <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap;align-items:center">
        <input type="date" name="att_date" class="form-control" value="<?= sanitize($date) ?>" style="max-width:160px">
        <button type="button" onclick="document.querySelectorAll('.scb:not(:disabled)').forEach(c=>c.checked=true)" class="btn btn-sm btn-secondary">Tümünü Seç</button>
        <button type="button" onclick="document.querySelectorAll('.scb:not(:disabled)').forEach(c=>c.checked=false)" class="btn btn-sm btn-secondary">Temizle</button>
        <button name="bulk_attendance" class="btn btn-primary btn-sm">✅ Yoklamaya Ekle</button>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px;max-height:300px;overflow-y:auto">
        <?php foreach ($allStudents as $s):
          $already = in_array($s['id'], $todayIds);
        ?>
        <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;cursor:pointer;border:1.5px solid <?= $already?'#86efac':'#e2e8f0' ?>;background:<?= $already?'#f0fdf4':'#fff' ?>">
          <input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" class="scb" <?= $already?'disabled checked':'' ?> style="accent-color:#6d28d9">
          <div>
            <div style="font-size:13px;font-weight:600"><?= sanitize($s['name'].' '.$s['surname']) ?></div>
            <div style="font-size:11px;color:#94a3b8"><?= $s['gender']==='male'?'👦':'👧' ?> <?= $s['age']?$s['age'].' yaş':'' ?> <?= $already?'· <span style="color:#16a34a">✅</span>':'' ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </form>
  </div>
</div>

<!-- Liste -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Yoklama Listesi</span>
    <form method="get" style="display:flex;gap:8px">
      <input type="date" name="date" class="form-control" value="<?= sanitize($date) ?>" style="max-width:160px">
      <button class="btn btn-sm btn-primary">Filtrele</button>
    </form>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Saat</th><th>Öğrenci</th><th>Yaş</th><th>QR Kod</th></tr></thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td style="font-weight:700;color:#6d28d9"><?= substr($r['scan_time'],0,5) ?></td>
          <td><strong><?= sanitize($r['name'].' '.$r['surname']) ?></strong><br>
            <span class="badge <?= $r['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:10px"><?= $r['gender']==='male'?'👦':'👧' ?></span></td>
          <td><?= $r['age']?$r['age'].' yaş':'—' ?></td>
          <td><code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= sanitize($r['qr_code']) ?></code></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($records)): ?><tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📋</div><div class="empty-state-title">Yoklama yok</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Kamera Modal -->
<div class="modal-overlay" id="cameraModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><span class="modal-title">📷 QR Tara</span><button class="modal-close" onclick="stopCamera()">✕</button></div>
    <div class="modal-body" style="padding:16px">
      <div style="position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1">
        <video id="cameraVideo" style="width:100%;height:100%;object-fit:cover" playsinline autoplay muted></video>
        <canvas id="cameraCanvas" style="display:none"></canvas>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none">
          <div style="width:65%;aspect-ratio:1;border:3px solid #c9a227;border-radius:12px;box-shadow:0 0 0 4000px rgba(0,0,0,.4)"></div>
        </div>
        <div id="cameraStatus" style="position:absolute;bottom:12px;left:0;right:0;text-align:center;color:#fff;font-size:13px;font-weight:600">QR kodu çerçeve içine alın...</div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let cs=null,si=null,lc='',cc=0;
function openCamera(){document.getElementById('cameraModal').classList.add('show');document.body.style.overflow='hidden';navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}}}).then(s=>{cs=s;document.getElementById('cameraVideo').srcObject=s;document.getElementById('cameraVideo').play();startScanning();}).catch(e=>{document.getElementById('cameraStatus').textContent='❌ '+e.message;});}
function startScanning(){const v=document.getElementById('cameraVideo'),c=document.getElementById('cameraCanvas'),ctx=c.getContext('2d');si=setInterval(()=>{if(v.readyState!==v.HAVE_ENOUGH_DATA)return;c.width=v.videoWidth;c.height=v.videoHeight;ctx.drawImage(v,0,0,c.width,c.height);const code=jsQR(ctx.getImageData(0,0,c.width,c.height).data,c.width,c.height);if(code){const val=code.data.trim().toUpperCase();if(val===lc)cc++;else{lc=val;cc=1;}if(cc>=2){stopCamera();document.getElementById('qrInput').value=val;const h=document.createElement('input');h.type='hidden';h.name='scan_qr';h.value='1';document.getElementById('attendanceForm').appendChild(h);document.getElementById('attendanceForm').submit();}}},150);}
function stopCamera(){if(si){clearInterval(si);si=null;}if(cs){cs.getTracks().forEach(t=>t.stop());cs=null;}lc='';cc=0;document.getElementById('cameraModal').classList.remove('show');document.body.style.overflow='';}
document.getElementById('cameraModal').addEventListener('click',e=>{if(e.target===document.getElementById('cameraModal'))stopCamera();});
</script>
<?php endif; ?>
<?php include 'layout/footer.php'; ?>
