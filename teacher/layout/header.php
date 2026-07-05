<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($page_title ?? 'Hoca Paneli') ?> · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">👨‍🏫</div>
      <div class="sidebar-title"><?= sanitize($_SESSION['teacher_name'] ?? 'Hoca Paneli') ?></div>
      <div class="sidebar-subtitle"><?= sanitize($_SESSION['teacher_mosque_name'] ?? '') ?></div>
      <span class="sidebar-badge" style="background:#8b5cf6">HOCA</span>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Genel</div>
      <a href="index.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📊</span> Kontrol Paneli
      </a>
      <div class="sidebar-section">Öğrencilerim</div>
      <a href="students.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='students.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📚</span> Öğrenciler
      </a>
      <a href="attendance.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='attendance.php'?'active':'' ?>">
        <span class="sidebar-link-icon">✅</span> Yoklama Al
      </a>
      <div class="sidebar-section">Eğitim İçeriği</div>
      <a href="memorizations.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='memorizations.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📖</span> Sure / Dua Ezberi
      </a>
      <a href="homeworks.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='homeworks.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📝</span> Ödevler
      </a>
      <a href="duas.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='duas.php'?'active':'' ?>">
        <span class="sidebar-link-icon">🤲</span> Dua Sistemi
      </a>
      <div class="sidebar-section">Hesap</div>
      <a href="change_password.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='change_password.php'?'active':'' ?>">
        <span class="sidebar-link-icon">🔑</span> Şifre Değiştir
      </a>
      <a href="logout.php" class="sidebar-link">
        <span class="sidebar-link-icon">🚪</span> Çıkış Yap
      </a>
    </nav>
  </aside>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><?= sanitize($page_title ?? '') ?></div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="topbar-avatar" style="background:#ede9fe;border-color:#8b5cf6">👨‍🏫</div>
          <span><?= sanitize($_SESSION['teacher_name'] ?? '') ?></span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-secondary">Çıkış</a>
      </div>
    </div>
    <div class="page-content">
