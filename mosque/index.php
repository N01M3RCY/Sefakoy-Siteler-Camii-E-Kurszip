<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$stats = [
    'students'  => $db->prepare("SELECT COUNT(*) FROM students WHERE mosque_id=? AND status='active'"),
    'total'     => $db->prepare("SELECT COUNT(*) FROM students WHERE mosque_id=?"),
    'today'     => $db->prepare("SELECT COUNT(*) FROM attendance WHERE mosque_id=? AND scan_date=CURDATE()"),
    'parents'   => $db->prepare("SELECT COUNT(DISTINCT parent_id) FROM students WHERE mosque_id=?"),
];
foreach ($stats as $k => $s) { $s->execute([$mid]); $stats[$k] = $s->fetchColumn(); }

$mosque = $db->prepare("SELECT * FROM mosques WHERE id=?");
$mosque->execute([$mid]);
$mosque = $mosque->fetch();

$recentStudents = $db->prepare("
    SELECT s.*, p.name AS p_name, p.surname AS p_surname, p.phone AS p_phone
    FROM students s JOIN parents p ON s.parent_id=p.id
    WHERE s.mosque_id=? ORDER BY s.created_at DESC LIMIT 8
");
$recentStudents->execute([$mid]);
$recentStudents = $recentStudents->fetchAll();

$page_title = 'Kontrol Paneli';
include 'layout/header.php';
?>

<!-- Cami info banner -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#0d5c2e,#1a7a3a);color:#fff;border-radius:16px">
  <div class="card-body" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
    <div style="font-size:56px">🕌</div>
    <div style="flex:1">
      <div style="font-size:22px;font-weight:800"><?= sanitize($mosque['name']) ?></div>
      <div style="opacity:.8;font-size:13px;margin-top:4px">
        <?php if ($mosque['district']): ?><?= sanitize($mosque['district']) ?> / <?= sanitize($mosque['city'] ?? '') ?> · <?php endif; ?>
        <?php if ($mosque['imam_name']): ?>İmam: <?= sanitize($mosque['imam_name']) ?> · <?php endif; ?>
        <?php if ($mosque['phone']): ?>📞 <?= sanitize($mosque['phone']) ?><?php endif; ?>
      </div>
    </div>
    <div>
      <a href="../register.php" target="_blank" class="btn btn-gold">📋 Veli Kayıt Formu →</a>
    </div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📚</div>
    <div><div class="stat-value"><?= $stats['students'] ?></div><div class="stat-label">Aktif Öğrenci</div></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon">👨‍👩‍👧</div>
    <div><div class="stat-value"><?= $stats['parents'] ?></div><div class="stat-label">Kayıtlı Veli</div></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">✅</div>
    <div><div class="stat-value"><?= $stats['today'] ?></div><div class="stat-label">Bugün Yoklama</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🎓</div>
    <div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Toplam Öğrenci</div></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">📚 Son Kayıt Öğrenciler</span>
    <a href="students.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Öğrenci</th><th>Veli</th><th>QR Kod</th><th>Durum</th><th>İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($recentStudents as $s): ?>
        <tr>
          <td>
            <strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong><br>
            <small style="color:#94a3b8"><?= $s['gender']==='male'?'Erkek':'Kız' ?><?= $s['birth_date'] ? ' · '.date('d.m.Y',strtotime($s['birth_date'])) : '' ?></small>
          </td>
          <td><?= sanitize($s['p_name'].' '.$s['p_surname']) ?><br><small style="color:#94a3b8">📞 <?= sanitize($s['p_phone']) ?></small></td>
          <td>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=<?= urlencode($s['qr_code']) ?>" width="44" height="44" style="border-radius:4px">
          </td>
          <td><span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>"><?= $s['status']==='active'?'Aktif':'Pasif' ?></span></td>
          <td><a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-secondary">🪪 Kart</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($recentStudents)): ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div class="empty-state-title">Henüz öğrenci kaydı yok</div>
            <div class="empty-state-desc">Velileri <a href="../register.php" style="color:#1a7a3a">kayıt formu</a> üzerinden yönlendirin.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'layout/footer.php'; ?>
