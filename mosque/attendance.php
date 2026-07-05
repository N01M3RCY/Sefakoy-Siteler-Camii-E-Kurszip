<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

// QR Tarama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_qr'])) {
    $code = trim(strtoupper($_POST['qr_code'] ?? ''));
    if ($code) {
        $stmt = $db->prepare("SELECT s.*, p.name AS p_name, p.surname AS p_surname FROM students s JOIN parents p ON s.parent_id=p.id WHERE s.qr_code=? AND s.mosque_id=? AND s.status='active'");
        $stmt->execute([$code, $mid]);
        $student = $stmt->fetch();
        if ($student) {
            // Bugün zaten kaydedilmiş mi?
            $chk = $db->prepare("SELECT id FROM attendance WHERE student_id=? AND mosque_id=? AND scan_date=CURDATE()");
            $chk->execute([$student['id'], $mid]);
            if ($chk->fetch()) {
                $success = '⚠️ ' . sanitize($student['name'].' '.$student['surname']) . ' bugün zaten yoklamaya işaretlendi.';
            } else {
                $db->prepare("INSERT INTO attendance (student_id,mosque_id,scan_date,scan_time) VALUES (?,?,CURDATE(),CURTIME())")->execute([$student['id'], $mid]);
                $success = '✅ ' . sanitize($student['name'].' '.$student['surname']) . ' yoklamaya eklendi! (Veli: ' . sanitize($student['p_name'].' '.$student['p_surname']) . ')';
            }
        } else {
            $error = 'QR kod bulunamadı veya öğrenci bu camiye kayıtlı değil.';
        }
    }
}

// Tarih filtresi
$date = $_GET['date'] ?? date('Y-m-d');

$records = $db->prepare("
    SELECT a.*, s.name, s.surname, s.gender, s.qr_code, p.name AS p_name, p.surname AS p_surname
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN parents p ON s.parent_id = p.id
    WHERE a.mosque_id=? AND a.scan_date=?
    ORDER BY a.scan_time DESC
");
$records->execute([$mid, $date]);
$records = $records->fetchAll();

// Bugün gerçek sayısı (tarih filtresinden bağımsız her zaman CURDATE)
$todayCount = $db->prepare("SELECT COUNT(*) FROM attendance WHERE mosque_id=? AND scan_date=CURDATE()");
$todayCount->execute([$mid]);
$todayCount = $todayCount->fetchColumn();

// Toplam öğrenci sayısı
$totalStudents = $db->prepare("SELECT COUNT(*) FROM students WHERE mosque_id=? AND status='active'");
$totalStudents->execute([$mid]);
$totalStudents = $totalStudents->fetchColumn();

$page_title = 'Yoklama';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
  <!-- QR Tarama -->
  <div class="card">
    <div class="card-header"><span class="card-title">📷 QR Kod Tara</span></div>
    <div class="card-body">
      <form method="post" id="attendanceForm">
        <div class="form-group">
          <label class="form-label">QR Kod / Öğrenci Kodu</label>
          <input type="text" name="qr_code" id="qrInput" class="form-control" placeholder="QR kodu girin veya kamerayı kullanın..." autofocus style="font-family:monospace;font-size:16px;text-transform:uppercase" required>
        </div>
        <div style="display:flex;gap:10px">
          <button name="scan_qr" class="btn btn-primary btn-lg" style="flex:1">✅ Yoklamaya Ekle</button>
          <button type="button" onclick="openCamera()" class="btn btn-gold btn-lg" title="Kamerayı Aç">📷 Kamerayla Tara</button>
        </div>
      </form>
      <div class="alert alert-info" style="margin-top:16px">
        ℹ️ QR okuyucu (barkod tarayıcı) veya kamera butonu ile tarayın, ya da kodu elle girin.
      </div>
    </div>
  </div>

  <!-- Bugün özet -->
  <div class="card">
    <div class="card-header"><span class="card-title">📊 Bugün Özeti</span></div>
    <div class="card-body" style="text-align:center;padding:32px">
      <div style="font-size:64px;font-weight:800;color:#1a7a3a"><?= $todayCount ?></div>
      <div style="font-size:16px;color:#64748b">öğrenci bugün geldi</div>
      <div style="margin-top:12px;padding:12px;background:#f1f5f9;border-radius:8px">
        <div style="font-size:13px;color:#64748b">Toplam Aktif Öğrenci: <strong><?= $totalStudents ?></strong></div>
        <?php $pct = $totalStudents > 0 ? round($todayCount/$totalStudents*100) : 0; ?>
        <div style="background:#e2e8f0;border-radius:999px;height:8px;margin-top:8px">
          <div style="background:#1a7a3a;height:8px;border-radius:999px;width:<?= $pct ?>%"></div>
        </div>
        <div style="font-size:12px;color:#94a3b8;margin-top:4px">%<?= $pct ?> katılım</div>
      </div>
    </div>
  </div>
</div>

<!-- Yoklama Listesi -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Yoklama Listesi</span>
    <form method="get" style="display:flex;gap:8px;align-items:center" class="no-print">
      <input type="date" name="date" class="form-control" value="<?= sanitize($date) ?>" style="max-width:160px">
      <button class="btn btn-sm btn-primary">Filtrele</button>
    </form>
    <button onclick="window.print()" class="btn btn-sm btn-secondary no-print">🖨️</button>
    <a href="export.php?type=attendance&format=xls&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-secondary no-print">📥 Excel</a>
    <a href="export.php?type=attendance&format=csv&from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-secondary no-print">📄 CSV</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Saat</th><th>Öğrenci</th><th>Veli</th><th>QR Kod</th></tr></thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td style="font-weight:700;color:#1a7a3a"><?= substr($r['scan_time'],0,5) ?></td>
          <td>
            <strong><?= sanitize($r['name'].' '.$r['surname']) ?></strong><br>
            <span class="badge <?= $r['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:11px"><?= $r['gender']==='male'?'Erkek':'Kız' ?></span>
          </td>
          <td><?= sanitize($r['p_name'].' '.$r['p_surname']) ?></td>
          <td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= sanitize($r['qr_code']) ?></code></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($records)): ?>
        <tr><td colspan="4">
          <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div class="empty-state-title"><?= $date === date('Y-m-d') ? 'Bugün henüz yoklama alınmadı' : 'Bu tarihe ait kayıt yok' ?></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Kamera QR Modal -->
