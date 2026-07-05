<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

// ─── QR Tarama ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_qr'])) {
    $code = trim(strtoupper($_POST['qr_code'] ?? ''));
    if ($code) {
        $stmt = $db->prepare("SELECT s.*, p.name AS p_name, p.surname AS p_surname FROM students s LEFT JOIN parents p ON s.parent_id=p.id WHERE s.qr_code=? AND s.mosque_id=? AND s.status='active'");
        $stmt->execute([$code, $mid]);
        $student = $stmt->fetch();
        if ($student) {
            $chk = $db->prepare("SELECT id FROM attendance WHERE student_id=? AND mosque_id=? AND scan_date=CURDATE()");
            $chk->execute([$student['id'], $mid]);
            if ($chk->fetch()) {
                $success = '⚠️ ' . sanitize($student['name'].' '.$student['surname']) . ' bugün zaten yoklamaya işaretlendi.';
            } else {
                $db->prepare("INSERT INTO attendance (student_id,mosque_id,scan_date,scan_time) VALUES (?,?,CURDATE(),CURTIME())")->execute([$student['id'], $mid]);
                $veli = $student['p_name'] ? sanitize($student['p_name'].' '.$student['p_surname']) : '(Veli yok)';
                $success = '✅ ' . sanitize($student['name'].' '.$student['surname']) . ' yoklamaya eklendi! Veli: ' . $veli;
            }
        } else {
            $error = 'QR kod bulunamadı veya öğrenci bu camiye kayıtlı değil.';
        }
    }
}

// ─── Manuel Toplu Yoklama ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_attendance'])) {
    $selected = $_POST['student_ids'] ?? [];
    $raw_date = $_POST['att_date'] ?? date('Y-m-d');
    // Tarih formatını doğrula (XSS ve geçersiz değer önleme)
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_date) ? $raw_date : date('Y-m-d');
    $count    = 0;
    foreach ($selected as $sid) {
        $sid = (int)$sid;
        // Bu camiye ait ve aktif mi?
        $chk = $db->prepare("SELECT id FROM students WHERE id=? AND mosque_id=? AND status='active'");
        $chk->execute([$sid, $mid]);
        if (!$chk->fetch()) continue;
        // Aynı gün zaten ekli mi?
        $dup = $db->prepare("SELECT id FROM attendance WHERE student_id=? AND mosque_id=? AND scan_date=?");
        $dup->execute([$sid, $mid, $date]);
        if ($dup->fetch()) continue;
        $db->prepare("INSERT INTO attendance (student_id,mosque_id,scan_date,scan_time) VALUES (?,?,?,?)")
           ->execute([$sid, $mid, $date, date('H:i:s')]);
        $count++;
    }
    $success = "✅ $count öğrenci " . sanitize($date) . " tarihinde yoklamaya eklendi.";
}

// ─── Yoklama Sil ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_att'])) {
    $db->prepare("DELETE FROM attendance WHERE id=? AND mosque_id=?")->execute([(int)$_POST['att_id'], $mid]);
    $success = 'Yoklama kaydı silindi.';
}

// ─── Tarih filtresi ───────────────────────────────────────
$date = $_GET['date'] ?? date('Y-m-d');

