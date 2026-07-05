<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$search = trim($_GET['search'] ?? '');
$where = '1=1'; $params = [];
if ($search) {
    $where .= " AND (p.name LIKE ? OR p.surname LIKE ? OR p.phone LIKE ? OR p.tc_no LIKE ?)";
    $s = "%$search%"; $params = [$s,$s,$s,$s];
}
$stmt = $db->prepare("
    SELECT p.*, COUNT(s.id) as student_count
    FROM parents p
    LEFT JOIN students s ON s.parent_id = p.id
    WHERE $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$parents = $stmt->fetchAll();

$page_title = 'Veliler';
include 'layout/header.php';
?>
<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    <form method="get" style="display:flex;gap:8px">
      <input type="text" name="search" class="form-control" placeholder="🔍 Ad, soyad, telefon, TC..." value="<?= sanitize($search) ?>" style="max-width:320px">
      <button class="btn btn-primary">Ara</button>
      <?php if ($search): ?><a href="parents.php" class="btn btn-secondary">Temizle</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">👨‍👩‍👧 Veliler (<?= count($parents) ?>)</span>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Ad Soyad</th><th>TC No</th><th>Telefon</th><th>E-posta</th><th>Öğrenci Sayısı</th><th>Kayıt Tarihi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($parents as $p): ?>
        <tr>
          <td style="font-size:12px;color:#94a3b8"><?= $p['id'] ?></td>
          <td><strong><?= sanitize($p['name'].' '.$p['surname']) ?></strong></td>
          <td><?= sanitize($p['tc_no'] ?? '—') ?></td>
          <td>📞 <?= sanitize($p['phone']) ?></td>
          <td><?= sanitize($p['email'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= $p['student_count'] ?> öğrenci</span></td>
          <td style="font-size:12px;color:#94a3b8"><?= date('d.m.Y', strtotime($p['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($parents)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">👨‍👩‍👧</div>
            <div class="empty-state-title">Veli bulunamadı</div>
            <div class="empty-state-desc">Henüz veli kaydı yapılmamış.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'layout/footer.php'; ?>
