<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $con = $_POST['confirm_password'] ?? '';
    $t = $db->prepare("SELECT password FROM teachers WHERE id=?");
    $t->execute([$tid]); $hash = $t->fetchColumn();
    if (!verifyPassword($old, $hash)) { $error = 'Mevcut şifre hatalı.'; }
    elseif (strlen($new) < 6)         { $error = 'Yeni şifre en az 6 karakter olmalıdır.'; }
    elseif ($new !== $con)            { $error = 'Şifreler eşleşmiyor.'; }
    else {
        $db->prepare("UPDATE teachers SET password=? WHERE id=?")->execute([hashPassword($new), $tid]);
        $success = 'Şifreniz başarıyla güncellendi.';
    }
}
$page_title = 'Şifre Değiştir';
include 'layout/header.php';
?>
<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
<div class="card" style="max-width:480px">
  <div class="card-header"><span class="card-title">🔑 Şifre Değiştir</span></div>
  <div class="card-body">
    <form method="post">
      <div class="form-group"><label class="form-label">Mevcut Şifre</label><input type="password" name="old_password" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Yeni Şifre</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
      <div class="form-group"><label class="form-label">Yeni Şifre Tekrar</label><input type="password" name="confirm_password" class="form-control" required></div>
      <button type="submit" class="btn btn-primary btn-block">🔑 Şifreyi Güncelle</button>
    </form>
  </div>
</div>
<?php include 'layout/footer.php'; ?>
