<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

// Hocanın kursu
$course = $db->prepare("SELECT * FROM courses WHERE teacher_id=? AND mosque_id=? AND status='active' LIMIT 1");
$course->execute([$tid, $mid]);
$course = $course->fetch();
$cid = $course['id'] ?? null;

// İstatistikler
$totalStudents = $cid ? $db->query("SELECT COUNT(*) FROM students WHERE course_id=$cid AND status='active'")->fetchColumn() : 0;
$todayAtt = $db->prepare("SELECT COUNT(*) FROM attendance a JOIN students s ON a.student_id=s.id WHERE s.mosque_id=? AND a.scan_date=CURDATE()" . ($cid ? " AND s.course_id=$cid" : " AND 1=0"));
$todayAtt->execute([$mid]); $todayAtt = $todayAtt->fetchColumn();
$activeMems = $cid ? $db->query("SELECT COUNT(*) FROM memorizations WHERE course_id=$cid AND status='active'")->fetchColumn() : 0;
$activeHws  = $db->prepare("SELECT COUNT(*) FROM homeworks WHERE mosque_id=? AND status='active'");
$activeHws->execute([$mid]); $activeHws = $activeHws->fetchColumn();

// Son öğrenciler
$students = [];
if ($cid) {
    $s = $db->prepare("SELECT s.*, p.name AS p_name, p.surname AS p_surname FROM students s LEFT JOIN parents p ON s.parent_id=p.id WHERE s.course_id=? AND s.status='active' ORDER BY s.name LIMIT 8");
    $s->execute([$cid]); $students = $s->fetchAll();
}

$page_title = 'Kontrol Paneli';
include 'layout/header.php';
?>

<!-- Banner -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#4c1d95,#6d28d9);color:#fff;border-radius:16px">
  <div class="card-body" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
    <div style="font-size:52px">👨‍🏫</div>
    <div style="flex:1">
      <div style="font-size:22px;font-weight:800"><?= sanitize($_SESSION['teacher_name']) ?></div>
      <div style="opacity:.8;font-size:13px;margin-top:4px">
        🕌 <?= sanitize($_SESSION['teacher_mosque_name']) ?>
        <?php if ($course): ?> · 📚 <?= sanitize($course['name']) ?><?php endif; ?>
      </div>
    </div>
    <?php if (!$course): ?>
    <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:12px 16px;font-size:13px">
      ⚠️ Henüz bir kursa atanmadınız.<br>Cami yöneticisiyle iletişime geçin.
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📚</div>
    <div><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">Kurs Öğrencisi</div></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon">✅</div>
    <div><div class="stat-value"><?= $todayAtt ?></div><div class="stat-label">Bugün Yoklama</div></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">📖</div>
    <div><div class="stat-value"><?= $activeMems ?></div><div class="stat-label">Aktif Ezbер</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📝</div>
    <div><div class="stat-value"><?= $activeHws ?></div><div class="stat-label">Aktif Ödev</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">
  <div class="card">
    <div class="card-header">
      <span class="card-title">📚 Kurs Öğrencilerim</span>
      <a href="students.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
    </div>
    <?php if (empty($students)): ?>
    <div class="empty-state" style="padding:40px">
      <div class="empty-state-icon">📚</div>
      <div class="empty-state-title"><?= $cid ? 'Kursta öğrenci yok' : 'Kursa atanmadınız' ?></div>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Öğrenci</th><th>Yaş</th><th>Durum</th></tr></thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr>
            <td><strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong><br>
              <span class="badge <?= $s['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:10px"><?= $s['gender']==='male'?'👦':'👧' ?></span>
            </td>
            <td><?= $s['age'] ? $s['age'].' yaş' : '—' ?></td>
            <td><span class="badge badge-success">Aktif</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><span class="card-title">⚡ Hızlı Erişim</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="memorizations.php" class="btn btn-primary btn-block">📖 Sure / Dua Ezberi</a>
        <a href="attendance.php" class="btn btn-secondary btn-block">✅ Yoklama Al</a>
        <a href="homeworks.php" class="btn btn-secondary btn-block">📝 Ödevler</a>
        <a href="duas.php" class="btn btn-secondary btn-block">🤲 Dua Sistemi</a>
      </div>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>
