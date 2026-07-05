<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$search   = trim($_GET['search'] ?? '');
$mosque_f = (int)($_GET['mosque'] ?? 0);
$where    = '1=1';
$params   = [];

if ($search) {
    $where  .= " AND (s.name LIKE ? OR s.surname LIKE ? OR s.tc_no LIKE ? OR s.qr_code LIKE ?)";
    $s = "%$search%"; $params = array_merge($params, [$s,$s,$s,$s]);
}
if ($mosque_f) { $where .= " AND s.mosque_id=?"; $params[] = $mosque_f; }

$stmt = $db->prepare("
    SELECT s.*, m.name AS mosque_name, p.name AS p_name, p.surname AS p_surname, p.phone AS p_phone
    FROM students s
    JOIN mosques m ON s.mosque_id = m.id
    JOIN parents p ON s.parent_id = p.id
    WHERE $where
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$students = $stmt->fetchAll();
$mosques  = $db->query("SELECT id, name FROM mosques WHERE status='active' ORDER BY name")->fetchAll();

$page_title = 'Öğrenciler';
include 'layout/header.php';
?>
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="get" style="display:flex;gap:8px;flex:1;flex-wrap:wrap">
      <input type="text" name="search" class="form-control" placeholder="🔍 Ad, soyad, TC, QR kod..." value="<?= sanitize($search) ?>" style="max-width:280px">
      <select name="mosque" class="form-control" style="max-width:220px">
        <option value="">Tüm Camiler</option>
        <?php foreach ($mosques as $m): ?>
        <option value="<?= $m['id'] ?>" <?= $mosque_f==$m['id']?'selected':'' ?>><?= sanitize($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary">Filtrele</button>
      <?php if ($search||$mosque_f): ?><a href="students.php" class="btn btn-secondary">Temizle</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">📚 Öğrenciler (<?= count($students) ?>)</span>
    <button onclick="window.print()" class="btn btn-sm btn-secondary no-print">🖨️ Yazdır</button>
    <a href="export.php?type=students&format=xls<?= $mosque_f?"&mosque=$mosque_f":'' ?>" class="btn btn-sm btn-secondary no-print">📥 Excel</a>
    <a href="export.php?type=students&format=csv<?= $mosque_f?"&mosque=$mosque_f":'' ?>" class="btn btn-sm btn-secondary no-print">📄 CSV</a>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Öğrenci</th><th>Doğum / Cinsiyet</th><th>Cami</th><th>Veli</th><th>QR Kod</th><th>Durum</th><th>İşlem</th></tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td style="font-size:12px;color:#94a3b8"><?= $s['id'] ?></td>
          <td>
            <strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong>
            <?php if ($s['tc_no']): ?><br><small style="color:#94a3b8">TC: <?= sanitize($s['tc_no']) ?></small><?php endif; ?>
          </td>
          <td>
            <?= $s['birth_date'] ? date('d.m.Y', strtotime($s['birth_date'])) : '—' ?><br>
            <span class="badge <?= $s['gender']==='male'?'badge-info':'badge-warning' ?>"><?= $s['gender']==='male'?'Erkek':'Kız' ?></span>
          </td>
          <td><?= sanitize($s['mosque_name']) ?></td>
          <td><?= sanitize($s['p_name'].' '.$s['p_surname']) ?><br><small style="color:#94a3b8">📞 <?= sanitize($s['p_phone']) ?></small></td>
          <td>
            <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=<?= urlencode($s['qr_code']) ?>" width="50" height="50" alt="QR" style="border-radius:4px">
            </a>
            <br><small style="font-family:monospace;font-size:10px"><?= sanitize($s['qr_code']) ?></small>
          </td>
          <td><span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>"><?= $s['status']==='active'?'Aktif':'Pasif' ?></span></td>
          <td>
            <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-secondary" title="QR Kart">🪪</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div class="empty-state-title">Öğrenci bulunamadı</div>
            <div class="empty-state-desc">Arama kriterlerini değiştirin.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'layout/footer.php'; ?>
