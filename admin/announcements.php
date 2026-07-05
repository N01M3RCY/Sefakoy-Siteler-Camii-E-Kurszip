<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ann'])) {
    $title  = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target = $_POST['target'] ?? 'all';
    $mosqueId = ($target === 'mosque') ? ((int)($_POST['mosque_id'] ?? 0) ?: null) : null;

    if (!$title || !$content) {
        $error = 'Başlık ve içerik zorunludur.';
    } elseif ($target === 'mosque' && !$mosqueId) {
        $error = 'Bir cami seçmelisiniz.';
    } else {
        $db->prepare("INSERT INTO announcements (source_type, mosque_id, title, content) VALUES ('admin', ?, ?, ?)")
           ->execute([$mosqueId, $title, $content]);
        $success = 'Duyuru yayınlandı.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ann'])) {
    $id = (int)$_POST['ann_id'];
    $cur = $db->prepare("SELECT status FROM announcements WHERE id=? AND source_type='admin'");
    $cur->execute([$id]); $cur = $cur->fetchColumn();
    if ($cur) {
        $db->prepare("UPDATE announcements SET status=? WHERE id=?")->execute([$cur === 'active' ? 'archived' : 'active', $id]);
        $success = 'Duyuru durumu güncellendi.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ann'])) {
    $db->prepare("DELETE FROM announcements WHERE id=? AND source_type='admin'")->execute([(int)$_POST['ann_id']]);
    $success = 'Duyuru silindi.';
}

$mosques = $db->query("SELECT id, name FROM mosques WHERE status='active' ORDER BY name")->fetchAll();

$announcements = $db->query("
    SELECT a.*, m.name AS mosque_name
    FROM announcements a LEFT JOIN mosques m ON a.mosque_id = m.id
    WHERE a.source_type = 'admin'
    ORDER BY a.created_at DESC
")->fetchAll();

$page_title = 'Duyurular';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">📢 Yayınlanan Duyurular (<?= count($announcements) ?>)</span></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Başlık</th><th>Hedef</th><th>Tarih</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($announcements as $a): ?>
          <tr>
            <td>
              <strong><?= sanitize($a['title']) ?></strong><br>
              <small style="color:#94a3b8"><?= sanitize(mb_substr($a['content'], 0, 80)) ?><?= mb_strlen($a['content']) > 80 ? '…' : '' ?></small>
            </td>
            <td>
              <?php if ($a['mosque_name']): ?>
                <span class="badge badge-info">🕌 <?= sanitize($a['mosque_name']) ?></span>
              <?php else: ?>
                <span class="badge badge-success">🌍 Tüm Camiler</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:#64748b"><?= date('d.m.Y', strtotime($a['created_at'])) ?></td>
            <td><span class="badge <?= $a['status']==='active'?'badge-success':'badge-danger' ?>"><?= $a['status']==='active'?'Aktif':'Arşivlendi' ?></span></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
                <button name="toggle_ann" class="btn btn-sm btn-secondary"><?= $a['status']==='active'?'📦 Arşivle':'♻️ Aktifleştir' ?></button>
              </form>
              <form method="post" style="display:inline">
                <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
                <button name="delete_ann" class="btn btn-sm btn-danger" onclick="return confirm('Duyuruyu silmek istiyor musunuz?')">🗑️</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($announcements)): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">📢</div><div class="empty-state-title">Henüz duyuru yok</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">➕ Yeni Duyuru</span></div>
    <div class="card-body">
      <form method="post">
        <div class="form-group"><label class="form-label">Başlık *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">İçerik *</label><textarea name="content" class="form-control" rows="5" required></textarea></div>
        <div class="form-group">
          <label class="form-label">Hedef Kitle</label>
          <label style="display:flex;align-items:center;gap:6px;margin-bottom:6px;cursor:pointer">
            <input type="radio" name="target" value="all" checked onchange="document.getElementById('mosquePicker').style.display='none'"> 🌍 Tüm Camiler
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
            <input type="radio" name="target" value="mosque" onchange="document.getElementById('mosquePicker').style.display='block'"> 🕌 Belirli Bir Cami
          </label>
        </div>
        <div class="form-group" id="mosquePicker" style="display:none">
          <label class="form-label">Cami</label>
          <select name="mosque_id" class="form-control">
            <?php foreach ($mosques as $m): ?><option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button name="add_ann" class="btn btn-primary btn-block">📢 Duyuruyu Yayınla</button>
      </form>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>
