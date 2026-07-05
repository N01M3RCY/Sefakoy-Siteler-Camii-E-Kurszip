<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

// Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dua'])) {
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? 'genel';
    $sched    = $_POST['scheduled_date'] ?? '';
    if (!$title || !$content) {
        $error = 'Başlık ve içerik zorunludur.';
    } else {
        $db->prepare("INSERT INTO duas (mosque_id,title,content,category,scheduled_date) VALUES (?,?,?,?,?)")
           ->execute([$mid, $title, $content, $category, $sched ?: null]);
        $success = 'Dua başarıyla eklendi.';
    }
}

// Sil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dua'])) {
    $db->prepare("DELETE FROM duas WHERE id=? AND mosque_id=?")->execute([(int)$_POST['dua_id'], $mid]);
    $success = 'Dua silindi.';
}

// Filtre
$cat_filter = $_GET['cat'] ?? '';
$where = "mosque_id=?"; $params = [$mid];
if ($cat_filter) { $where .= " AND category=?"; $params[] = $cat_filter; }

$duas = $db->prepare("SELECT * FROM duas WHERE $where ORDER BY created_at DESC");
$duas->execute($params);
$duas = $duas->fetchAll();

$cats = [
    'sabah'  => ['label'=>'Sabah Duası',  'color'=>'#f59e0b', 'icon'=>'🌅'],
    'oglen'  => ['label'=>'Öğlen Duası',  'color'=>'#f97316', 'icon'=>'☀️'],
    'ikindi' => ['label'=>'İkindi Duası', 'color'=>'#eab308', 'icon'=>'🌤️'],
    'aksam'  => ['label'=>'Akşam Duası',  'color'=>'#8b5cf6', 'icon'=>'🌆'],
    'yatsi'  => ['label'=>'Yatsı Duası',  'color'=>'#1d4ed8', 'icon'=>'🌙'],
    'genel'  => ['label'=>'Genel Dua',    'color'=>'#1a7a3a', 'icon'=>'🤲'],
    'ozel'   => ['label'=>'Özel Dua',     'color'=>'#c9a227', 'icon'=>'⭐'],
];

$page_title = 'Dua Sistemi';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<!-- Dua Ekle Formu -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <span class="card-title">🤲 Yeni Dua Ekle</span>
  </div>
  <div class="card-body">
    <form method="post">
      <div class="form-row">
        <div class="form-group" style="flex:2">
          <label class="form-label">Dua Başlığı *</label>
          <input type="text" name="title" class="form-control" placeholder="Sabah Duası, Kuran Hatim Duası..." required>
        </div>
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select name="category" class="form-control">
            <?php foreach ($cats as $val => $c): ?>
            <option value="<?= $val ?>"><?= $c['icon'] ?> <?= $c['label'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tarih <small style="color:#64748b">(opsiyonel)</small></label>
          <input type="date" name="scheduled_date" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Dua Metni *</label>
        <textarea name="content" class="form-control" rows="4" placeholder="Duanın Arapça, Türkçe metni veya açıklaması..." required></textarea>
      </div>
      <button name="add_dua" class="btn btn-primary">🤲 Duayı Kaydet</button>
    </form>
  </div>
</div>

<!-- Filtre -->
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;padding:12px 16px">
    <a href="duas.php" class="btn btn-sm <?= !$cat_filter?'btn-primary':'btn-secondary' ?>">Tümü</a>
    <?php foreach ($cats as $val => $c): ?>
    <a href="duas.php?cat=<?= $val ?>" class="btn btn-sm <?= $cat_filter===$val?'btn-primary':'btn-secondary' ?>"><?= $c['icon'] ?> <?= $c['label'] ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Dua Listesi -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
  <?php foreach ($duas as $d):
    $c = $cats[$d['category']] ?? $cats['genel'];
  ?>
  <div class="card" style="border-left:4px solid <?= $c['color'] ?>">
    <div class="card-header" style="padding-bottom:8px">
      <div>
        <span style="font-size:16px"><?= $c['icon'] ?></span>
        <strong style="margin-left:6px"><?= sanitize($d['title']) ?></strong><br>
        <span style="font-size:11px;color:#94a3b8">
          <?= $c['label'] ?>
          <?php if ($d['scheduled_date']): ?> · 📅 <?= date('d.m.Y', strtotime($d['scheduled_date'])) ?><?php endif; ?>
          · <?= date('d.m.Y', strtotime($d['created_at'])) ?>
        </span>
      </div>
    </div>
    <div class="card-body" style="padding-top:8px">
      <div style="white-space:pre-wrap;font-size:14px;line-height:1.7;color:#374151;background:#f8fafc;padding:12px;border-radius:8px;max-height:160px;overflow-y:auto"><?= sanitize($d['content']) ?></div>
      <form method="post" style="margin-top:10px;text-align:right">
        <input type="hidden" name="dua_id" value="<?= $d['id'] ?>">
        <button name="delete_dua" class="btn btn-sm btn-danger" onclick="return confirm('Bu duayı silmek istediğinizden emin misiniz?')">🗑️ Sil</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($duas)): ?>
  <div style="grid-column:1/-1">
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-state-icon">🤲</div>
      <div class="empty-state-title">Henüz dua eklenmedi</div>
      <div class="empty-state-desc">Yukarıdaki formu kullanarak dua ekleyin.</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>
