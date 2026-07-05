<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db  = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

// Hoca Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !$username || !$password) { $error = 'Ad, kullanıcı adı ve şifre zorunludur.'; }
    elseif (strlen($password) < 6) { $error = 'Şifre en az 6 karakter olmalıdır.'; }
    else {
        $chk = $db->prepare("SELECT id FROM teachers WHERE username=?"); $chk->execute([$username]);
        if ($chk->fetch()) { $error = 'Bu kullanıcı adı zaten kullanılıyor.'; }
        else {
            $db->prepare("INSERT INTO teachers (mosque_id,name,username,password) VALUES (?,?,?,?)")
               ->execute([$mid, $name, $username, hashPassword($password)]);
            $success = "Hoca hesabı oluşturuldu. Giriş: $username / $password";
        }
    }
}

// Durum değiştir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_teacher'])) {
    $t = $db->prepare("SELECT status FROM teachers WHERE id=? AND mosque_id=?");
    $t->execute([(int)$_POST['teacher_id'], $mid]); $cur = $t->fetchColumn();
    $db->prepare("UPDATE teachers SET status=? WHERE id=? AND mosque_id=?")->execute([$cur==='active'?'inactive':'active', (int)$_POST['teacher_id'], $mid]);
    $success = 'Durum güncellendi.';
}

// Sil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_teacher'])) {
    $db->prepare("DELETE FROM teachers WHERE id=? AND mosque_id=?")->execute([(int)$_POST['teacher_id'], $mid]);
    $success = 'Hoca silindi.';
}

$teachers = $db->prepare("SELECT t.*, c.name AS course_name FROM teachers t LEFT JOIN courses c ON c.teacher_id=t.id AND c.mosque_id=t.mosque_id WHERE t.mosque_id=? ORDER BY t.created_at DESC");
$teachers->execute([$mid]); $teachers = $teachers->fetchAll();

$page_title = 'Hocalar';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

  <!-- Ekle Formu -->
  <div class="card">
    <div class="card-header"><span class="card-title">➕ Hoca Hesabı Oluştur</span></div>
    <div class="card-body">
      <form method="post">
        <div class="form-group"><label class="form-label">Ad Soyad *</label><input type="text" name="name" class="form-control" placeholder="Ahmet Yılmaz" required></div>
        <div class="form-group"><label class="form-label">Kullanıcı Adı *</label><input type="text" name="username" class="form-control" placeholder="hoca_ahmet" required></div>
        <div class="form-group"><label class="form-label">Şifre * (min 6 karakter)</label><input type="password" name="password" class="form-control" minlength="6" required></div>
        <div class="alert alert-info" style="margin-bottom:16px">ℹ️ Hoca, <strong>/teacher/login.php</strong> adresinden giriş yapar.</div>
        <button name="add_teacher" class="btn btn-primary btn-block">👨‍🏫 Hoca Hesabı Oluştur</button>
      </form>
    </div>
  </div>

  <!-- Liste -->
  <div class="card">
    <div class="card-header"><span class="card-title">👨‍🏫 Hocalar (<?= count($teachers) ?>)</span></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Ad</th><th>Kullanıcı Adı</th><th>Kurs</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($teachers as $t): ?>
          <tr>
            <td><strong><?= sanitize($t['name']) ?></strong></td>
            <td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?= sanitize($t['username']) ?></code></td>
            <td><?= $t['course_name'] ? '<span class="badge badge-success">'.sanitize($t['course_name']).'</span>' : '<span style="color:#94a3b8;font-size:12px">—</span>' ?></td>
            <td><span class="badge <?= $t['status']==='active'?'badge-success':'badge-danger' ?>"><?= $t['status']==='active'?'✅ Aktif':'❌ Pasif' ?></span></td>
            <td>
              <div style="display:flex;gap:4px">
                <form method="post" style="display:inline"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>"><button name="toggle_teacher" class="btn btn-sm btn-secondary"><?= $t['status']==='active'?'⏸️':'▶️' ?></button></form>
                <form method="post" style="display:inline"><input type="hidden" name="teacher_id" value="<?= $t['id'] ?>"><button name="delete_teacher" class="btn btn-sm btn-danger" onclick="return confirm('Hocayı silmek istediğinizden emin misiniz?')">🗑️</button></form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($teachers)): ?><tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">👨‍🏫</div><div class="empty-state-title">Henüz hoca eklenmedi</div></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?php include 'layout/footer.php'; ?>
