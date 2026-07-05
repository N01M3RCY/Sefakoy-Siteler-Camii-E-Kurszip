<?php
require_once '../config/db.php';
requireLogin('parent', 'login.php');
$db  = getDB();
$pid = $_SESSION['parent_id'];

// Bu velinin öğrencileri
$students = $db->prepare("
    SELECT s.*, m.name AS mosque_name, m.district, m.city, m.imam_name, m.phone AS mosque_phone
    FROM students s JOIN mosques m ON s.mosque_id=m.id
    WHERE s.parent_id=? ORDER BY s.name
");
$students->execute([$pid]);
$students = $students->fetchAll();

// Bu ayki devam özeti (her öğrenci)
$thisMonth = date('Y-m');
$attendance = [];
foreach ($students as $s) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND DATE_FORMAT(scan_date,'%Y-%m')=?");
    $stmt->execute([$s['id'], $thisMonth]);
    $attendance[$s['id']] = $stmt->fetchColumn();
}

// Son yoklamalar
$recentAttendance = $db->prepare("
    SELECT a.*, s.name AS s_name, s.surname AS s_surname, m.name AS mosque_name
    FROM attendance a JOIN students s ON a.student_id=s.id JOIN mosques m ON a.mosque_id=m.id
    WHERE s.parent_id=? ORDER BY a.scan_date DESC, a.scan_time DESC LIMIT 10
");
$recentAttendance->execute([$pid]);
$recentAttendance = $recentAttendance->fetchAll();

$page_title = 'Kontrol Paneli';
include 'layout/header.php';
?>

<div style="margin-bottom:24px;background:linear-gradient(135deg,#78350f,#c9a227);border-radius:16px;padding:24px 28px;color:#fff;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
  <div style="font-size:52px">👨‍👩‍👧</div>
  <div>
    <div style="font-size:20px;font-weight:800"><?= sanitize($_SESSION['parent_name'].' '.$_SESSION['parent_surname']) ?></div>
    <div style="opacity:.8;font-size:13px;margin-top:4px"><?= count($students) ?> kayıtlı öğrenci · <?= date('F Y') ?></div>
  </div>
</div>

<!-- Öğrenci Kartları -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:28px">
  <?php foreach ($students as $s): ?>
  <div class="card" style="border-top:4px solid <?= $s['status']==='active'?'#1a7a3a':'#dc2626' ?>">
    <div class="card-body">
      <div style="display:flex;gap:16px;align-items:flex-start">
        <div style="flex-shrink:0">
          <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($s['qr_code']) ?>&color=0d5c2e"
                 width="72" height="72" style="border-radius:8px;border:2px solid #e8f5ee" alt="QR">
          </a>
        </div>
        <div style="flex:1">
          <div style="font-size:18px;font-weight:800;color:#0d5c2e"><?= sanitize($s['name'].' '.$s['surname']) ?></div>
          <div style="font-size:13px;color:#64748b;margin-top:4px">
            <?= $s['gender']==='male'?'👦 Erkek':'👧 Kız' ?>
            <?= $s['birth_date'] ? ' · '.date('d.m.Y',strtotime($s['birth_date'])) : '' ?>
          </div>
          <span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>" style="margin-top:6px">
            <?= $s['status']==='active'?'✅ Aktif':'❌ Pasif' ?>
          </span>
        </div>
      </div>

      <div style="background:#f0f7f0;border-radius:8px;padding:12px;margin-top:16px">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">🕌 Kayıtlı Cami</div>
        <div style="font-weight:700;color:#0d5c2e"><?= sanitize($s['mosque_name']) ?></div>
        <?php if ($s['district']): ?><div style="font-size:12px;color:#64748b">📍 <?= sanitize($s['district'].($s['city']?' / '.$s['city']:'')) ?></div><?php endif; ?>
        <?php if ($s['imam_name']): ?><div style="font-size:12px;color:#64748b">👤 <?= sanitize($s['imam_name']) ?></div><?php endif; ?>
        <?php if ($s['mosque_phone']): ?><div style="font-size:12px;color:#64748b">📞 <?= sanitize($s['mosque_phone']) ?></div><?php endif; ?>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0">
        <div>
          <div style="font-size:24px;font-weight:800;color:#c9a227"><?= $attendance[$s['id']] ?></div>
          <div style="font-size:11px;color:#64748b">bu ay devam</div>
        </div>
        <div style="display:flex;gap:6px">
          <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-primary">🪪 Kimlik</a>
          <a href="attendance.php?student=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">📅 Devam</a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($students)): ?>
  <div class="card" style="grid-column:1/-1">
    <div class="empty-state">
      <div class="empty-state-icon">📚</div>
      <div class="empty-state-title">Henüz kayıtlı öğrenci yok</div>
      <div class="empty-state-desc"><a href="../register.php" style="color:#1a7a3a">Kayıt formuna giderek</a> çocuğunuzu kaydedebilirsiniz.</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Son Yoklamalar -->
<?php if (!empty($recentAttendance)): ?>
<div class="card">
  <div class="card-header"><span class="card-title">📅 Son Yoklamalar</span></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Tarih</th><th>Saat</th><th>Öğrenci</th><th>Cami</th></tr></thead>
      <tbody>
        <?php foreach ($recentAttendance as $a): ?>
        <tr>
          <td><strong><?= date('d.m.Y', strtotime($a['scan_date'])) ?></strong></td>
          <td><?= substr($a['scan_time'],0,5) ?></td>
          <td><?= sanitize($a['s_name'].' '.$a['s_surname']) ?></td>
          <td><?= sanitize($a['mosque_name']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
