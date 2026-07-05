<?php
require_once '../config/db.php';
session_start_safe();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $district  = trim($_POST['district'] ?? '');
    $city      = trim($_POST['city'] ?? 'İstanbul');
    $imam_name = trim($_POST['imam_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $capacity  = (int)($_POST['capacity'] ?? 50);

    if (!$name || !$username || !$password) {
        $error = 'Cami adı, kullanıcı adı ve şifre zorunludur.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $db = getDB();
        $chk = $db->prepare("SELECT id FROM mosques WHERE username=?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            $error = 'Bu kullanıcı adı zaten kullanılıyor.';
        } else {
            $stmt = $db->prepare("INSERT INTO mosques (name,address,district,city,imam_name,phone,email,username,password,capacity,status) VALUES (?,?,?,?,?,?,?,?,?,?,'pending')");
            $stmt->execute([$name,$address,$district,$city,$imam_name,$phone,$email,$username,hashPassword($password),$capacity]);
            $success = 'Cami kaydınız alındı! Yönetici onayı sonrası giriş yapabilirsiniz.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cami Kaydı · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="register-page">
  <div class="register-box" style="max-width:600px">
    <div class="register-box-header">
      <div class="mosque-icon">🕌</div>
      <h1>Cami Kaydı</h1>
      <p>Yeni cami kaydınızı oluşturun. Admin onayı sonrası aktif olacaktır.</p>
    </div>
    <div class="register-form-body">
      <?php if ($success): ?>
      <div class="alert alert-success">
        ✅ <?= sanitize($success) ?>
        <br><a href="login.php" style="color:inherit;font-weight:700">→ Giriş Sayfasına Git</a>
      </div>
      <?php else: ?>

      <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

      <form method="post">
        <div class="section-title">🕌 Cami Bilgileri</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Cami Adı *</label>
            <input type="text" name="name" class="form-control" placeholder="Merkez Camii" required value="<?= sanitize($_POST['name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">İmam Adı</label>
            <input type="text" name="imam_name" class="form-control" placeholder="Ahmet Yılmaz" value="<?= sanitize($_POST['imam_name'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Adres</label>
          <textarea name="address" class="form-control" placeholder="Mahalle, sokak, kapı no..."><?= sanitize($_POST['address'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">İlçe</label>
            <input type="text" name="district" class="form-control" placeholder="Kadıköy" value="<?= sanitize($_POST['district'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Şehir</label>
            <input type="text" name="city" class="form-control" placeholder="İstanbul" value="<?= sanitize($_POST['city'] ?? 'İstanbul') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Telefon</label>
            <input type="tel" name="phone" class="form-control" placeholder="0212 xxx xx xx" value="<?= sanitize($_POST['phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" placeholder="cami@ornek.com" value="<?= sanitize($_POST['email'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Öğrenci Kapasitesi</label>
          <input type="number" name="capacity" class="form-control" value="<?= (int)($_POST['capacity'] ?? 50) ?>" min="1" max="500">
        </div>

        <div class="section-title" style="margin-top:24px">🔐 Giriş Bilgileri</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Kullanıcı Adı *</label>
            <input type="text" name="username" class="form-control" placeholder="merkez_camii" required value="<?= sanitize($_POST['username'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Şifre * (en az 6 karakter)</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Şifre Tekrar *</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <div class="alert alert-info">ℹ️ Kaydınız admin tarafından onaylandıktan sonra giriş yapabilirsiniz.</div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">🕌 Camiyi Kaydet</button>
      </form>
      <?php endif; ?>

      <div style="text-align:center;margin-top:20px;font-size:13px;color:#64748b">
        Zaten hesabınız var mı? <a href="login.php" style="color:#1a7a3a;font-weight:700">Giriş Yap →</a>
        <br><br>
        <a href="../index.php" style="color:#94a3b8">← Ana Sayfaya Dön</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