$records = $db->prepare("
    SELECT a.*, s.name, s.surname, s.gender, s.qr_code, s.age,
           p.name AS p_name, p.surname AS p_surname
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE a.mosque_id=? AND a.scan_date=?
    ORDER BY a.scan_time DESC
");
$records->execute([$mid, $date]);
$records = $records->fetchAll();

$todayCount = $db->prepare("SELECT COUNT(*) FROM attendance WHERE mosque_id=? AND scan_date=CURDATE()");
$todayCount->execute([$mid]);
$todayCount = $todayCount->fetchColumn();

$totalStudents = $db->prepare("SELECT COUNT(*) FROM students WHERE mosque_id=? AND status='active'");
$totalStudents->execute([$mid]);
$totalStudents = $totalStudents->fetchColumn();

// Tüm aktif öğrenciler (manuel yoklama için)
$allStudents = $db->prepare("SELECT id, name, surname, age, gender FROM students WHERE mosque_id=? AND status='active' ORDER BY name");
$allStudents->execute([$mid]);
$allStudents = $allStudents->fetchAll();

// Bugün yoklamada olanlar
$todayIds = $db->prepare("SELECT student_id FROM attendance WHERE mosque_id=? AND scan_date=?");
$todayIds->execute([$mid, $date]);
$todayIds = array_column($todayIds->fetchAll(), 'student_id');

$page_title = 'Yoklama';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<!-- Üst Kartlar -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <!-- QR Tarama -->
  <div class="card">
    <div class="card-header"><span class="card-title">📷 QR Kod ile Yoklama</span></div>
    <div class="card-body">
      <form method="post" id="attendanceForm">
        <div class="form-group">
          <label class="form-label">QR Kod / Öğrenci Kodu</label>
          <input type="text" name="qr_code" id="qrInput" class="form-control" placeholder="QR kodu girin veya kamerayı kullanın..." autofocus style="font-family:monospace;font-size:16px;text-transform:uppercase" required>
        </div>
        <div style="display:flex;gap:10px">
          <button name="scan_qr" class="btn btn-primary btn-lg" style="flex:1">✅ Yoklamaya Ekle</button>
          <button type="button" onclick="openCamera()" class="btn btn-gold btn-lg" title="Kamerayı Aç">📷</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bugün özet -->
  <div class="card">
    <div class="card-header"><span class="card-title">📊 Bugün Özeti</span></div>
    <div class="card-body" style="text-align:center;padding:24px">
      <div style="font-size:56px;font-weight:800;color:#1a7a3a"><?= $todayCount ?></div>
      <div style="font-size:15px;color:#64748b">öğrenci bugün geldi</div>
      <div style="margin-top:12px;padding:12px;background:#f1f5f9;border-radius:8px">
        <div style="font-size:13px;color:#64748b">Toplam Aktif: <strong><?= $totalStudents ?></strong></div>
        <?php $pct = $totalStudents > 0 ? round($todayCount/$totalStudents*100) : 0; ?>
        <div style="background:#e2e8f0;border-radius:999px;height:10px;margin-top:8px">
          <div style="background:linear-gradient(90deg,#1a7a3a,#2ea855);height:10px;border-radius:999px;width:<?= $pct ?>%"></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:#1a7a3a;margin-top:6px">%<?= $pct ?> katılım</div>
      </div>
    </div>
  </div>
</div>

<!-- Manuel Toplu Yoklama -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title">📋 Manuel Toplu Yoklama</span>
    <span style="font-size:13px;color:#94a3b8">Listeden öğrenci seçerek toplu işaret edin</span>
  </div>
  <div class="card-body">
    <form method="post" id="bulkForm">
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
        <label class="form-label" style="margin:0">Tarih:</label>
        <input type="date" name="att_date" class="form-control" value="<?= sanitize($date) ?>" style="max-width:160px">
        <button type="button" onclick="selectAll()" class="btn btn-sm btn-secondary">Tümünü Seç</button>
        <button type="button" onclick="clearAll()" class="btn btn-sm btn-secondary">Temizle</button>
        <button name="bulk_attendance" class="btn btn-primary btn-sm">✅ Seçilileri Yoklamaya Ekle</button>
      </div>

      <?php if (empty($allStudents)): ?>
        <div class="alert alert-info">Henüz aktif öğrenci yok. <a href="add_student.php">Öğrenci ekleyin →</a></div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;max-height:320px;overflow-y:auto;padding:4px">
        <?php foreach ($allStudents as $s):
          $already = in_array($s['id'], $todayIds);
        ?>
        <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;cursor:pointer;border:1.5px solid <?= $already ? '#86efac' : '#e2e8f0' ?>;background:<?= $already ? '#f0fdf4' : '#fff' ?>;user-select:none">
          <input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" class="student-cb" <?= $already ? 'disabled checked' : '' ?> style="width:16px;height:16px;accent-color:#1a7a3a">
          <div>
            <div style="font-size:13px;font-weight:600"><?= sanitize($s['name'].' '.$s['surname']) ?></div>
            <div style="font-size:11px;color:#94a3b8">
              <?= $s['gender']==='male'?'👦':'👧' ?>
              <?= $s['age'] ? $s['age'].' yaş' : '' ?>
              <?= $already ? ' · <span style="color:#16a34a">✅ Geldi</span>' : '' ?>
            </div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </form>
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
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Saat</th><th>Öğrenci</th><th>Yaş</th><th>Veli</th><th>QR Kod</th><th class="no-print">İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td style="font-weight:700;color:#1a7a3a"><?= substr($r['scan_time'],0,5) ?></td>
          <td>
            <strong><?= sanitize($r['name'].' '.$r['surname']) ?></strong><br>
            <span class="badge <?= $r['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:11px"><?= $r['gender']==='male'?'👦 Erkek':'👧 Kız' ?></span>
          </td>
          <td><?= $r['age'] ? $r['age'].' yaş' : '—' ?></td>
          <td><?= $r['p_name'] ? sanitize($r['p_name'].' '.$r['p_surname']) : '<span style="color:#94a3b8">—</span>' ?></td>
          <td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= sanitize($r['qr_code']) ?></code></td>
          <td class="no-print">
            <form method="post" style="display:inline">
              <input type="hidden" name="att_id" value="<?= $r['id'] ?>">
              <button name="delete_att" class="btn btn-sm btn-danger" onclick="return confirm('Bu yoklama kaydını silmek istediğinizden emin misiniz?')" title="Sil">🗑️</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($records)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div class="empty-state-title"><?= $date === date('Y-m-d') ? 'Bugün henüz yoklama alınmadı' : 'Bu tarihe ait kayıt yok' ?></div>
            <div class="empty-state-desc">QR tarayıcı veya manuel liste kullanın</div>
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

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let cameraStream = null, scanInterval = null, lastCode = '', codeConfirmCount = 0;

function selectAll() {
  document.querySelectorAll('.student-cb:not(:disabled)').forEach(cb => cb.checked = true);
}
function clearAll() {
  document.querySelectorAll('.student-cb:not(:disabled)').forEach(cb => cb.checked = false);
}

function openCamera() {
  document.getElementById('cameraModal').classList.add('show');
  document.body.style.overflow = 'hidden';
  const video = document.getElementById('cameraVideo');
  const status = document.getElementById('cameraStatus');
  status.textContent = 'Kamera başlatılıyor...';
  navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } })
    .then(stream => {
      cameraStream = stream; video.srcObject = stream; video.play();
      status.textContent = 'QR kodu çerçeve içine alın...';
      startScanning();
    }).catch(err => { status.textContent = '❌ Kamera açılamadı: ' + err.message; });
}

