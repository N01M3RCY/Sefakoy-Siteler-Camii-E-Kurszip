<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';
$search  = trim($_GET['search'] ?? '');
$gender  = $_GET['gender'] ?? '';

// Durum değiştir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $sid = (int)$_POST['student_id'];
    $stmt = $db->prepare("SELECT status FROM students WHERE id=? AND mosque_id=?");
    $stmt->execute([$sid, $mid]);
    $cur = $stmt->fetchColumn();
    $new = $cur === 'active' ? 'inactive' : 'active';
    $db->prepare("UPDATE students SET status=? WHERE id=? AND mosque_id=?")->execute([$new, $sid, $mid]);
    $success = 'Öğrenci durumu güncellendi.';
}

$where = "s.mosque_id = ?"; $params = [$mid];
if ($search) {
    $where .= " AND (s.name LIKE ? OR s.surname LIKE ? OR s.qr_code LIKE ? OR p.name LIKE ? OR p.phone LIKE ?)";
    $sv = "%$search%"; array_push($params, $sv, $sv, $sv, $sv, $sv);
}
if ($gender) { $where .= " AND s.gender=?"; $params[] = $gender; }

$stmt = $db->prepare("
    SELECT s.*, p.name AS p_name, p.surname AS p_surname, p.phone AS p_phone, p.email AS p_email
    FROM students s LEFT JOIN parents p ON s.parent_id = p.id
    WHERE $where ORDER BY s.created_at DESC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$page_title = 'Öğrencilerim';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="get" style="display:flex;gap:8px;flex:1;flex-wrap:wrap">
      <input type="text" name="search" class="form-control" placeholder="🔍 Ad, soyad, QR, veli tel..." value="<?= sanitize($search) ?>" style="max-width:280px">
      <select name="gender" class="form-control" style="max-width:140px">
        <option value="">Tüm Cinsiyet</option>
        <option value="male"   <?= $gender==='male'  ?'selected':'' ?>>Erkek</option>
        <option value="female" <?= $gender==='female'?'selected':'' ?>>Kız</option>
      </select>
      <button class="btn btn-primary">Filtrele</button>
      <?php if ($search||$gender): ?><a href="students.php" class="btn btn-secondary">Temizle</a><?php endif; ?>
    </form>
    <button onclick="window.print()" class="btn btn-secondary no-print">🖨️ Yazdır</button>
    <a href="../register.php" target="_blank" class="btn btn-gold no-print">📋 Kayıt Formu</a>
    <a href="export.php?type=students&format=xls" class="btn btn-secondary no-print">📥 Excel</a>
    <a href="export.php?type=students&format=csv" class="btn btn-secondary no-print">📄 CSV</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">📚 Öğrenciler (<?= count($students) ?>)</span>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>#</th><th>Öğrenci</th><th>Doğum Tarihi</th><th>Veli Bilgisi</th><th>QR Kod</th><th>Durum</th><th>İşlemler</th></tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td style="font-size:12px;color:#94a3b8"><?= $s['id'] ?></td>
          <td>
            <strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong><br>
            <span class="badge <?= $s['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:11px"><?= $s['gender']==='male'?'Erkek':'Kız' ?></span>
            <?php if ($s['tc_no']): ?><small style="color:#94a3b8"> · TC: <?= sanitize($s['tc_no']) ?></small><?php endif; ?>
          </td>
          <td><?= $s['birth_date'] ? date('d.m.Y',strtotime($s['birth_date'])) : '—' ?></td>
          <td>
            <strong><?= sanitize($s['p_name'].' '.$s['p_surname']) ?></strong><br>
            <small>📞 <?= sanitize($s['p_phone']) ?></small>
            <?php if ($s['p_email']): ?><br><small>✉️ <?= sanitize($s['p_email']) ?></small><?php endif; ?>
          </td>
          <td>
            <div class="qr-wrap">
              <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=<?= urlencode($s['qr_code']) ?>" width="60" height="60" style="border-radius:6px;border:2px solid #e2e8f0">
              </a>
              <span class="qr-code-text"><?= sanitize($s['qr_code']) ?></span>
            </div>
          </td>
          <td>
            <span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>">
              <?= $s['status']==='active'?'✅ Aktif':'❌ Pasif' ?>
            </span>
            <?php if ($s['notes']): ?><br><small style="color:#94a3b8"><?= sanitize($s['notes']) ?></small><?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-primary" title="QR Kart Görüntüle">🪪</a>
              <form method="post" style="display:inline">
                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                <button name="toggle_status" class="btn btn-sm btn-secondary" title="Durumu Değiştir" onclick="return confirm('Öğrenci durumunu değiştirmek istiyor musunuz?')">
                  <?= $s['status']==='active'?'⏸️':'▶️' ?>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div class="empty-state-title">Öğrenci bulunamadı</div>
            <div class="empty-state-desc">Velileri <a href="../register.php" target="_blank" style="color:#1a7a3a">kayıt formu</a> üzerinden yönlendirin.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'layout/footer.php'; ?>
