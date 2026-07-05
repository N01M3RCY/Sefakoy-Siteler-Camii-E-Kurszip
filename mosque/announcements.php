<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db  = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ann'])) {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!$title || !$content) {
        $error = 'Başlık ve içerik zorunludur.';
    } else {
        $db->prepare("INSERT INTO announcements (source_type, mosque_id, title, content) VALUES ('mosque', ?, ?, ?)")
           ->execute([$mid, $title, $content]);
        $success = 'Duyuru velilere yayınlandı.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ann'])) {
    $id = (int)$_POST['ann_id'];
    $cur = $db->prepare("SELECT status FROM announcements WHERE id=? AND mosque_id=? AND source_type='mosque'");
    $cur->execute([$id, $mid]); $cur = $cur->fetchColumn();
    if ($cur) {
        $db->prepare("UPDATE announcements SET status=? WHERE id=?")->execute([$cur === 'active' ? 'archived' : 'active', $id]);
        $success = 'Duyuru durumu güncellendi.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ann'])) {
    $db->prepare("DELETE FROM announcements WHERE id=? AND mosque_id=? AND source_type='mosque'")->execute([(int)$_POST['ann_id'], $mid]);
    $success = 'Duyuru silindi.';
}

$myAnnouncements = $db->prepare("SELECT * FROM announcements WHERE mosque_id=? AND source_type='mosque' ORDER BY created_at DESC");
$myAnnouncements->execute([$mid]); $myAnnouncements = $myAnnouncements->fetchAll();

$adminAnnouncements = $db->prepare("
    SELECT * FROM announcements
    WHERE source_type='admin' AND status='active' AND (mosque_id IS NULL OR mosque_id=?)
    ORDER BY created_at DESC LIMIT 5
");
$adminAnnouncements->execute([$mid]); $adminAnnouncements = $adminAnnouncements->fetchAll();

$page_title = 'Duyurular';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<?php if (!empty($adminAnnouncements)): ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid #7c3aed">
  <div class="card-header"><span class="card-title">🏛️ Müftülükten Gelen Duyurular</span></div>
  <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($adminAnnouncements as $a): ?>
    <div style="padding:10px 14px;background:#f5f3ff;border-radius:8px">
      <div style="font-weight:700;color:#5b21b6"><?= sanitize($a['title']) ?></div>
      <div style="font-size:13px;color:#374151;margin-top:4px"><?= nl2br(sanitize($a['content'])) ?></div>
      <div style="font-size:11px;color:#94a3b8;margin-top:6px"><?= date('d.m.Y', strtotime($a['created_at'])) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">📢 Velilere Duyurularım (<?= count($myAnnouncements) ?>)</span></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Başlık</th><th>Tarih</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($myAnnouncements as $a): ?>
          <tr>
            <td>
              <strong><?= sanitize($a['title']) ?></strong><br>
              <small style="color:#94a3b8"><?= sanitize(mb_substr($a['content'], 0, 80)) ?><?= mb_strlen($a['content']) > 80 ? '…' : '' ?></small>
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
          <?php if (empty($myAnnouncements)): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📢</div><div class="empty-state-title">Henüz duyuru yok</div></div></td></tr>
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
        <button name="add_ann" class="btn btn-primary btn-block">📢 Velilere Yayınla</button>
      </form>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>
