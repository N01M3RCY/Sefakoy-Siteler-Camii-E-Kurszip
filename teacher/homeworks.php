<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

$course = $db->prepare("SELECT * FROM courses WHERE teacher_id=? AND mosque_id=? AND status='active' LIMIT 1");
$course->execute([$tid, $mid]); $course = $course->fetch();
$cid = $course['id'] ?? null;

$students = [];
if ($cid) {
    $s = $db->prepare("SELECT id, name, surname FROM students WHERE course_id=? AND status='active' ORDER BY name");
    $s->execute([$cid]); $students = $s->fetchAll();
}

$success = $error = '';

// Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hw']) && $cid) {
    $title  = trim($_POST['title'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $due    = $_POST['due_date'] ?? '';
    $target = $_POST['target'] ?? 'class';
    $sids   = $_POST['student_ids'] ?? [];

    if (!$title) {
        $error = 'Başlık zorunludur.';
    } elseif ($target === 'students' && empty($sids)) {
        $error = 'En az bir öğrenci seçmelisiniz.';
    } else {
        $db->prepare("INSERT INTO homeworks (mosque_id,course_id,title,description,due_date) VALUES (?,?,?,?,?)")
           ->execute([$mid, $cid, $title, $desc, $due ?: null]);
        $hwId = $db->lastInsertId();

        $assignTo = $target === 'students' ? array_map('intval', $sids) : array_column($students, 'id');
        $ins = $db->prepare("INSERT INTO homework_students (homework_id, student_id) VALUES (?,?)");
        foreach ($assignTo as $sid) {
            // sadece bu kursa ait öğrencilere ata
            $valid = $db->prepare("SELECT id FROM students WHERE id=? AND course_id=? AND status='active'");
            $valid->execute([$sid, $cid]);
            if ($valid->fetch()) { $ins->execute([$hwId, $sid]); }
        }
        $success = 'Ödev '.count($assignTo).' öğrenciye atandı.';
    }
}

// Tüm ödevi arşivle/aktife al
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_hw'])) {
    $hw = $db->prepare("SELECT status FROM homeworks WHERE id=? AND mosque_id=? AND course_id=?");
    $hw->execute([(int)$_POST['hw_id'], $mid, $cid]);
    $cur = $hw->fetchColumn();
    if ($cur !== false) {
        $db->prepare("UPDATE homeworks SET status=? WHERE id=?")->execute([$cur==='active'?'done':'active', (int)$_POST['hw_id']]);
        $success = 'Ödev güncellendi.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hw'])) {
    $db->prepare("DELETE FROM homeworks WHERE id=? AND mosque_id=? AND course_id=?")->execute([(int)$_POST['hw_id'], $mid, $cid]);
    $success = 'Silindi.';
}

// Öğrenci bazlı tamamlandı/aktif toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_student_hw'])) {
    $hsId = (int)$_POST['hs_id'];
    $chk = $db->prepare("SELECT hs.id, hs.status FROM homework_students hs JOIN homeworks h ON hs.homework_id=h.id WHERE hs.id=? AND h.mosque_id=? AND h.course_id=?");
    $chk->execute([$hsId, $mid, $cid]);
    $row = $chk->fetch();
    if ($row) {
        $new = $row['status'] === 'done' ? 'active' : 'done';
        $db->prepare("UPDATE homework_students SET status=?, completed_at=? WHERE id=?")->execute([$new, $new==='done' ? date('Y-m-d H:i:s') : null, $hsId]);
        $success = 'Öğrenci ödev durumu güncellendi.';
    }
}

$sf = $_GET['status'] ?? 'active';
$hws = [];
if ($cid) {
    $q = $db->prepare("SELECT * FROM homeworks WHERE mosque_id=? AND course_id=? AND status=? ORDER BY due_date ASC, created_at DESC");
    $q->execute([$mid, $cid, $sf]); $hws = $q->fetchAll();
}
$counts = ['active'=>0,'done'=>0];
if ($cid) {
    $c = $db->prepare("SELECT status, COUNT(*) c FROM homeworks WHERE mosque_id=? AND course_id=? GROUP BY status");
    $c->execute([$mid, $cid]); $counts = array_merge($counts, array_column($c->fetchAll(), 'c', 'status'));
}

