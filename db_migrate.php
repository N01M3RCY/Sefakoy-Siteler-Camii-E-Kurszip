<?php
/**
 * Veritabanı Güncelleme Scripti
 * Mevcut kuruluma yeni kolonları ekler.
 * Güvenlik: Aktif admin oturumu gerektirir.
 * Çalıştırdıktan sonra bu dosyayı silin.
 */
require_once 'config/db.php';
session_start_safe();

// Admin oturumu zorunlu
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:30px;background:#fee2e2;max-width:500px;margin:50px auto;border-radius:12px">
        <h2 style="color:#dc2626">🔒 Yetkisiz Erişim</h2>
        <p>Bu scripti çalıştırmak için önce <a href="admin/login.php">admin girişi</a> yapın.</p>
    </div>');
}

// POST onayı gerekli (GET sadece form gösterir)
if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    echo \'<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><link rel="stylesheet" href="assets/css/style.css"></head><body>
    <div style="max-width:500px;margin:60px auto">
      <div class="card"><div class="card-body">
        <h2 style="color:#dc2626;margin-bottom:12px">⚠️ Veritabanı Güncelleme</h2>
        <p style="margin-bottom:20px;color:#64748b">Bu işlem veritabanı şemasını güncelleyecektir. Devam etmek istediğinizden emin misiniz?</p>
        <form method="post">
          <button type="submit" class="btn btn-danger btn-block">Güncellemeyi Çalıştır</button>
        </form>
        <a href="admin/index.php" style="display:block;text-align:center;margin-top:12px;color:#64748b">← İptal</a>
      </div></div>
    </div></body></html>\';
    exit;
}

require_once 'config/db.php';
$db = getDB();

$migrations = [];

// 1. parents tablosuna password kolonu ekle
try {
    $db->exec("ALTER TABLE parents ADD COLUMN password VARCHAR(255) NULL AFTER email");
    $migrations[] = ['✅', 'parents.password kolonu eklendi'];
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = ['ℹ️', 'parents.password kolonu zaten var (atlandı)'];
    } else {
        $migrations[] = ['❌', 'parents.password: ' . $e->getMessage()];
    }
}

// 2. parents tablosuna last_login ekle
try {
    $db->exec("ALTER TABLE parents ADD COLUMN last_login TIMESTAMP NULL AFTER password");
    $migrations[] = ['✅', 'parents.last_login kolonu eklendi'];
} catch (PDOException $e) {
    $migrations[] = [str_contains($e->getMessage(),'Duplicate')?'ℹ️':'❌', 'parents.last_login: ' . (str_contains($e->getMessage(),'Duplicate')?'zaten var':'HATA: '.$e->getMessage())];
}

// Sonuçlar
echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">
<style>body{font-family:sans-serif;padding:30px;background:#f0f7f0}
.box{background:#fff;max-width:600px;margin:0 auto;border-radius:12px;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
h2{color:#0d5c2e}li{padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
.btn{display:inline-block;background:#0d5c2e;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;margin-top:16px;font-weight:bold}
</style></head><body><div class="box">
<h2>🔄 Veritabanı Güncelleme</h2>
<ul>';
foreach ($migrations as [$icon, $msg]) {
    echo "<li>$icon $msg</li>";
}
echo '</ul>
<div style="background:#fef9c3;padding:14px;border-radius:8px;margin-top:16px;font-size:13px">
⚠️ <strong>Güvenlik:</strong> Bu dosyayı (db_migrate.php) sunucudan silin!
</div>
<a href="index.php" class="btn">🏠 Ana Sayfaya Dön</a>
</div></body></html>';
