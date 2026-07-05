<?php
require_once '../config/db.php';
session_start_safe();
if (isset($_SESSION['teacher_id'])) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT t.*, m.name AS mosque_name FROM teachers t JOIN mosques m ON t.mosque_id = m.id WHERE t.username = ? AND t.status = 'active' AND m.status = 'active'");
        $stmt->execute([$username]);
        $teacher = $stmt->fetch();
        if ($teacher && verifyPassword($password, $teacher['password'])) {
            session_regenerate_id(true);
            $_SESSION['teacher_id']         = $teacher['id'];
            $_SESSION['teacher_name']        = $teacher['name'];
            $_SESSION['teacher_mosque_id']   = $teacher['mosque_id'];
            $_SESSION['teacher_mosque_name'] = $teacher['mosque_name'];
            redirect('index.php');
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
<title>Hoca Girişi · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="register-page">
  <div class="register-box">
    <div class="register-box-header">
      <div class="mosque-icon">👨‍🏫</div>
      <h1>Hoca Girişi</h1>
      <p>Hoca hesabınızla giriş yapın</p>
    </div>
    <div class="register-form-body">
      <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="form-group">
          <label class="form-label">Kullanıcı Adı</label>
          <input type="text" name="username" class="form-control" placeholder="hoca_username" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Şifre</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">👨‍🏫 Giriş Yap</button>
      </form>
      <div style="text-align:center;margin-top:20px;font-size:13px;color:#64748b">
        <a href="../index.php" style="color:#1a7a3a">← Ana Sayfaya Dön</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