function startScanning() {
  const video = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  const ctx = canvas.getContext('2d');
  const status = document.getElementById('cameraStatus');
  const detected = document.getElementById('detectedCode');
  scanInterval = setInterval(() => {
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
    if (code) {
      const val = code.data.trim().toUpperCase();
      if (val === lastCode) { codeConfirmCount++; } else { lastCode = val; codeConfirmCount = 1; }
      if (codeConfirmCount >= 2) {
        stopCamera();
        document.getElementById('qrInput').value = val;
        detected.textContent = '✅ Algılandı: ' + val;
        detected.style.display = 'block';
        setTimeout(() => {
          const h = document.createElement('input');
          h.type='hidden'; h.name='scan_qr'; h.value='1';
          document.getElementById('attendanceForm').appendChild(h);
          document.getElementById('attendanceForm').submit();
        }, 600);
      } else { status.textContent = '🔍 Bulunan: ' + val; }
    } else { if (lastCode) { codeConfirmCount = 0; lastCode = ''; } }
  }, 150);
}

function stopCamera() {
  if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
  if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
  lastCode = ''; codeConfirmCount = 0;
  document.getElementById('cameraModal').classList.remove('show');
  document.body.style.overflow = '';
  document.getElementById('detectedCode').style.display = 'none';
}

document.getElementById('cameraModal').addEventListener('click', e => { if (e.target === this) stopCamera(); });
</script>

<?php include 'layout/footer.php'; ?>
