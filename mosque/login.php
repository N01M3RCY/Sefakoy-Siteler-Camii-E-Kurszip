<?php
require_once '../config/db.php';
session_start_safe();

if (isset($_SESSION['mosque_id'])) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM mosques WHERE username=?");
        $stmt->execute([$username]);
        $mosque = $stmt->fetch();

        if ($mosque && verifyPassword($password, $mosque['password'])) {
            if ($mosque['status'] === 'pending') {
                $error = 'Hesabınız henüz onaylanmamış. Lütfen admin onayını bekleyin.';
            } elseif ($mosque['status'] === 'inactive') {
                $error = 'Hesabınız devre dışı bırakılmış. Lütfen yönetici ile iletişime geçin.';
            } else {
                session_regenerate_id(true);
                $_SESSION['mosque_id']   = $mosque['id'];
                $_SESSION['mosque_name'] = $mosque['name'];
                redirect('index.php');
            }
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı.';
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
<title>Cami Girişi · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-box">
    <div class="auth-header">
      <div class="auth-icon">🕌</div>
      <div class="auth-title">Cami Paneli Girişi</div>
      <div class="auth-subtitle">Cami hesabınızla giriş yapın</div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label class="form-label">Kullanıcı Adı</label>
        <input type="text" name="username" class="form-control" placeholder="camii_kullanici_adi" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Giriş Yap</button>
    </form>

    <div class="auth-footer" style="margin-top:16px">
      Camini kaydetmek ister misin? <a href="register.php">Cami Kaydı →</a><br><br>
      <a href="../index.php">← Ana Sayfaya Dön</a>
    </div>
  </div>
</div>
</body>
</html>
