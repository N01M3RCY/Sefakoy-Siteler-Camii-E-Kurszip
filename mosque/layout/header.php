<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($page_title ?? 'Cami Paneli') ?> · Cami Sistemi</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">🕌</div>
      <div class="sidebar-title"><?= sanitize($_SESSION['mosque_name'] ?? 'Cami Paneli') ?></div>
      <div class="sidebar-subtitle">Cami Yönetim Paneli</div>
      <span class="sidebar-badge">İMAM</span>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Genel</div>
      <a href="index.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📊</span> Kontrol Paneli
      </a>

      <div class="sidebar-section">Tüm Öğrenciler</div>
      <a href="students.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='students.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📚</span> Tüm Öğrenciler
      </a>
      <a href="add_student.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='add_student.php'?'active':'' ?>">
        <span class="sidebar-link-icon">➕</span> Öğrenci Ekle
      </a>
      <a href="attendance.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='attendance.php'?'active':'' ?>">
        <span class="sidebar-link-icon">✅</span> Yoklama Al
      </a>

      <div class="sidebar-section">Kurs Yönetimi</div>
      <a href="teachers.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='teachers.php'?'active':'' ?>">
        <span class="sidebar-link-icon">👨‍🏫</span> Hocalar
      </a>
      <a href="courses.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='courses.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📋</span> Kurslar & Gruplar
      </a>

      <div class="sidebar-section">Eğitim</div>
      <a href="homeworks.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='homeworks.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📝</span> Ödevler
      </a>
      <a href="duas.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='duas.php'?'active':'' ?>">
        <span class="sidebar-link-icon">🤲</span> Dua Sistemi
      </a>

      <div class="sidebar-section">İletişim</div>
      <a href="announcements.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF'])==='announcements.php'?'active':'' ?>">
        <span class="sidebar-link-icon">📢</span> Duyurular
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
      Veli kaydı için:<br>
      <a href="../register.php" style="color:rgba(255,255,255,.5)">Kayıt Formu →</a>
    </div>
  </aside>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><?= sanitize($page_title ?? '') ?></div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="topbar-avatar">🕌</div>
          <span><?= sanitize($_SESSION['mosque_name'] ?? '') ?></span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-secondary">Çıkış</a>
      </div>
    </div>
    <div class="page-content">
