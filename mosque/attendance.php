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
      <form method="post">
        <div class="form-group">
          <label class="form-label">QR Kod / Öğrenci Kodu</label>
          <input type="text" name="qr_code" class="form-control" placeholder="QR kodu buraya girin veya okutun..." autofocus style="font-family:monospace;font-size:16px;text-transform:uppercase" required>
        </div>
        <button name="scan_qr" class="btn btn-primary btn-block btn-lg">✅ Yoklamaya Ekle</button>
      </form>
      <div class="alert alert-info" style="margin-top:16px">
        ℹ️ Öğrenci kimlik kartındaki QR kodu okuyucuya tutun veya kodu elle girin.
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

<?php include 'layout/footer.php'; ?>
