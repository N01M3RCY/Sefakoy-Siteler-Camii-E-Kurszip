<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hw'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $due   = $_POST['due_date'] ?? '';
    if (!$title) { $error = 'Başlık zorunludur.'; }
    else {
        $db->prepare("INSERT INTO homeworks (mosque_id,title,description,due_date) VALUES (?,?,?,?)")
           ->execute([$mid, $title, $desc, $due ?: null]);
        $success = 'Ödev eklendi.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_hw'])) {
    $hw = $db->prepare("SELECT status FROM homeworks WHERE id=? AND mosque_id=?");
    $hw->execute([(int)$_POST['hw_id'], $mid]);
    $cur = $hw->fetchColumn();
    $db->prepare("UPDATE homeworks SET status=? WHERE id=? AND mosque_id=?")->execute([$cur==='active'?'done':'active', (int)$_POST['hw_id'], $mid]);
    $success = 'Güncellendi.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hw'])) {
    $db->prepare("DELETE FROM homeworks WHERE id=? AND mosque_id=?")->execute([(int)$_POST['hw_id'], $mid]);
    $success = 'Silindi.';
}

$sf = $_GET['status'] ?? 'active';
$hws = $db->prepare("SELECT * FROM homeworks WHERE mosque_id=? AND status=? ORDER BY due_date ASC, created_at DESC");
$hws->execute([$mid, $sf]); $hws = $hws->fetchAll();
$counts = $db->prepare("SELECT status, COUNT(*) c FROM homeworks WHERE mosque_id=? GROUP BY status");
$counts->execute([$mid]); $counts = array_column($counts->fetchAll(), 'c', 'status');

$page_title = 'Ödevler';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card"><div class="stat-icon">📝</div><div><div class="stat-value"><?= $counts['active']??0 ?></div><div class="stat-label">Aktif Ödev</div></div></div>
  <div class="stat-card gold"><div class="stat-icon">✅</div><div><div class="stat-value"><?= $counts['done']??0 ?></div><div class="stat-label">Tamamlanan</div></div></div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">➕ Yeni Ödev Ekle</span></div>
  <div class="card-body">
    <form method="post">
      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label class="form-label">Ödev Başlığı *</label>
          <input type="text" name="title" class="form-control" placeholder="Sure ezberle, Dua öğren..." required>
        </div>
        <div class="form-group">
          <label class="form-label">Son Tarih</label>
          <input type="date" name="due_date" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Açıklama</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Ödevin detayları..."></textarea>
      </div>
      <button name="add_hw" class="btn btn-primary">📝 Ödevi Kaydet</button>
    </form>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:8px;padding:12px 16px">
    <a href="homeworks.php?status=active" class="btn btn-sm <?= $sf==='active'?'btn-primary':'btn-secondary' ?>">📝 Aktif (<?= $counts['active']??0 ?>)</a>
    <a href="homeworks.php?status=done" class="btn btn-sm <?= $sf==='done'?'btn-primary':'btn-secondary' ?>">✅ Tamamlanan (<?= $counts['done']??0 ?>)</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Ödev</th><th>Açıklama</th><th>Son Tarih</th><th>İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($hws as $h):
          $overdue = $h['due_date'] && $h['due_date'] < date('Y-m-d') && $h['status']==='active';
        ?>
        <tr style="<?= $overdue?'background:#fff7ed':'' ?>">
          <td><strong><?= sanitize($h['title']) ?></strong><br><small style="color:#94a3b8"><?= date('d.m.Y',strtotime($h['created_at'])) ?></small></td>
          <td style="font-size:13px;color:#64748b;max-width:200px"><?= $h['description']?sanitize(mb_substr($h['description'],0,80)):'—' ?></td>
          <td><?= $h['due_date']?'<span style="'.($overdue?'color:#dc2626;font-weight:700':'color:#1a7a3a').'">'.($overdue?'⚠️ ':'📅 ').date('d.m.Y',strtotime($h['due_date'])).'</span>':'—' ?></td>
          <td>
            <div style="display:flex;gap:4px">
              <form method="post" style="display:inline"><input type="hidden" name="hw_id" value="<?= $h['id'] ?>"><button name="toggle_hw" class="btn btn-sm btn-secondary"><?= $h['status']==='done'?'↩️':'✅' ?></button></form>
              <form method="post" style="display:inline"><input type="hidden" name="hw_id" value="<?= $h['id'] ?>"><button name="delete_hw" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istiyor musunuz?')">🗑️</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($hws)): ?><tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📝</div><div class="empty-state-title">Ödev yok</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'layout/footer.php'; ?>