// Her ödev için öğrenci atamalarını çek
$assignments = [];
if ($hws) {
    $ids = array_column($hws, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $a = $db->prepare("SELECT hs.*, s.name, s.surname FROM homework_students hs JOIN students s ON hs.student_id=s.id WHERE hs.homework_id IN ($in) ORDER BY s.name");
    $a->execute($ids);
    foreach ($a->fetchAll() as $row) { $assignments[$row['homework_id']][] = $row; }
}

$expand = isset($_GET['odev']) ? (int)$_GET['odev'] : null;

$page_title = 'Ödevler';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<?php if (!$course): ?>
<div class="alert alert-info">⚠️ Henüz bir kursa atanmadınız. Cami yöneticisiyle iletişime geçin.</div>
<?php else: ?>

<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card"><div class="stat-icon">📝</div><div><div class="stat-value"><?= $counts['active']??0 ?></div><div class="stat-label">Aktif Ödev</div></div></div>
  <div class="stat-card gold"><div class="stat-icon">🗄️</div><div><div class="stat-value"><?= $counts['done']??0 ?></div><div class="stat-label">Arşiv</div></div></div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">➕ Yeni Ödev Ekle</span></div>
  <div class="card-body">
    <form method="post" id="hwForm">
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
      <div class="form-group">
        <label class="form-label">Kime Atanacak? *</label>
        <div style="display:flex;gap:16px;margin-bottom:10px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="radio" name="target" value="class" checked onchange="document.getElementById('studentPicker').style.display='none'"> 👥 Tüm Sınıf (<?= count($students) ?> öğrenci)</label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="radio" name="target" value="students" onchange="document.getElementById('studentPicker').style.display='grid'"> 🎯 Belirli Öğrenci(ler)</label>
        </div>
        <div id="studentPicker" style="display:none;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:6px;max-height:220px;overflow-y:auto;padding:10px;background:#f8fafc;border-radius:8px">
          <?php foreach ($students as $s): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
            <input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>"> <?= sanitize($s['name'].' '.$s['surname']) ?>
          </label>
          <?php endforeach; ?>
          <?php if (empty($students)): ?><span style="color:#94a3b8;font-size:13px">Kursta öğrenci yok</span><?php endif; ?>
        </div>
      </div>
      <button name="add_hw" class="btn btn-primary">📝 Ödevi Kaydet</button>
    </form>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:8px;padding:12px 16px">
    <a href="homeworks.php?status=active" class="btn btn-sm <?= $sf==='active'?'btn-primary':'btn-secondary' ?>">📝 Aktif (<?= $counts['active']??0 ?>)</a>
    <a href="homeworks.php?status=done" class="btn btn-sm <?= $sf==='done'?'btn-primary':'btn-secondary' ?>">🗄️ Arşiv (<?= $counts['done']??0 ?>)</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Ödev</th><th>Son Tarih</th><th>Tamamlanma</th><th>İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($hws as $h):
          $overdue = $h['due_date'] && $h['due_date'] < date('Y-m-d') && $h['status']==='active';
          $list = $assignments[$h['id']] ?? [];
          $doneCount = count(array_filter($list, fn($a) => $a['status']==='done'));
        ?>
        <tr style="<?= $overdue?'background:#fff7ed':'' ?>">
          <td>
            <strong><?= sanitize($h['title']) ?></strong><br>
            <small style="color:#94a3b8"><?= $h['description'] ? sanitize(mb_substr($h['description'],0,80)) : date('d.m.Y',strtotime($h['created_at'])) ?></small>
          </td>
          <td><?= $h['due_date']?'<span style="'.($overdue?'color:#dc2626;font-weight:700':'color:#1a7a3a').'">'.($overdue?'⚠️ ':'📅 ').date('d.m.Y',strtotime($h['due_date'])).'</span>':'—' ?></td>
          <td>
            <strong style="color:#1a7a3a"><?= $doneCount ?>/<?= count($list) ?></strong>
            <a href="homeworks.php?status=<?= $sf ?>&odev=<?= $expand === $h['id'] ? '' : $h['id'] ?>" class="btn btn-sm btn-secondary" style="margin-left:6px"><?= $expand === $h['id'] ? '▲' : '▼ Detay' ?></a>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <form method="post" style="display:inline"><input type="hidden" name="hw_id" value="<?= $h['id'] ?>"><button name="toggle_hw" class="btn btn-sm btn-secondary" title="<?= $h['status']==='done'?'Aktife Al':'Arşivle' ?>"><?= $h['status']==='done'?'↩️':'🗄️' ?></button></form>
              <form method="post" style="display:inline"><input type="hidden" name="hw_id" value="<?= $h['id'] ?>"><button name="delete_hw" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istiyor musunuz?')">🗑️</button></form>
            </div>
          </td>
        </tr>
        <?php if ($expand === $h['id']): ?>
        <tr>
          <td colspan="4" style="background:#f8fafc">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;padding:8px">
              <?php foreach ($list as $a): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:6px 10px;border-radius:8px;border:1px solid <?= $a['status']==='done'?'#86efac':'#e2e8f0' ?>;background:<?= $a['status']==='done'?'#f0fdf4':'#fff' ?>;font-size:13px">
                <span><?= sanitize($a['name'].' '.$a['surname']) ?></span>
                <form method="post"><input type="hidden" name="hs_id" value="<?= $a['id'] ?>"><button name="toggle_student_hw" class="btn btn-sm <?= $a['status']==='done'?'btn-secondary':'btn-primary' ?>"><?= $a['status']==='done'?'↩️':'✅' ?></button></form>
              </div>
              <?php endforeach; ?>
              <?php if (empty($list)): ?><span style="color:#94a3b8;font-size:13px">Atanan öğrenci yok</span><?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($hws)): ?><tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📝</div><div class="empty-state-title">Ödev yok</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
<?php include 'layout/footer.php'; ?>
