<?php
require_once '../config/db.php';
requireLogin('parent', 'login.php');
$db  = getDB();
$pid = $_SESSION['parent_id'];

$student_filter = (int)($_GET['student'] ?? 0);
$month = $_GET['month'] ?? date('Y-m');

// Bu velinin öğrencileri
$students = $db->prepare("SELECT s.id, s.name, s.surname FROM students s WHERE s.parent_id=? ORDER BY s.name");
$students->execute([$pid]);
$students = $students->fetchAll();

// Seçili öğrenci yoksa ilkini seç
if (!$student_filter && !empty($students)) $student_filter = $students[0]['id'];

// Öğrenci bu veliye ait mi?
$valid = false;
foreach ($students as $s) { if ($s['id'] === $student_filter) { $valid = true; break; } }
if (!$valid) $student_filter = $students[0]['id'] ?? 0;

// Yoklama verileri
$records = $db->prepare("
    SELECT a.scan_date, a.scan_time, m.name AS mosque_name
    FROM attendance a JOIN mosques m ON a.mosque_id=m.id
    WHERE a.student_id=? AND DATE_FORMAT(a.scan_date,'%Y-%m')=?
    ORDER BY a.scan_date DESC
");
$records->execute([$student_filter, $month]);
$records = $records->fetchAll();

// Bu ayki toplam iş günü (kabaca)
$days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)substr($month,5,2), (int)substr($month,0,4));

$page_title = 'Devam Durumu';
include 'layout/header.php';
?>

<!-- Filtreler -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
      <?php if (count($students) > 1): ?>
      <div>
        <label class="form-label" style="margin-bottom:4px">Öğrenci</label>
        <select name="student" class="form-control" style="max-width:220px">
          <?php foreach ($students as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $s['id']===$student_filter?'selected':'' ?>><?= sanitize($s['name'].' '.$s['surname']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <input type="hidden" name="student" value="<?= $student_filter ?>">
      <?php endif; ?>
      <div>
        <label class="form-label" style="margin-bottom:4px">Ay</label>
        <input type="month" name="month" class="form-control" value="<?= sanitize($month) ?>" style="max-width:160px">
      </div>
      <div style="align-self:flex-end"><button class="btn btn-primary">Göster</button></div>
    </form>
  </div>
</div>

<!-- Özet -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div><div class="stat-value"><?= count($records) ?></div><div class="stat-label">Bu Ay Katılım</div></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon">📅</div>
    <div><div class="stat-value"><?= $days_in_month - count($records) ?></div><div class="stat-label">Devamsız Gün</div></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">📊</div>
    <div><div class="stat-value">%<?= $days_in_month > 0 ? round(count($records)/$days_in_month*100) : 0 ?></div><div class="stat-label">Katılım Oranı</div></div>
  </div>
</div>

<!-- Takvim görünümü -->
<?php
$year  = (int)substr($month,0,4);
$mon   = (int)substr($month,5,2);
$attended = array_column($records, 'scan_date');
$attended = array_map(fn($d) => substr($d,0,10), $attended);
?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">📅 <?= date('F Y', strtotime($month.'-01')) ?> Takvimi</span></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;text-align:center">
      <?php foreach (['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $d): ?>
        <div style="font-size:11px;font-weight:700;color:#94a3b8;padding:4px"><?= $d ?></div>
      <?php endforeach; ?>
      <?php
      $first_day = date('N', strtotime("$year-$mon-01")); // 1=Mon, 7=Sun
      for ($i = 1; $i < $first_day; $i++) echo '<div></div>';
      for ($day = 1; $day <= $days_in_month; $day++):
          $date_str = sprintf('%04d-%02d-%02d', $year, $mon, $day);
          $came = in_array($date_str, $attended);
          $today = $date_str === date('Y-m-d');
      ?>
        <div style="padding:8px 4px;border-radius:8px;font-size:14px;font-weight:700;
             background:<?= $came?'#dcfce7':($today?'#dbeafe':'#f8fafc') ?>;
             color:<?= $came?'#15803d':($today?'#1d4ed8':'#64748b') ?>;
             border:<?= $today?'2px solid #3b82f6':'1px solid #e2e8f0' ?>">
          <?= $day ?>
          <?php if ($came): ?><div style="font-size:8px">✅</div><?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- Liste -->
<?php if (!empty($records)): ?>
<div class="card">
  <div class="card-header"><span class="card-title">📋 Devam Listesi</span></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Tarih</th><th>Gün</th><th>Saat</th><th>Cami</th></tr></thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td><strong><?= date('d.m.Y',strtotime($r['scan_date'])) ?></strong></td>
          <td><?= ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'][date('N',strtotime($r['scan_date']))-1] ?></td>
          <td><?= substr($r['scan_time'],0,5) ?></td>
          <td><?= sanitize($r['mosque_name']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