<div class="modal-overlay" id="cameraModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title">📷 QR Kodu Tara</span>
      <button class="modal-close" onclick="stopCamera()">✕</button>
    </div>
    <div class="modal-body" style="padding:16px">
      <div style="position:relative;background:#000;border-radius:10px;overflow:hidden;aspect-ratio:1">
        <video id="cameraVideo" style="width:100%;height:100%;object-fit:cover" playsinline autoplay muted></video>
        <canvas id="cameraCanvas" style="display:none"></canvas>
        <!-- İsabet çizgisi -->
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none">
          <div style="width:65%;aspect-ratio:1;border:3px solid #c9a227;border-radius:12px;box-shadow:0 0 0 4000px rgba(0,0,0,.4)"></div>
        </div>
        <div id="cameraStatus" style="position:absolute;bottom:12px;left:0;right:0;text-align:center;color:#fff;font-size:13px;font-weight:600;text-shadow:0 1px 4px rgba(0,0,0,.8)">
          QR kodu çerçeve içine alın...
        </div>
      </div>
      <div id="detectedCode" style="display:none;margin-top:12px;text-align:center;padding:12px;background:#dcfce7;border-radius:8px;font-family:monospace;font-weight:700;color:#15803d;font-size:16px"></div>
    </div>
  </div>
</div>

<!-- jsQR kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let cameraStream = null;
let scanInterval  = null;
let lastCode      = '';
let codeConfirmCount = 0;

function openCamera() {
  const modal = document.getElementById('cameraModal');
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';

  const video = document.getElementById('cameraVideo');
  const status = document.getElementById('cameraStatus');
  status.textContent = 'Kamera başlatılıyor...';

  const constraints = {
    video: {
      facingMode: { ideal: 'environment' }, // arka kamera
      width:  { ideal: 1280 },
      height: { ideal: 720 }
    }
  };

  navigator.mediaDevices.getUserMedia(constraints)
    .then(function(stream) {
      cameraStream = stream;
      video.srcObject = stream;
      video.play();
      status.textContent = 'QR kodu çerçeve içine alın...';
      startScanning();
    })
    .catch(function(err) {
      status.textContent = '❌ Kamera açılamadı: ' + err.message;
      console.error('Camera error:', err);
    });
}

function startScanning() {
  const video    = document.getElementById('cameraVideo');
  const canvas   = document.getElementById('cameraCanvas');
  const ctx      = canvas.getContext('2d');
  const status   = document.getElementById('cameraStatus');
  const detected = document.getElementById('detectedCode');

  scanInterval = setInterval(function() {
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height, {
      inversionAttempts: 'dontInvert'
    });

    if (code) {
      const val = code.data.trim().toUpperCase();
      if (val === lastCode) {
        codeConfirmCount++;
      } else {
        lastCode = val;
        codeConfirmCount = 1;
      }

      // 2 kez aynı kodu görünce onayla
      if (codeConfirmCount >= 2) {
        stopCamera();
        document.getElementById('qrInput').value = val;
        detected.textContent = '✅ Algılandı: ' + val;
        detected.style.display = 'block';

        // Hidden input ekleyerek submit et (button olmadan scan_qr geçmez)
        setTimeout(function() {
          const h = document.createElement('input');
          h.type = 'hidden'; h.name = 'scan_qr'; h.value = '1';
          document.getElementById('attendanceForm').appendChild(h);
          document.getElementById('attendanceForm').submit();
        }, 600);
      } else {
        status.textContent = '🔍 Bulunan: ' + val;
      }
    } else {
      if (lastCode) {
        codeConfirmCount = 0;
        lastCode = '';
      }
    }
  }, 150);
}

function stopCamera() {
  if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
  if (cameraStream) {
    cameraStream.getTracks().forEach(t => t.stop());
    cameraStream = null;
  }
  lastCode = '';
  codeConfirmCount = 0;
  const modal = document.getElementById('cameraModal');
  modal.classList.remove('show');
  document.body.style.overflow = '';
  document.getElementById('detectedCode').style.display = 'none';
}

// Modal kapatılırsa kamerayı durdur
document.getElementById('cameraModal').addEventListener('click', function(e) {
  if (e.target === this) stopCamera();
});
</script>

<?php include 'layout/footer.php'; ?>
