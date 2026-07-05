<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

$course = $db->prepare("SELECT * FROM courses WHERE teacher_id=? AND mosque_id=? AND status='active' LIMIT 1");
$course->execute([$tid, $mid]); $course = $course->fetch();
$cid = $course['id'] ?? null;

$search = trim($_GET['search'] ?? '');
$where  = "s.mosque_id=?"; $params = [$mid];
if ($cid) { $where .= " AND s.course_id=?"; $params[] = $cid; }
else       { $where .= " AND 1=0"; } // Kurs yoksa hiçbir şey gösterme

if ($search) {
    $where .= " AND (s.name LIKE ? OR s.surname LIKE ?)";
    $sv = "%$search%"; array_push($params, $sv, $sv);
}

$stmt = $db->prepare("SELECT s.*, p.name AS p_name, p.surname AS p_surname, p.phone AS p_phone FROM students s LEFT JOIN parents p ON s.parent_id=p.id WHERE $where ORDER BY s.name");
$stmt->execute($params); $students = $stmt->fetchAll();

// Yaş grubu dağılımı
$ages = $cid ? $db->query("SELECT age, gender FROM students WHERE course_id=$cid AND status='active'")->fetchAll() : [];

$page_title = 'Öğrencilerim';
include 'layout/header.php';
?>

<?php if (!$course): ?>
<div class="alert alert-info">⚠️ Henüz bir kursa atanmadınız. Cami yöneticisiyle iletişime geçin.</div>
<?php else: ?>

<div class="card" style="margin-bottom:16px;background:linear-gradient(135deg,#4c1d95,#6d28d9);color:#fff">
  <div class="card-body" style="display:flex;gap:12px;align-items:center">
    <div style="font-size:36px">📚</div>
    <div>
      <div style="font-weight:800;font-size:18px"><?= sanitize($course['name']) ?></div>
      <?php if ($course['description']): ?><div style="opacity:.8;font-size:13px"><?= sanitize($course['description']) ?></div><?php endif; ?>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;gap:16px">
      <div style="font-size:24px;font-weight:800"><?= count($students) ?> öğrenci</div>
      <a href="add_student.php" class="btn btn-sm" style="background:#fff;color:#4c1d95">➕ Öğrenci Ekle</a>
    </div>
  </div>
</div>

<!-- Yaş dağılımı -->
<?php if (!empty($ages)): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:24px;align-items:center;flex-wrap:wrap">
    <?php
    $erkek = count(array_filter($ages, fn($a) => $a['gender']==='male'));
    $kiz   = count($ages) - $erkek;
    $yasList = array_filter(array_column($ages,'age'));
    $avgAge = $yasList ? round(array_sum($yasList)/count($yasList),1) : '—';
    ?>
    <div style="text-align:center"><div style="font-size:28px;font-weight:800;color:#3b82f6">👦 <?= $erkek ?></div><div style="font-size:12px;color:#64748b">Erkek</div></div>
    <div style="text-align:center"><div style="font-size:28px;font-weight:800;color:#ec4899">👧 <?= $kiz ?></div><div style="font-size:12px;color:#64748b">Kız</div></div>
    <div style="text-align:center"><div style="font-size:28px;font-weight:800;color:#1a7a3a">⌀ <?= $avgAge ?></div><div style="font-size:12px;color:#64748b">Ort. Yaş</div></div>
  </div>
</div>
<?php endif; ?>

<!-- Arama -->
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="padding:12px 16px">
    <form method="get" style="display:flex;gap:8px">
      <input type="text" name="search" class="form-control" placeholder="🔍 Ad, soyad..." value="<?= sanitize($search) ?>" style="max-width:280px">
      <button class="btn btn-primary">Ara</button>
      <?php if ($search): ?><a href="students.php" class="btn btn-secondary">Temizle</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">📚 Öğrenciler (<?= count($students) ?>)</span></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>#</th><th>Öğrenci</th><th>Yaş</th><th>Veli</th><th>QR Kod</th><th>Durum</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td style="color:#94a3b8;font-size:12px"><?= $s['id'] ?></td>
          <td>
            <strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong><br>
            <span class="badge <?= $s['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:10px"><?= $s['gender']==='male'?'👦 Erkek':'👧 Kız' ?></span>
          </td>
          <td><?= $s['age'] ? $s['age'].' yaş' : '—' ?></td>
          <td><?= $s['p_name'] ? sanitize($s['p_name'].' '.$s['p_surname']).'<br><small style="color:#94a3b8">📞 '.sanitize($s['p_phone']).'</small>' : '<span style="color:#94a3b8">—</span>' ?></td>
          <td><code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= sanitize($s['qr_code']) ?></code></td>
          <td><span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>"><?= $s['status']==='active'?'✅ Aktif':'❌ Pasif' ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📚</div><div class="empty-state-title">Öğrenci bulunamadı</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php include 'layout/footer.php'; ?>
