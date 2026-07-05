<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db  = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

// Kurs Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $name      = trim($_POST['name'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $teacher_id = (int)($_POST['teacher_id'] ?? 0) ?: null;
    if (!$name) { $error = 'Kurs adı zorunludur.'; }
    else {
        $db->prepare("INSERT INTO courses (mosque_id,name,description,teacher_id) VALUES (?,?,?,?)")
           ->execute([$mid, $name, $desc, $teacher_id]);
        $success = "\"$name\" kursu oluşturuldu.";
    }
}

// Hoca ata
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $cid = (int)$_POST['course_id'];
    $tid = (int)($_POST['teacher_id'] ?? 0) ?: null;
    $db->prepare("UPDATE courses SET teacher_id=? WHERE id=? AND mosque_id=?")->execute([$tid, $cid, $mid]);
    $success = 'Hoca ataması güncellendi.';
}

// Kurs öğrencisi ata (toplu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_students'])) {
    $cid      = (int)$_POST['course_id'];
    $sids     = $_POST['student_ids'] ?? [];
    // Önce bu kursdaki herkesi temizle (seçilenler hariç yeniden atama)
    foreach ($sids as $sid) {
        $db->prepare("UPDATE students SET course_id=? WHERE id=? AND mosque_id=?")->execute([$cid, (int)$sid, $mid]);
    }
    $success = count($sids).' öğrenci kursa atandı.';
}

// Sil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course'])) {
    $db->prepare("UPDATE students SET course_id=NULL WHERE course_id=? AND mosque_id=?")->execute([(int)$_POST['course_id'], $mid]);
    $db->prepare("DELETE FROM courses WHERE id=? AND mosque_id=?")->execute([(int)$_POST['course_id'], $mid]);
    $success = 'Kurs silindi.';
}

$courses = $db->prepare("SELECT c.*, t.name AS teacher_name, (SELECT COUNT(*) FROM students WHERE course_id=c.id) AS student_count FROM courses c LEFT JOIN teachers t ON c.teacher_id=t.id WHERE c.mosque_id=? ORDER BY c.name");
$courses->execute([$mid]); $courses = $courses->fetchAll();

$teachers = $db->prepare("SELECT * FROM teachers WHERE mosque_id=? AND status='active' ORDER BY name");
$teachers->execute([$mid]); $teachers = $teachers->fetchAll();

// Kurssuz öğrenciler
$unassigned = $db->prepare("SELECT id, name, surname, age, gender FROM students WHERE mosque_id=? AND (course_id IS NULL OR course_id=0) AND status='active' ORDER BY name");
$unassigned->execute([$mid]); $unassigned = $unassigned->fetchAll();

// Tüm öğrenciler
$allStudents = $db->prepare("SELECT id, name, surname, age, gender, course_id FROM students WHERE mosque_id=? AND status='active' ORDER BY name");
$allStudents->execute([$mid]); $allStudents = $allStudents->fetchAll();

// Seçilen kurs (detay için)
$selCourse = isset($_GET['course']) ? (int)$_GET['course'] : null;

