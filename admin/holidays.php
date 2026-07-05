<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_holiday'])) {
    $name = trim($_POST['name'] ?? '');
    $date = $_POST['holiday_date'] ?? '';
    $type = in_array($_POST['type'] ?? '', ['resmi','dini','ozel']) ? $_POST['type'] : 'resmi';

    if (!$name || !$date) {
        $error = 'Tatil adı ve tarih zorunludur.';
    } else {
        try {
            $db->prepare("INSERT INTO holidays (name, holiday_date, type) VALUES (?,?,?)")->execute([$name, $date, $type]);
            $success = 'Tatil eklendi.';
        } catch (Exception $e) {
            $error = 'Bu tarihte aynı isimde bir tatil zaten var.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_holiday'])) {
    $db->prepare("DELETE FROM holidays WHERE id=?")->execute([(int)$_POST['holiday_id']]);
    $success = 'Tatil silindi.';
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$holidays = $db->prepare("SELECT * FROM holidays WHERE YEAR(holiday_date)=? ORDER BY holiday_date");
$holidays->execute([$year]); $holidays = $holidays->fetchAll();

$years = $db->query("SELECT DISTINCT YEAR(holiday_date) y FROM holidays ORDER BY y")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array((string)$year, array_map('strval', $years)) && !in_array($year, $years)) $years[] = $year;
sort($years);

$typeLabels = ['resmi' => ['🇹🇷 Resmi', '#1a7a3a'], 'dini' => ['🌙 Dini Bayram', '#c9a227'], 'ozel' => ['⭐ Özel Gün', '#7c3aed']];
$gunAdlari = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];

$page_title = 'Resmi Tatiller';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

  <div class="card">
    <div class="card-header">
      <span class="card-title">🗓️ Resmi &amp; Dini Tatiller</span>
      <form method="get" style="display:flex;gap:6px;align-items:center">
        <select name="year" class="form-control" style="font-size:13px;padding:4px 8px;height:auto" onchange="this.form.submit()">
          <?php foreach ($years as $y): ?><option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
        </select>
      </form>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Tarih</th><th>Tatil Adı</th><th>Tür</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($holidays as $h): $tl = $typeLabels[$h['type']] ?? $typeLabels['resmi']; ?>
          <tr>
            <td><strong><?= date('d.m.Y', strtotime($h['holiday_date'])) ?></strong><br><small style="color:#94a3b8"><?= $gunAdlari[(int)date('w', strtotime($h['holiday_date']))] ?></small></td>
            <td><?= sanitize($h['name']) ?></td>
            <td><span class="badge" style="background:<?= $tl[1] ?>1a;color:<?= $tl[1] ?>;border:1px solid <?= $tl[1] ?>"><?= $tl[0] ?></span></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="holiday_id" value="<?= $h['id'] ?>">
                <button name="delete_holiday" class="btn btn-sm btn-danger" onclick="return confirm('Bu tatili silmek istiyor musunuz?')">🗑️</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($holidays)): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">🗓️</div><div class="empty-state-title"><?= $year ?> yılı için tatil kaydı yok</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">➕ Yeni Tatil Ekle</span></div>
    <div class="card-body">
      <form method="post">
        <div class="form-group"><label class="form-label">Tatil Adı *</label><input type="text" name="name" class="form-control" placeholder="ör. Ramazan Bayramı 1. Gün" required></div>
        <div class="form-group"><label class="form-label">Tarih *</label><input type="date" name="holiday_date" class="form-control" required></div>
        <div class="form-group">
          <label class="form-label">Tür</label>
          <select name="type" class="form-control">
            <option value="resmi">🇹🇷 Resmi Tatil</option>
            <option value="dini">🌙 Dini Bayram</option>
            <option value="ozel">⭐ Özel Gün</option>
          </select>
        </div>
        <button name="add_holiday" class="btn btn-primary btn-block">🗓️ Tatili Ekle</button>
      </form>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>
