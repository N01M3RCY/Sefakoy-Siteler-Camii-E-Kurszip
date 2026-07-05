<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$stats = [
    'mosques'  => $db->query("SELECT COUNT(*) FROM mosques")->fetchColumn(),
    'active'   => $db->query("SELECT COUNT(*) FROM mosques WHERE status='active'")->fetchColumn(),
    'pending'  => $db->query("SELECT COUNT(*) FROM mosques WHERE status='pending'")->fetchColumn(),
    'students' => $db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'parents'  => $db->query("SELECT COUNT(*) FROM parents")->fetchColumn(),
];

$recentMosques = $db->query("SELECT * FROM mosques ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentStudents = $db->query("
    SELECT s.*, m.name as mosque_name, p.name as parent_name, p.surname as parent_surname
    FROM students s
    JOIN mosques m ON s.mosque_id = m.id
    JOIN parents p ON s.parent_id = p.id
    ORDER BY s.created_at DESC LIMIT 5
")->fetchAll();

$page_title = 'Kontrol Paneli';
include 'layout/header.php';
?>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">🕌</div>
    <div>
      <div class="stat-value"><?= $stats['mosques'] ?></div>
      <div class="stat-label">Toplam Cami</div>
    </div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon">⏳</div>
    <div>
      <div class="stat-value"><?= $stats['pending'] ?></div>
      <div class="stat-label">Onay Bekleyen</div>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">👨‍👩‍👧</div>
    <div>
      <div class="stat-value"><?= $stats['parents'] ?></div>
      <div class="stat-label">Kayıtlı Veli</div>
    </div>
  </div>
  <div class="stat-card" style="border-left-color:#7c3aed">
    <div class="stat-icon" style="background:#f5f3ff">📚</div>
    <div>
      <div class="stat-value"><?= $stats['students'] ?></div>
      <div class="stat-label">Toplam Öğrenci</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
  <!-- Son Camiler -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">🕌 Son Kayıt Olan Camiler</span>
      <a href="mosques.php" class="btn btn-sm btn-secondary">Tümü</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Cami Adı</th><th>Durum</th><th>Tarih</th></tr></thead>
        <tbody>
          <?php foreach ($recentMosques as $m): ?>
          <tr>
            <td><strong><?= sanitize($m['name']) ?></strong><br><small style="color:#64748b"><?= sanitize($m['district'] ?? '') ?></small></td>
            <td>
              <?php if ($m['status'] === 'active'): ?>
                <span class="badge badge-success">Aktif</span>
              <?php elseif ($m['status'] === 'pending'): ?>
                <span class="badge badge-warning">Bekliyor</span>
              <?php else: ?>
                <span class="badge badge-danger">Pasif</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#64748b"><?= date('d.m.Y', strtotime($m['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentMosques)): ?>
          <tr><td colspan="3"><div class="empty-state" style="padding:20px"><div class="empty-state-icon">🕌</div><div class="empty-state-desc">Henüz cami yok</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Son Öğrenciler -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📚 Son Kayıt Öğrenciler</span>
      <a href="students.php" class="btn btn-sm btn-secondary">Tümü</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Öğrenci</th><th>Cami</th><th>Tarih</th></tr></thead>
        <tbody>
          <?php foreach ($recentStudents as $s): ?>
          <tr>
            <td>
              <strong><?= sanitize($s['name'] . ' ' . $s['surname']) ?></strong><br>
              <small style="color:#64748b">Veli: <?= sanitize($s['parent_name'] . ' ' . $s['parent_surname']) ?></small>
            </td>
            <td style="font-size:12px"><?= sanitize($s['mosque_name']) ?></td>
            <td style="font-size:12px;color:#64748b"><?= date('d.m.Y', strtotime($s['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentStudents)): ?>
          <tr><td colspan="3"><div class="empty-state" style="padding:20px"><div class="empty-state-icon">📚</div><div class="empty-state-desc">Henüz öğrenci yok</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Hızlı Eylemler -->
<div class="card">
  <div class="card-header"><span class="card-title">⚡ Hızlı İşlemler</span></div>
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="mosques.php?action=add" class="btn btn-primary">➕ Yeni Cami Ekle</a>
    <a href="mosques.php?filter=pending" class="btn btn-gold">⏳ Onay Bekleyenler (<?= $stats['pending'] ?>)</a>
    <a href="students.php" class="btn btn-secondary">📚 Öğrencileri Görüntüle</a>
    <a href="parents.php" class="btn btn-secondary">👨‍👩‍👧 Velileri Görüntüle</a>
    <a href="change_password.php" class="btn btn-secondary">🔑 Şifre Değiştir</a>
  </div>
</div>

<?php include 'layout/footer.php'; ?>