$page_title = 'Kurslar & Gruplar';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<!-- Üst: Kurs Listesi + Ekle -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;align-items:start">

  <!-- Kurs Listesi -->
  <div class="card">
    <div class="card-header"><span class="card-title">📚 Kurslar (<?= count($courses) ?>)</span></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Kurs Adı</th><th>Hoca</th><th>Öğrenci</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
          <tr>
            <td>
              <strong><?= sanitize($c['name']) ?></strong>
              <?php if ($c['description']): ?><br><small style="color:#94a3b8"><?= sanitize($c['description']) ?></small><?php endif; ?>
            </td>
            <td>
              <?php if ($c['teacher_name']): ?>
                <span class="badge badge-success"><?= sanitize($c['teacher_name']) ?></span>
              <?php else: ?>
                <form method="post" style="display:flex;gap:6px;align-items:center">
                  <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                  <select name="teacher_id" class="form-control" style="font-size:12px;padding:4px 8px;height:auto">
                    <option value="">— Hoca seç —</option>
                    <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= sanitize($t['name']) ?></option><?php endforeach; ?>
                  </select>
                  <button name="assign_teacher" class="btn btn-sm btn-primary">Ata</button>
                </form>
              <?php endif; ?>
            </td>
            <td>
              <strong style="color:#1a7a3a"><?= $c['student_count'] ?></strong>
              <a href="courses.php?course=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" style="margin-left:6px">Öğrenci Ekle</a>
            </td>
            <td>
              <?php if ($c['teacher_name']): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="teacher_id" value="">
                <button name="assign_teacher" class="btn btn-sm btn-secondary" title="Hoca atamasını kaldır" onclick="return confirm('Hoca atamasını kaldırmak istiyor musunuz?')">↩️</button>
              </form>
              <?php endif; ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                <button name="delete_course" class="btn btn-sm btn-danger" onclick="return confirm('Kursu silmek istiyor musunuz? Öğrencilerin kurs ataması sıfırlanır.')">🗑️</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($courses)): ?><tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📚</div><div class="empty-state-title">Kurs yok</div></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Kurs Ekle -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><span class="card-title">➕ Yeni Kurs Ekle</span></div>
      <div class="card-body">
        <form method="post">
          <div class="form-group"><label class="form-label">Kurs Adı *</label><input type="text" name="name" class="form-control" placeholder="A Grubu, Sabah Kursu..." required></div>
          <div class="form-group"><label class="form-label">Açıklama</label><input type="text" name="description" class="form-control" placeholder="Kısa açıklama..."></div>
          <div class="form-group">
            <label class="form-label">Hoca (opsiyonel)</label>
            <select name="teacher_id" class="form-control">
              <option value="">— Sonra ata —</option>
              <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= sanitize($t['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <button name="add_course" class="btn btn-primary btn-block">📚 Kursu Oluştur</button>
        </form>
      </div>
    </div>

    <!-- Kurssuz öğrenciler -->
    <?php if (!empty($unassigned)): ?>
    <div class="card">
      <div class="card-header"><span class="card-title">⚠️ Kurssuz Öğrenciler</span></div>
      <div class="card-body" style="font-size:13px;color:#64748b">
        <?= count($unassigned) ?> öğrenci henüz bir kursa atanmadı. Aşağıdan öğrenci ekleyin.
        <div style="margin-top:8px;max-height:150px;overflow-y:auto">
          <?php foreach ($unassigned as $s): ?>
          <div style="padding:4px 0;border-bottom:1px solid #f1f5f9"><?= $s['gender']==='male'?'👦':'👧' ?> <?= sanitize($s['name'].' '.$s['surname']) ?> <?= $s['age']?'('.$s['age'].' yaş)':'' ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Öğrenci Atama (kurs seçilmişse) -->
<?php if ($selCourse):
  $sc = null;
  foreach ($courses as $c) { if ($c['id'] === $selCourse) { $sc = $c; break; } }
  if ($sc):
    $courseStudents = array_filter($allStudents, fn($s) => $s['course_id'] == $selCourse);
?>
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 "<?= sanitize($sc['name']) ?>" Kursuna Öğrenci Ekle</span>
    <a href="courses.php" class="btn btn-sm btn-secondary">← Geri</a>
  </div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="course_id" value="<?= $selCourse ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;max-height:360px;overflow-y:auto;margin-bottom:16px">
        <?php foreach ($allStudents as $s):
          $inThisCourse = $s['course_id'] == $selCourse;
          $inOther = $s['course_id'] && !$inThisCourse;
        ?>
        <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;cursor:pointer;border:1.5px solid <?= $inThisCourse?'#86efac':'#e2e8f0' ?>;background:<?= $inThisCourse?'#f0fdf4':'#fff' ?>">
          <input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" <?= $inThisCourse?'checked':'' ?> style="accent-color:#1a7a3a">
          <div>
            <div style="font-size:13px;font-weight:600"><?= sanitize($s['name'].' '.$s['surname']) ?></div>
            <div style="font-size:11px;color:#94a3b8"><?= $s['gender']==='male'?'👦':'👧' ?> <?= $s['age']?$s['age'].' yaş':'' ?><?= $inOther?' · <span style="color:#f97316">⚠️ Başka kurs</span>':'' ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <button name="assign_students" class="btn btn-primary">✅ Seçili Öğrencileri Kursa Ata</button>
    </form>
  </div>
</div>
<?php endif; endif; ?>

<?php include 'layout/footer.php'; ?>
