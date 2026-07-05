<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

$course = $db->prepare("SELECT * FROM courses WHERE teacher_id=? AND mosque_id=? AND status='active' LIMIT 1");
$course->execute([$tid, $mid]); $course = $course->fetch();
$cid = $course['id'] ?? null;

$success = $error = '';

$STANDART = [
    ['dua','Sübhaneke'], ['dua','Tehiyyat'], ['dua','Salli'], ['dua','Barik'],
    ['dua','Rabbena Duası (Rabbena Atina)'], ['dua','Rabbenağfirli'],
    ['dua','Kunut 1'], ['dua','Kunut 2'],
    ['sure','Fatiha'], ['sure','Nas'], ['sure','Felak'], ['sure','İhlas'], ['sure','Tebbet'],
    ['sure','Nasr'], ['sure','Kafirun'], ['sure','Kevser'], ['sure','Maun'], ['sure','Kureyş'],
    ['sure','Fil'], ['dua','Ayetel Kürsü'], ['sure','Haşr Son 5 Ayet'], ['sure','Bakara 285-286'],
    ['sure','Hümeze'], ['sure','Asr'], ['sure','Tekasür'], ['sure','Karia'], ['sure','Adiyat'],
    ['sure','Zilzal'], ['sure','Beyyine'], ['sure','Kadir'], ['sure','Alak'], ['sure','Tin'],
    ['sure','İnşirah'], ['sure','Duha'],
];

// Standart sırayı yükle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_standard']) && $cid) {
    $cnt = $db->prepare("SELECT COUNT(*) FROM memorizations WHERE course_id=?");
    $cnt->execute([$cid]);
    if ((int)$cnt->fetchColumn() === 0) {
        $ord = 1;
        $ins = $db->prepare("INSERT INTO memorizations (mosque_id,teacher_id,course_id,title,type,sort_order) VALUES (?,?,?,?,?,?)");
        foreach ($STANDART as [$type, $title]) {
            $ins->execute([$mid, $tid, $cid, $title, $type, $ord++]);
        }
        $success = 'Standart sıralama ('.count($STANDART).' sure/dua) yüklendi.';
    } else {
        $error = 'Bu kursta zaten ezber listesi var. Önce mevcut listeyi temizleyin.';
    }
}

// Ekle (listenin sonuna)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mem']) && $cid) {
    $title   = trim($_POST['title'] ?? '');
    $type    = $_POST['type'] ?? 'sure';
    $content = trim($_POST['content'] ?? '');
    if (!$title) { $error = 'Başlık zorunludur.'; }
    else {
        $max = $db->prepare("SELECT COALESCE(MAX(sort_order),0) FROM memorizations WHERE course_id=?");
        $max->execute([$cid]);
        $next = (int)$max->fetchColumn() + 1;
        $db->prepare("INSERT INTO memorizations (mosque_id,teacher_id,course_id,title,type,content,sort_order) VALUES (?,?,?,?,?,?,?)")
           ->execute([$mid, $tid, $cid, $title, $type, $content, $next]);
        $success = '"'.$title.'" listeye '.$next.'. sıraya eklendi.';
    }
}

// Sırayı yukarı/aşağı taşı
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_mem']) && $cid) {
    $memId = (int)$_POST['mem_id'];
    $dir   = $_POST['dir'] === 'up' ? 'up' : 'down';
    $cur = $db->prepare("SELECT * FROM memorizations WHERE id=? AND course_id=?");
    $cur->execute([$memId, $cid]); $cur = $cur->fetch();
    if ($cur) {
        $neighborStmt = $dir === 'up'
            ? $db->prepare("SELECT * FROM memorizations WHERE course_id=? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1")
            : $db->prepare("SELECT * FROM memorizations WHERE course_id=? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        $neighborStmt->execute([$cid, $cur['sort_order']]);
        $neighbor = $neighborStmt->fetch();
        if ($neighbor) {
            $db->prepare("UPDATE memorizations SET sort_order=? WHERE id=?")->execute([$neighbor['sort_order'], $cur['id']]);
            $db->prepare("UPDATE memorizations SET sort_order=? WHERE id=?")->execute([$cur['sort_order'], $neighbor['id']]);
        }
    }
}

