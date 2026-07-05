<?php
require_once '../config/db.php';
session_start_safe();

if (isset($_SESSION['parent_id'])) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM parents WHERE phone = ? AND password IS NOT NULL AND password != ''");
        $stmt->execute([$phone]);
        $parent = $stmt->fetch();

        if ($parent && verifyPassword($password, $parent['password'])) {
            session_regenerate_id(true);
            $_SESSION['parent_id']      = $parent['id'];
            $_SESSION['parent_name']    = $parent['name'];
            $_SESSION['parent_surname'] = $parent['surname'];
            redirect('index.php');
        } else {
            $error = 'Telefon numarası veya şifre hatalı. Şifre belirlemediyseniz kayıt formundan tekrar kayıt olun.';
        }
    } else {
        $error = 'Tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Veli Girişi · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-box">
    <div class="auth-header">
      <div class="auth-icon">👨‍👩‍👧</div>
      <div class="auth-title">Veli Paneli</div>
      <div class="auth-subtitle">Çocuğunuzun devam ve QR bilgilerini görüntüleyin</div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label class="form-label">Cep Telefonu</label>
        <input type="tel" name="phone" class="form-control" placeholder="Kayıt sırasında kullandığınız numara" required autofocus
               value="<?= sanitize($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg" style="background:#c9a227">Giriş Yap</button>
    </form>

    <div class="auth-footer" style="margin-top:20px">
      <div class="alert alert-info" style="text-align:left;font-size:13px">
        ℹ️ Kayıt sırasında şifre belirlediyseniz giriş yapabilirsiniz.<br>
        Şifreniz yoksa <a href="../register.php" style="color:#1d4ed8;font-weight:700">kayıt formunda</a> yeni bir kayıt yapın.
      </div>
      <a href="../index.php">← Ana Sayfaya Dön</a>
    </div>
  </div>
</div>
</body>
</html>
