<?php
require_once '../config/db.php';
requireLogin('parent', 'login.php');
$db = getDB();
$pid = $_SESSION['parent_id'];

$childrenStmt = $db->prepare("SELECT s.id, s.name, s.surname, s.course_id, s.mosque_id, m.name AS mosque_name FROM students s JOIN mosques m ON s.mosque_id=m.id WHERE s.parent_id=? AND s.status='active'");
$childrenStmt->execute([$pid]);
$children = $childrenStmt->fetchAll();

$rows = [];
foreach ($children as $child) {
    $stmt = $db->prepare("
        SELECT h.*, hs.id AS hs_id, hs.status AS my_status, hs.completed_at
        FROM homeworks h
        LEFT JOIN homework_students hs ON hs.homework_id = h.id AND hs.student_id = ?
        WHERE h.mosque_id = ?
          AND h.status = 'active'
          AND (h.course_id IS NULL OR h.course_id = ?)
          AND (
                hs.id IS NOT NULL
                OR NOT EXISTS (SELECT 1 FROM homework_students hs2 WHERE hs2.homework_id = h.id)
              )
        ORDER BY h.due_date ASC, h.created_at DESC
    ");
    $stmt->execute([$child['id'], $child['mosque_id'], $child['course_id']]);
    foreach ($stmt->fetchAll() as $h) {
        $h['child_name'] = $child['name'] . ' ' . $child['surname'];
        $h['mosque_name'] = $child['mosque_name'];
        $h['effective_status'] = $h['my_status'] ?? 'active';
        $rows[] = $h;
    }
}

$page_title = 'Ödevler';
include 'layout/header.php';
?>

<div class="card" style="margin-bottom:20px;background:linear-gradient(135deg,#0d5c2e,#1a7a3a);color:#fff">
  <div class="card-body" style="display:flex;gap:16px;align-items:center">
    <div style="font-size:48px">📝</div>
    <div>
      <div style="font-size:20px;font-weight:800">Ödev Takibi</div>
      <div style="opacity:.8;font-size:13px">Çocuğunuza özel olarak verilen ödevler</div>
    </div>
  </div>
</div>

<?php if (empty($rows)): ?>
<div class="card">
  <div class="empty-state" style="padding:60px 20px">
    <div class="empty-state-icon">📝</div>
    <div class="empty-state-title">Şu an aktif ödev yok</div>
    <div class="empty-state-desc">Hocası yeni ödev eklediğinde burada görünecek.</div>
  </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px">
  <?php foreach ($rows as $h):
    $overdue = $h['due_date'] && $h['due_date'] < date('Y-m-d') && $h['effective_status'] !== 'done';
    $isDone = $h['effective_status'] === 'done';
  ?>
  <div class="card" style="border-left:4px solid <?= $isDone ? '#1a7a3a' : ($overdue ? '#dc2626' : '#c9a227') ?>">
    <div class="card-header">
      <div>
        <strong><?= sanitize($h['title']) ?></strong><br>
        <small style="color:#94a3b8">🧒 <?= sanitize($h['child_name']) ?> · 🕌 <?= sanitize($h['mosque_name']) ?></small>
      </div>
      <span class="badge <?= $isDone ? 'badge-success' : 'badge-warning' ?>"><?= $isDone ? '✅ Tamamlandı' : '📝 Aktif' ?></span>
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
