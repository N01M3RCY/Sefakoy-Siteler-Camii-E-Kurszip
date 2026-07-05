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

// Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mem'])) {
    $title   = trim($_POST['title'] ?? '');
    $type    = $_POST['type'] ?? 'sure';
    $content = trim($_POST['content'] ?? '');
    $due     = $_POST['due_date'] ?? '';
    if (!$title) { $error = 'Başlık zorunludur.'; }
    else {
        $db->prepare("INSERT INTO memorizations (mosque_id,teacher_id,course_id,title,type,content,due_date) VALUES (?,?,?,?,?,?,?)")
           ->execute([$mid, $tid, $cid, $title, $type, $content, $due ?: null]);
        $success = 'Ezber görevi eklendi.';
    }
}

// Tamamlandı / Sil
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
$tf = $_GET['type'] ?? '';
$where = "mosque_id=? AND teacher_id=? AND status=?"; $params = [$mid, $tid, $sf];
if ($tf) { $where .= " AND type=?"; $params[] = $tf; }
$mems = $db->prepare("SELECT * FROM memorizations WHERE $where ORDER BY due_date ASC, created_at DESC");
$mems->execute($params); $mems = $mems->fetchAll();

$counts = $db->prepare("SELECT status, COUNT(*) c FROM memorizations WHERE mosque_id=? AND teacher_id=? GROUP BY status");
$counts->execute([$mid, $tid]); $counts = array_column($counts->fetchAll(), 'c', 'status');

$types = [
    'sure'   => ['label' => 'Sure Ezberi', 'icon' => '📖', 'color' => '#1a7a3a'],
    'dua'    => ['label' => 'Dua Ezberi',  'icon' => '🤲', 'color' => '#c9a227'],
    'ayet'   => ['label' => 'Ayet Ezberi', 'icon' => '✨', 'color' => '#8b5cf6'],
    'diger'  => ['label' => 'Diğer',       'icon' => '📋', 'color' => '#64748b'],
];

$page_title = 'Sure / Dua Ezberi';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<?php if ($course): ?>
<div class="alert alert-info" style="margin-bottom:16px">📚 Kurs: <strong><?= sanitize($course['name']) ?></strong></div>
<?php endif; ?>

<!-- İstatistikler -->
<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card"><div class="stat-icon">📖</div><div><div class="stat-value"><?= $counts['active'] ?? 0 ?></div><div class="stat-label">Aktif Ezber</div></div></div>
  <div class="stat-card gold"><div class="stat-icon">✅</div><div><div class="stat-value"><?= $counts['done'] ?? 0 ?></div><div class="stat-label">Tamamlanan</div></div></div>
</div>

<!-- Ekle Formu -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">➕ Yeni Ezber Görevi Ekle</span></div>
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
        <div class="form-group">
          <label class="form-label">Son Tarih</label>
          <input type="date" name="due_date" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">İçerik / Açıklama</label>
        <textarea name="content" class="form-control" rows="4" placeholder="Sure metni, duanın Türkçe anlamı, nasıl ezberleneceği..."></textarea>
      </div>
      <button name="add_mem" class="btn btn-primary">📖 Ezber Görevi Ekle</button>
    </form>
  </div>
</div>

<!-- Filtre -->
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;padding:12px 16px">
    <a href="memorizations.php?status=active" class="btn btn-sm <?= $sf==='active'?'btn-primary':'btn-secondary' ?>">📖 Aktif (<?= $counts['active'] ?? 0 ?>)</a>
    <a href="memorizations.php?status=done" class="btn btn-sm <?= $sf==='done'?'btn-primary':'btn-secondary' ?>">✅ Tamamlanan (<?= $counts['done'] ?? 0 ?>)</a>
    <span style="margin:0 4px;color:#e2e8f0">|</span>
    <?php foreach ($types as $val => $t): ?>
    <a href="memorizations.php?status=<?= $sf ?>&type=<?= $val ?>" class="btn btn-sm <?= $tf===$val?'btn-primary':'btn-secondary' ?>"><?= $t['icon'] ?> <?= $t['label'] ?></a>
    <?php endforeach; ?>
    <?php if ($tf): ?><a href="memorizations.php?status=<?= $sf ?>" class="btn btn-sm btn-secondary">✕ Temizle</a><?php endif; ?>
  </div>
</div>

<!-- Liste -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
  <?php foreach ($mems as $m):
    $t = $types[$m['type']] ?? $types['diger'];
    $overdue = $m['due_date'] && $m['due_date'] < date('Y-m-d') && $m['status']==='active';
  ?>
  <div class="card" style="border-left:4px solid <?= $t['color'] ?>;<?= $m['status']==='done'?'opacity:.7':'' ?>">
    <div class="card-header">
      <div>
        <span><?= $t['icon'] ?></span>
        <strong style="margin-left:6px"><?= sanitize($m['title']) ?></strong><br>
        <span style="font-size:11px;color:#94a3b8">
          <?= $t['label'] ?>
          <?php if ($m['due_date']): ?>
            · <span style="<?= $overdue?'color:#dc2626;font-weight:700':'' ?>"><?= $overdue?'⚠️ ':'' ?>📅 <?= date('d.m.Y',strtotime($m['due_date'])) ?></span>
          <?php endif; ?>
        </span>
      </div>
      <span class="badge <?= $m['status']==='done'?'badge-success':'badge-warning' ?>"><?= $m['status']==='done'?'✅ Tamam':'📖 Aktif' ?></span>
    </div>
    <?php if ($m['content']): ?>
    <div class="card-body" style="padding-top:8px">
      <div style="white-space:pre-wrap;font-size:13px;line-height:1.7;background:#f8fafc;padding:10px;border-radius:8px;max-height:140px;overflow-y:auto"><?= sanitize($m['content']) ?></div>
    </div>
    <?php endif; ?>
    <div style="display:flex;gap:6px;padding:10px 16px;border-top:1px solid #f1f5f9">
      <form method="post" style="display:inline">
        <input type="hidden" name="mem_id" value="<?= $m['id'] ?>">
        <button name="toggle_mem" class="btn btn-sm btn-secondary"><?= $m['status']==='done'?'↩️ Aktife Al':'✅ Tamamlandı' ?></button>
      </form>
      <form method="post" style="display:inline;margin-left:auto">
        <input type="hidden" name="mem_id" value="<?= $m['id'] ?>">
        <button name="delete_mem" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?')">🗑️</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($mems)): ?>
  <div style="grid-column:1/-1"><div class="empty-state" style="padding:60px"><div class="empty-state-icon">📖</div><div class="empty-state-title">Ezber görevi yok</div></div></div>
  <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>