// Aktif/Pasif (arşivle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_mem'])) {
    $m = $db->prepare("SELECT status FROM memorizations WHERE id=? AND teacher_id=?");
    $m->execute([(int)$_POST['mem_id'], $tid]);
    $cur = $m->fetchColumn();
    $db->prepare("UPDATE memorizations SET status=? WHERE id=? AND teacher_id=?")->execute([$cur==='active'?'done':'active', (int)$_POST['mem_id'], $tid]);
    $success = 'Güncellendi.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mem'])) {
    $db->prepare("DELETE FROM memorizations WHERE id=? AND teacher_id=?")->execute([(int)$_POST['mem_id'], $tid]);
    $success = 'Silindi.';
}

$sf = $_GET['status'] ?? 'active';
$mems = [];
if ($cid) {
    $mems = $db->prepare("SELECT * FROM memorizations WHERE course_id=? AND status=? ORDER BY sort_order ASC, created_at ASC");
    $mems->execute([$cid, $sf]); $mems = $mems->fetchAll();
}

$counts = $db->prepare("SELECT status, COUNT(*) c FROM memorizations WHERE mosque_id=? AND teacher_id=? GROUP BY status");
$counts->execute([$mid, $tid]); $counts = array_column($counts->fetchAll(), 'c', 'status');

