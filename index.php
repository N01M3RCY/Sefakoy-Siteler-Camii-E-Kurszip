<?php
require_once 'config/db.php';
session_start_safe();

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['admin_id'])) redirect('admin/index.php');
if (isset($_SESSION['mosque_id'])) redirect('mosque/index.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cami Öğrenci Yönetim Sistemi</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="landing-page">
  <div class="landing-logo">
    <span class="mosque-icon">🕌</span>
    <h1 class="landing-title">Cami Öğrenci Yönetim Sistemi</h1>
    <p class="landing-subtitle">Diyanet İşleri Başkanlığı · Müftülük Hizmetleri</p>
    <div class="landing-divider"></div>
  </div>

  <div class="login-cards">
    <!-- Admin -->
    <a href="admin/login.php" class="login-card">
      <span class="login-card-icon">🔐</span>
      <div class="login-card-title">Sistem Yöneticisi</div>
      <div class="login-card-desc">Tüm camileri, öğrencileri ve kayıtları yönetin. Müftülük düzeyinde tam erişim.</div>
      <span class="login-card-btn">Admin Girişi →</span>
    </a>

    <!-- Cami Kaydı -->
    <a href="mosque/register.php" class="login-card">
      <span class="login-card-icon">🕌</span>
      <div class="login-card-title">Cami Kaydı</div>
      <div class="login-card-desc">Yeni camini sisteme kaydet. Onay sonrası öğrenci ve kayıt işlemlerini yapabilirsin.</div>
      <span class="login-card-btn">Cami Kaydet →</span>
    </a>

    <!-- Cami Girişi -->
    <a href="mosque/login.php" class="login-card">
      <span class="login-card-icon">👤</span>
      <div class="login-card-title">Cami Girişi</div>
      <div class="login-card-desc">Kayıtlı cami hesabınla giriş yap. Öğrenci listesi, devam takibi ve QR kodlarını görüntüle.</div>
      <span class="login-card-btn">Giriş Yap →</span>
    </a>
  </div>

  <div class="register-link-bar" style="margin-top:16px">
    <a href="parent/login.php" style="display:inline-flex;align-items:center;gap:8px;background:rgba(201,162,39,.15);border:1px solid rgba(201,162,39,.4);padding:10px 22px;border-radius:999px;color:#f0d060;font-weight:700;transition:.2s">
      👨‍👩‍👧 Veli Paneli Girişi
    </a>
  </div>
  <div class="register-link-bar" style="margin-top:12px">
    📋 Çocuğunuzu kaydetmek ister misiniz? &nbsp;
    <a href="register.php">Veli Kayıt Formu →</a>
  </div>

  <div style="margin-top:32px;color:rgba(255,255,255,.3);font-size:12px;text-align:center;">
    Cami Öğrenci Yönetim Sistemi &copy; <?= date('Y') ?> · Tüm hakları saklıdır
  </div>
</div>
</body>
</html>
