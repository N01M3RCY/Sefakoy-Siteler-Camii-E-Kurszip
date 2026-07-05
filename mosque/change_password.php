<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $mosque = $db->prepare("SELECT * FROM mosques WHERE id=?");
    $mosque->execute([$mid]);
    $mosque = $mosque->fetch();

    if (!verifyPassword($current, $mosque['password'])) {
        $error = 'Mevcut şifre hatalı.';
    } elseif (strlen($new) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalıdır.';
    } elseif ($new !== $confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $db->prepare("UPDATE mosques SET password=? WHERE id=?")->execute([hashPassword($new), $mid]);
        $success = 'Şifreniz başarıyla güncellendi.';
    }
}

$page_title = 'Şifre Değiştir';
include 'layout/header.php';
?>
<div style="max-width:480px">
  <div class="card">
    <div class="card-header"><span class="card-title">🔑 Şifre Değiştir</span></div>
    <div class="card-body">
      <?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="form-group">
          <label class="form-label">Mevcut Şifre</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Yeni Şifre</label>
          <input type="password" name="new_password" class="form-control" minlength="6" required>
        </div>
        <div class="form-group">
          <label class="form-label">Yeni Şifre (Tekrar)</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Şifreyi Güncelle</button>
      </form>
    </div>
  </div>
</div>
<?php include 'layout/footer.php'; ?>