// Her ezber öğesi için öğrenci ilerleme özeti (sadece aktif listede, sırayla)
$progressSummary = [];
if ($cid && $mems) {
    $studentCountStmt = $db->prepare("SELECT COUNT(*) FROM students WHERE course_id=? AND status='active'");
    $studentCountStmt->execute([$cid]);
    $totalStudents = (int)$studentCountStmt->fetchColumn();

    $ids = array_column($mems, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sp = $db->prepare("SELECT sp.memorization_id, sp.status, COUNT(*) c FROM student_progress sp JOIN students s ON sp.student_id=s.id WHERE sp.memorization_id IN ($in) AND s.status='active' GROUP BY sp.memorization_id, sp.status");
    $sp->execute($ids);
    foreach ($sp->fetchAll() as $row) {
        $progressSummary[$row['memorization_id']][$row['status']] = (int)$row['c'];
    }
}

$types = [
    'sure'   => ['label' => 'Sure Ezberi', 'icon' => '📖', 'color' => '#1a7a3a'],
    'dua'    => ['label' => 'Dua Ezberi',  'icon' => '🤲', 'color' => '#c9a227'],
    'ayet'   => ['label' => 'Ayet Ezberi', 'icon' => '✨', 'color' => '#8b5cf6'],
    'diger'  => ['label' => 'Diğer',       'icon' => '📋', 'color' => '#64748b'],
];

$page_title = 'Sure / Dua Sıralı Ezber Listesi';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<?php if (!$course): ?>
<div class="alert alert-info">⚠️ Henüz bir kursa atanmadınız. Cami yöneticisiyle iletişime geçin.</div>
<?php else: ?>

<div class="alert alert-info" style="margin-bottom:16px">📚 Kurs: <strong><?= sanitize($course['name']) ?></strong> · Öğrenciler bu listedeki sırayla ilerler: bir sureyi/duayı ezberlemeden bir sonrakine geçemez.</div>

<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card"><div class="stat-icon">📖</div><div><div class="stat-value"><?= $counts['active'] ?? 0 ?></div><div class="stat-label">Listedeki Sıra</div></div></div>
  <div class="stat-card gold"><div class="stat-icon">🗄️</div><div><div class="stat-value"><?= $counts['done'] ?? 0 ?></div><div class="stat-label">Arşivlenen</div></div></div>
  <div class="stat-card blue"><div class="stat-icon">🧭</div><div><a href="progress.php" class="stat-value" style="text-decoration:none;color:inherit">Takip →</a><div class="stat-label">Öğrenci İlerlemesi</div></div></div>
</div>

<?php if (empty($mems) && $sf === 'active' && (int)($counts['active'] ?? 0) === 0 && (int)($counts['done'] ?? 0) === 0): ?>
<div class="card" style="margin-bottom:24px;border-left:4px solid #c9a227">
  <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div>
      <strong>🚀 Hızlı Başlangıç</strong>
      <div style="font-size:13px;color:#64748b;margin-top:4px">Standart cami dersi sıralamasını (Sübhaneke'den Duha'ya, <?= count($STANDART) ?> sure/dua) tek tıkla yükleyin. Sonra dilediğiniz gibi sırasını değiştirebilirsiniz.</div>
    </div>
    <form method="post"><button name="load_standard" class="btn btn-primary">📥 Standart Sırayı Yükle</button></form>
  </div>
</div>
<?php endif; ?>

<!-- Ekle Formu -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">➕ Listeye Yeni Sure/Dua Ekle</span></div>
  <div class="card-body">
    <form method="post">
      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label class="form-label">Başlık *</label>
          <input type="text" name="title" class="form-control" placeholder="Fatiha Suresi, Ayetel Kürsi, Sübhaneke Duası..." required>
        </div>
        <div class="form-group">
          <label class="form-label">Tür</label>
          <select name="type" class="form-control">
            <?php foreach ($types as $val => $t): ?>
            <option value="<?= $val ?>"><?= $t['icon'] ?> <?= $t['label'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">İçerik / Açıklama</label>
        <textarea name="content" class="form-control" rows="3" placeholder="Sure metni, duanın Türkçe anlamı, nasıl ezberleneceği..."></textarea>
      </div>
      <button name="add_mem" class="btn btn-primary">📖 Listenin Sonuna Ekle</button>
    </form>
  </div>
</div>

<!-- Filtre -->
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;padding:12px 16px">
    <a href="memorizations.php?status=active" class="btn btn-sm <?= $sf==='active'?'btn-primary':'btn-secondary' ?>">📖 Aktif Liste (<?= $counts['active'] ?? 0 ?>)</a>
    <a href="memorizations.php?status=done" class="btn btn-sm <?= $sf==='done'?'btn-primary':'btn-secondary' ?>">🗄️ Arşiv (<?= $counts['done'] ?? 0 ?>)</a>
  </div>
</div>

<!-- Sıralı Liste -->
<div class="card">
  <div class="card-header"><span class="card-title">📋 Ezber Sırası</span></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>#</th><th>Başlık</th><th>Tür</th><th>Öğrenci Durumu</th><th>Sıra</th><th>İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($mems as $i => $m):
          $t = $types[$m['type']] ?? $types['diger'];
          $prog = $progressSummary[$m['id']] ?? [];
          $done = $prog['tamamlandi'] ?? 0;
          $tekrar = $prog['tekrar'] ?? 0;
        ?>
        <tr style="<?= $m['status']==='done'?'opacity:.6':'' ?>">
          <td><span class="badge badge-info" style="font-size:13px;font-weight:800">#<?= $i+1 ?></span></td>
          <td>
            <strong><?= sanitize($m['title']) ?></strong>
            <?php if ($m['content']): ?><br><small style="color:#94a3b8"><?= sanitize(mb_substr($m['content'],0,60)) ?><?= mb_strlen($m['content'])>60?'...':'' ?></small><?php endif; ?>
          </td>
          <td><span><?= $t['icon'] ?></span> <?= $t['label'] ?></td>
          <td style="font-size:12px">
            <?php if ($done): ?><span class="badge badge-success">✅ <?= $done ?> tamamladı</span><?php endif; ?>
            <?php if ($tekrar): ?><span class="badge badge-warning">🔁 <?= $tekrar ?> tekrar ediyor</span><?php endif; ?>
            <?php if (!$done && !$tekrar): ?><span style="color:#94a3b8">—</span><?php endif; ?>
          </td>
          <td>
            <?php if ($m['status']==='active'): ?>
            <form method="post" style="display:inline"><input type="hidden" name="mem_id" value="<?= $m['id'] ?>"><input type="hidden" name="dir" value="up"><button name="move_mem" class="btn btn-sm btn-secondary" <?= $i===0?'disabled':'' ?>>⬆️</button></form>
            <form method="post" style="display:inline"><input type="hidden" name="mem_id" value="<?= $m['id'] ?>"><input type="hidden" name="dir" value="down"><button name="move_mem" class="btn btn-sm btn-secondary" <?= $i===count($mems)-1?'disabled':'' ?>>⬇️</button></form>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <form method="post" style="display:inline"><input type="hidden" name="mem_id" value="<?= $m['id'] ?>"><button name="toggle_mem" class="btn btn-sm btn-secondary" title="<?= $m['status']==='done'?'Aktife Al':'Arşivle' ?>"><?= $m['status']==='done'?'↩️':'🗄️' ?></button></form>
              <form method="post" style="display:inline"><input type="hidden" name="mem_id" value="<?= $m['id'] ?>"><button name="delete_mem" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz? Öğrenci ilerlemesi de silinecek.')">🗑️</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($mems)): ?>
        <tr><td colspan="6"><div class="empty-state" style="padding:40px"><div class="empty-state-icon">📖</div><div class="empty-state-title"><?= $sf==='active'?'Ezber listesi boş':'Arşivde öğe yok' ?></div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
<?php include 'layout/footer.php'; ?>
