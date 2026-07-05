<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($page_title ?? 'Veli Paneli') ?> · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">👨‍👩‍👧</div>
      <div class="sidebar-title">Veli Paneli</div>
      <div class="sidebar-subtitle"><?= sanitize(($_SESSION['parent_name'] ?? '').' '.($_SESSION['parent_surname'] ?? '')) ?></div>
      <span class="sidebar-badge" style="background:#c9a227">VELİ</span>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Genel</div>
      <a href="index.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📊</span> Kontrol Paneli
      </a>
      <div class="sidebar-section">Çocuklarım</div>
      <a href="students.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='students.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📚</span> Öğrenci Bilgileri
      </a>
      <a href="attendance.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='attendance.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📅</span> Devam Durumu
      </a>
      <a href="homeworks.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='homeworks.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📝</span> Ödevler
      </a>
      <div class="sidebar-section">Hesap</div>
      <a href="change_password.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='change_password.php'?'active':'' ?>">
        <span class="sidebar-link-icon">🔑</span> Şifre Değiştir
      </a>
      <a href="logout.php" class="sidebar-link">
        <span class="sidebar-link-icon">🚪</span> Çıkış Yap
      </a>
    </nav>
    <div class="sidebar-footer" style="color:rgba(255,255,255,.4);font-size:11px;text-align:center">
      Yeni öğrenci kaydı için:<br>
      <a href="../register.php" style="color:rgba(255,255,255,.5)">Kayıt Formu →</a>
    </div>
  </aside>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><?= sanitize($page_title ?? '') ?></div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="topbar-avatar" style="background:#fef9c3;border-color:#c9a227">👨‍👩‍👧</div>
          <span><?= sanitize(($_SESSION['parent_name'] ?? '').' '.($_SESSION['parent_surname'] ?? '')) ?></span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-secondary">Çıkış</a>
      </div>
    </div>
    <div class="page-content">
