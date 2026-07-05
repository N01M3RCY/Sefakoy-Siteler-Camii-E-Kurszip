<?php
require_once 'config/db.php';
$db = getDB();

$code = strtoupper(trim($_GET['code'] ?? ''));
if (!$code) {
    http_response_code(404);
    die('<p style="font-family:sans-serif;padding:20px;color:red">QR kod belirtilmedi.</p>');
}

$stmt = $db->prepare("
    SELECT s.*, m.name AS mosque_name, m.district, m.city, m.imam_name, m.phone AS mosque_phone,
           p.name AS p_name, p.surname AS p_surname, p.phone AS p_phone
    FROM students s
    JOIN mosques m ON s.mosque_id = m.id
    JOIN parents p ON s.parent_id = p.id
    WHERE s.qr_code = ?
");
$stmt->execute([$code]);
$student = $stmt->fetch();

if (!$student) {
    http_response_code(404);
    die('<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Bulunamadı</title><link rel="stylesheet" href="assets/css/style.css"></head><body><div style="display:flex;align-items:center;justify-content:center;min-height:100vh"><div class="empty-state"><div class="empty-state-icon">❌</div><div class="empty-state-title">Öğrenci Bulunamadı</div><div class="empty-state-desc">Bu QR koda ait kayıt bulunamadı.</div><a href="index.php" style="display:inline-block;margin-top:16px;color:#1a7a3a">← Ana Sayfaya Dön</a></div></div></body></html>');
}

$age = $student['birth_date']
    ? floor((time() - strtotime($student['birth_date'])) / (365.25 * 24 * 3600)) . ' yaş'
    : '—';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Öğrenci Kimlik Kartı · <?= sanitize($student['name'].' '.$student['surname']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  body { background: #f0f7f0; }
  .id-card {
    max-width: 420px;
    margin: 40px auto;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.12);
    font-family: 'Segoe UI', sans-serif;
  }
  .id-card-header {
    background: linear-gradient(135deg, #0d5c2e, #1a7a3a);
    padding: 24px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .id-card-logo { font-size: 40px; }
  .id-card-org { font-size: 11px; opacity: .7; text-transform: uppercase; letter-spacing: 1px; }
  .id-card-title { font-size: 16px; font-weight: 800; }
  .id-card-body { padding: 24px; display: flex; gap: 20px; align-items: flex-start; }
  .id-card-qr { flex-shrink: 0; }
  .id-card-qr img { border-radius: 10px; border: 3px solid #e8f5ee; display: block; }
  .id-card-info { flex: 1; }
  .id-name { font-size: 20px; font-weight: 800; color: #0d5c2e; margin-bottom: 4px; }
  .id-row { display: flex; gap: 8px; margin-bottom: 8px; font-size: 13px; }
  .id-label { color: #94a3b8; font-weight: 600; min-width: 70px; }
  .id-value { color: #334155; font-weight: 500; }
  .id-card-footer {
    background: #f8fafc;
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    color: #94a3b8;
  }
  .id-card-footer strong { color: #0d5c2e; }
  .id-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
  }
  .id-status.active { background: #dcfce7; color: #15803d; }
  .id-status.inactive { background: #fee2e2; color: #dc2626; }
  .id-divider { height: 1px; background: #e2e8f0; margin: 16px 0; }
  .mosque-info { background: #f0f7f0; border-radius: 10px; padding: 12px 16px; margin-top: 12px; }
  .mosque-info-title { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
  .mosque-name { font-size: 15px; font-weight: 700; color: #0d5c2e; }
  .mosque-detail { font-size: 12px; color: #64748b; margin-top: 2px; }
  .print-btn {
    display: block;
    margin: 16px auto;
    max-width: 420px;
    text-align: center;
  }
  @media print {
    body { background: #fff; }
    .print-btn { display: none; }
    .id-card { box-shadow: none; margin: 0; max-width: 100%; }
  }
</style>
</head>
<body>

<div class="id-card">
  <div class="id-card-header">
    <div class="id-card-logo">🕌</div>
    <div>
      <div class="id-card-org">Cami Kuran Kursu</div>
      <div class="id-card-title">Öğrenci Kimlik Kartı</div>
    </div>
    <div style="margin-left:auto">
      <span class="id-status <?= $student['status'] ?>">
        <?= $student['status'] === 'active' ? '✅ Aktif' : '❌ Pasif' ?>
      </span>
    </div>
  </div>

  <div class="id-card-body">
    <div class="id-card-qr">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= urlencode($student['qr_code']) ?>&color=0d5c2e&bgcolor=ffffff"
           width="130" height="130" alt="QR Kod">
      <div style="text-align:center;font-family:monospace;font-size:10px;color:#94a3b8;margin-top:6px"><?= sanitize($student['qr_code']) ?></div>
    </div>
    <div class="id-card-info">
      <div class="id-name"><?= sanitize($student['name'].' '.$student['surname']) ?></div>
      <div style="margin-bottom:12px">
        <span style="font-size:20px"><?= $student['gender'] === 'male' ? '👦' : '👧' ?></span>
        <span style="font-size:13px;color:#64748b;margin-left:4px"><?= $student['gender'] === 'male' ? 'Erkek' : 'Kız' ?></span>
      </div>

      <div class="id-row"><span class="id-label">Yaş:</span><span class="id-value"><?= $age ?></span></div>
      <?php if ($student['birth_date']): ?>
      <div class="id-row"><span class="id-label">Doğum:</span><span class="id-value"><?= date('d.m.Y', strtotime($student['birth_date'])) ?></span></div>
      <?php endif; ?>
      <?php if ($student['tc_no']): ?>
      <div class="id-row"><span class="id-label">TC No:</span><span class="id-value"><?= substr(sanitize($student['tc_no']), 0, 3) . '****' . substr(sanitize($student['tc_no']), -2) ?></span></div>
      <?php endif; ?>

      <div class="id-divider"></div>
      <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Veli Bilgisi</div>
      <div class="id-row"><span class="id-label">Ad:</span><span class="id-value"><?= sanitize($student['p_name'].' '.$student['p_surname']) ?></span></div>
      <div class="id-row"><span class="id-label">Tel:</span><span class="id-value">📞 <?= substr(sanitize($student['p_phone']), 0, 4) . '***' . substr(sanitize($student['p_phone']), -2) ?></span></div>
    </div>
  </div>

  <div style="padding: 0 24px 20px">
    <div class="mosque-info">
      <div class="mosque-info-title">🕌 Kayıtlı Cami</div>
      <div class="mosque-name"><?= sanitize($student['mosque_name']) ?></div>
      <?php if ($student['district'] || $student['city']): ?>
      <div class="mosque-detail">📍 <?= sanitize(trim(($student['district'] ?? '').' / '.($student['city'] ?? ''), ' / ')) ?></div>
      <?php endif; ?>
      <?php if ($student['imam_name']): ?>
      <div class="mosque-detail">👤 İmam: <?= sanitize($student['imam_name']) ?></div>
      <?php endif; ?>
      <?php if ($student['mosque_phone']): ?>
      <div class="mosque-detail">📞 <?= sanitize($student['mosque_phone']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="id-card-footer">
    <span>Kayıt: <?= date('d.m.Y', strtotime($student['created_at'])) ?></span>
    <span><strong>Cami Öğrenci Sistemi</strong></span>
  </div>
</div>

<div class="print-btn">
  <button onclick="window.print()" class="btn btn-primary btn-lg" style="margin-right:8px">🖨️ Kartı Yazdır</button>
  <a href="index.php" class="btn btn-secondary btn-lg">🏠 Ana Sayfa</a>
</div>

</body>
</html>
