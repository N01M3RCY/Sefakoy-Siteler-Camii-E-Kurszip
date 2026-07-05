<?php
require_once '../config/db.php';
requireLogin('parent', 'login.php');
$db  = getDB();
$pid = $_SESSION['parent_id'];

$students = $db->prepare("
    SELECT s.*, m.name AS mosque_name, m.district, m.city, m.imam_name, m.phone AS mosque_phone, m.address AS mosque_address
    FROM students s JOIN mosques m ON s.mosque_id=m.id
    WHERE s.parent_id=? ORDER BY s.name
");
$students->execute([$pid]);
$students = $students->fetchAll();

$page_title = 'Öğrenci Bilgileri';
include 'layout/header.php';
?>

<?php foreach ($students as $s): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <span class="card-title">📚 <?= sanitize($s['name'].' '.$s['surname']) ?></span>
    <div style="display:flex;gap:8px">
      <span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>"><?= $s['status']==='active'?'Aktif':'Pasif' ?></span>
      <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-primary">🪪 Kimlik Kartı</a>
      <button onclick="window.print()" class="btn btn-sm btn-secondary no-print">🖨️</button>
    </div>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:auto 1fr;gap:28px;align-items:start">
      <div style="text-align:center">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode($s['qr_code']) ?>&color=0d5c2e"
             width="150" height="150" style="border-radius:12px;border:3px solid #e8f5ee;display:block">
        <div style="font-family:monospace;font-size:11px;color:#94a3b8;margin-top:8px"><?= sanitize($s['qr_code']) ?></div>
        <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-primary" style="margin-top:10px;display:inline-flex">🖨️ Yazdır</a>
      </div>
      <div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div>
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px">Ad Soyad</div>
            <div style="font-weight:700"><?= sanitize($s['name'].' '.$s['surname']) ?></div>
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px">Cinsiyet</div>
            <div><?= $s['gender']==='male'?'👦 Erkek':'👧 Kız' ?></div>
          </div>
          <?php if ($s['birth_date']): ?>
          <div>
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px">Doğum Tarihi</div>
            <div><?= date('d.m.Y',strtotime($s['birth_date'])) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($s['tc_no']): ?>
          <div>
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px">TC No</div>
            <div><?= substr(sanitize($s['tc_no']),0,3).'****'.substr(sanitize($s['tc_no']),-2) ?></div>
          </div>
          <?php endif; ?>
          <div style="grid-column:1/-1">
            <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:4px">Kayıt Tarihi</div>
            <div><?= date('d.m.Y',strtotime($s['created_at'])) ?></div>
          </div>
        </div>

        <div style="background:#f0f7f0;border-radius:10px;padding:16px;margin-top:16px">
          <div style="font-size:12px;font-weight:700;color:#0d5c2e;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">🕌 Kayıtlı Cami</div>
          <div style="font-size:16px;font-weight:700"><?= sanitize($s['mosque_name']) ?></div>
          <?php if ($s['district']): ?><div style="font-size:13px;color:#64748b;margin-top:4px">📍 <?= sanitize($s['district'].($s['city']?' / '.$s['city']:'')) ?></div><?php endif; ?>
          <?php if ($s['mosque_address']): ?><div style="font-size:13px;color:#64748b">🏠 <?= sanitize($s['mosque_address']) ?></div><?php endif; ?>
          <?php if ($s['imam_name']): ?><div style="font-size:13px;color:#64748b">👤 İmam: <?= sanitize($s['imam_name']) ?></div><?php endif; ?>
          <?php if ($s['mosque_phone']): ?><div style="font-size:13px;color:#64748b">📞 <a href="tel:<?= sanitize($s['mosque_phone']) ?>"><?= sanitize($s['mosque_phone']) ?></a></div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($students)): ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">📚</div>
  <div class="empty-state-title">Kayıtlı öğrenci yok</div>
  <div class="empty-state-desc"><a href="../register.php" style="color:#1a7a3a">Kayıt formuna git →</a></div>
</div></div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
