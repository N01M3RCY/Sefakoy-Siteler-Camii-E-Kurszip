<?php
require_once '../config/db.php';
requireLogin('parent', 'login.php');
$db  = getDB();
$pid = $_SESSION['parent_id'];

$mosqueIds = $db->prepare("SELECT DISTINCT mosque_id FROM students WHERE parent_id=?");
$mosqueIds->execute([$pid]);
$mosqueIds = $mosqueIds->fetchAll(PDO::FETCH_COLUMN);

$announcements = [];
if (!empty($mosqueIds)) {
    $in = implode(',', array_fill(0, count($mosqueIds), '?'));
    $stmt = $db->prepare("
        SELECT a.*, m.name AS mosque_name
        FROM announcements a LEFT JOIN mosques m ON a.mosque_id = m.id
        WHERE a.status = 'active'
          AND (
                (a.source_type = 'admin' AND (a.mosque_id IS NULL OR a.mosque_id IN ($in)))
                OR (a.source_type = 'mosque' AND a.mosque_id IN ($in))
              )
        ORDER BY a.created_at DESC
    ");
    $stmt->execute(array_merge($mosqueIds, $mosqueIds));
    $announcements = $stmt->fetchAll();
}

$page_title = 'Duyurular';
include 'layout/header.php';
?>

<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,#78350f,#c9a227);color:#fff">
  <div class="card-body" style="display:flex;gap:16px;align-items:center">
    <div style="font-size:48px">📢</div>
    <div>
      <div style="font-size:20px;font-weight:800">Duyurular</div>
      <div style="opacity:.85;font-size:13px">Müftülük ve caminizden gelen güncel duyurular</div>
    </div>
  </div>
</div>

<?php if (empty($announcements)): ?>
<div class="card">
  <div class="empty-state" style="padding:60px 20px">
    <div class="empty-state-icon">📢</div>
    <div class="empty-state-title">Henüz duyuru yok</div>
    <div class="empty-state-desc">Yeni duyuru geldiğinde burada görünecek.</div>
  </div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
  <?php foreach ($announcements as $a):
    $isAdmin = $a['source_type'] === 'admin';
  ?>
  <div class="card" style="border-left:4px solid <?= $isAdmin ? '#7c3aed' : '#1a7a3a' ?>">
    <div class="card-header">
      <div>
        <strong><?= sanitize($a['title']) ?></strong><br>
        <small style="color:#94a3b8"><?= $isAdmin ? '🏛️ Müftülük' : '🕌 '.sanitize($a['mosque_name']) ?></small>
      </div>
      <span style="font-size:12px;color:#94a3b8"><?= date('d.m.Y', strtotime($a['created_at'])) ?></span>
    </div>
    <div class="card-body">
      <p style="font-size:14px;color:#374151;line-height:1.6"><?= nl2br(sanitize($a['content'])) ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
