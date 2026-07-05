<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($page_title ?? 'Admin') ?> · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">🕌</div>
      <div class="sidebar-title">Cami Yönetim Sistemi</div>
      <div class="sidebar-subtitle">Müftülük Admin Paneli</div>
      <span class="sidebar-badge">YÖNETİCİ</span>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Genel</div>
      <a href="index.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
        <span class="sidebar-link-icon">📊</span> Kontrol Paneli
      </a>

      <div class="sidebar-section">Yönetim</div>
      <a href="mosques.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'mosques.php' ? 'active' : '' ?>">
        <span class="sidebar-link-icon">🕌</span> Camiler
      </a>
      <a href="students.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : '' ?>">
        <span class="sidebar-link-icon">📚</span> Öğrenciler
      </a>
      <a href="parents.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'parents.php' ? 'active' : '' ?>">
        <span class="sidebar-link-icon">👨‍👩‍👧</span> Veliler
      </a>

      <div class="sidebar-section">Hesap</div>
      <a href="change_password.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'change_password.php' ? 'active' : '' ?>">
        <span class="sidebar-link-icon">🔑</span> Şifre Değiştir
      </a>
      <a href="logout.php" class="sidebar-link">
        <span class="sidebar-link-icon">🚪</span> Çıkış Yap
      </a>
    </nav>
    <div class="sidebar-footer" style="color:rgba(255,255,255,.4);font-size:12px;text-align:center">
      Giriş: <?= sanitize($_SESSION['admin_name'] ?? '') ?>
    </div>
  </aside>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><?= sanitize($page_title ?? '') ?></div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="topbar-avatar">👤</div>
          <span><?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-secondary">Çıkış</a>
      </div>
    </div>
    <div class="page-content">
