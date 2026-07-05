<?php
require_once '../config/db.php';
requireLogin('parent', 'login.php');
$db = getDB();
$pid = $_SESSION['parent_id'];

// Bu velinin çocuklarının kayıtlı olduğu camileri bul
$mosques_stmt = $db->prepare("
    SELECT DISTINCT m.id, m.name FROM mosques m
    JOIN students s ON s.mosque_id = m.id
    WHERE s.parent_id = ?
");
$mosques_stmt->execute([$pid]);
$parent_mosques = $mosques_stmt->fetchAll();
$mosque_ids = array_column($parent_mosques, 'id');

$homeworks = [];
if (!empty($mosque_ids)) {
    $in = implode(',', array_fill(0, count($mosque_ids), '?'));
    $hw_stmt = $db->prepare("
        SELECT h.*, m.name AS mosque_name
        FROM homeworks h
        JOIN mosques m ON h.mosque_id = m.id
        WHERE h.mosque_id IN ($in) AND h.status = 'active'
        ORDER BY h.due_date ASC, h.created_at DESC
    ");
    $hw_stmt->execute($mosque_ids);
    $homeworks = $hw_stmt->fetchAll();
}

$page_title = 'Ödevler';
include 'layout/header.php';
?>

<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,#0d5c2e,#1a7a3a);color:#fff">
  <div class="card-body" style="display:flex;gap:16px;align-items:center">
    <div style="font-size:48px">📝</div>
    <div>
      <div style="font-size:20px;font-weight:800">Ödev Takibi</div>
      <div style="opacity:.8;font-size:13px">Çocuğunuzun camisinden verilen ödevler</div>
    </div>
  </div>
</div>

<?php if (empty($homeworks)): ?>
<div class="card">
  <div class="empty-state" style="padding:60px 20px">
    <div class="empty-state-icon">📝</div>
    <div class="empty-state-title">Şu an aktif ödev yok</div>
    <div class="empty-state-desc">Cami personeli yeni ödev eklediğinde burada görünecek.</div>
  </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
  <?php foreach ($homeworks as $h):
    $overdue = $h['due_date'] && $h['due_date'] < date('Y-m-d');
  ?>
  <div class="card" style="border-left:4px solid <?= $overdue ? '#dc2626' : '#1a7a3a' ?>">
    <div class="card-header">
      <div>
        <strong><?= sanitize($h['title']) ?></strong><br>
        <small style="color:#94a3b8">🕌 <?= sanitize($h['mosque_name']) ?></small>
      </div>
    </div>
    <div class="card-body">
      <?php if ($h['description']): ?>
      <p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:12px"><?= nl2br(sanitize($h['description'])) ?></p>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;align-items:center">
        <?php if ($h['due_date']): ?>
        <span style="font-size:13px;<?= $overdue ? 'color:#dc2626;font-weight:700' : 'color:#1a7a3a' ?>">
          <?= $overdue ? '⚠️ Son tarih geçti: ' : '📅 Son tarih: ' ?><?= date('d.m.Y', strtotime($h['due_date'])) ?>
        </span>
        <?php else: ?>
        <span style="font-size:13px;color:#94a3b8">📅 Son tarih belirtilmedi</span>
        <?php endif; ?>
        <span style="font-size:12px;color:#94a3b8"><?= date('d.m.Y', strtotime($h['created_at'])) ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
