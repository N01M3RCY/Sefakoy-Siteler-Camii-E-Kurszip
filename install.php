<?php
// Veritabanı kurulum scripti - Sadece bir kez çalıştırın!
// KURULUM SONRASI BU DOSYAYI SİLİN!

// Güvenlik: cami_kurulum_tamam.lock dosyası varsa engelle
if (file_exists(__DIR__ . '/cami_kurulum_tamam.lock')) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:30px;background:#fee2e2;max-width:500px;margin:50px auto;border-radius:12px;border:1px solid #fca5a5">
        <h2 style="color:#dc2626">🔒 Kurulum Zaten Tamamlandı</h2>
        <p>Bu scripti ikinci kez çalıştıramazsınız. Güvenlik için install.php dosyasını silin.</p>
        <a href="index.php" style="color:#dc2626">← Ana Sayfaya Dön</a>
    </div>');
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cami_sistemi');

$adminUser = 'admin';
$adminPass = 'admin123'; // Kurulumdan sonra değiştirin!

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mosques (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        address TEXT,
        district VARCHAR(100),
        city VARCHAR(100) DEFAULT 'İstanbul',
        imam_name VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        capacity INT DEFAULT 50,
        status ENUM('active','inactive','pending') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        surname VARCHAR(100) NOT NULL,
        tc_no VARCHAR(11),
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mosque_id INT NOT NULL,
        parent_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        surname VARCHAR(100) NOT NULL,
        tc_no VARCHAR(11),
        birth_date DATE,
        gender ENUM('male','female') NOT NULL,
        qr_code VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        mosque_id INT NOT NULL,
        scan_date DATE NOT NULL,
        scan_time TIME NOT NULL,
        notes VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Varsayılan admin oluştur
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO admins (username, password, name, email) VALUES (?, ?, 'Sistem Yöneticisi', 'admin@cami.gov.tr')");
    $stmt->execute([$adminUser, $hash]);

    // Kurulum kilit dosyası oluştur (tekrar çalışmayı engeller)
    file_put_contents(__DIR__ . '/cami_kurulum_tamam.lock', date('Y-m-d H:i:s'));

    echo '<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><title>Kurulum</title>
<style>
body{font-family:sans-serif;background:#f0f7f0;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}
.box{background:#fff;border-radius:12px;padding:40px;max-width:500px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center}
h1{color:#1a7a3a}
.success{background:#d4edda;color:#155724;padding:15px;border-radius:8px;margin:15px 0}
.warning{background:#fff3cd;color:#856404;padding:15px;border-radius:8px;margin:15px 0}
.btn{display:inline-block;background:#1a7a3a;color:#fff;padding:12px 30px;border-radius:8px;text-decoration:none;margin-top:15px;font-weight:bold}
</style></head>
<body>
<div class="box">
  <h1>✅ Kurulum Tamamlandı!</h1>
  <div class="success">
    <strong>Veritabanı başarıyla oluşturuldu.</strong><br>
    Tüm tablolar kuruldu.
  </div>
  <div class="warning">
    ⚠️ <strong>Güvenlik Uyarısı:</strong><br>
    Admin kullanıcı adı: <code>admin</code><br>
    Admin şifresi: <code>admin123</code><br>
    Giriş yaptıktan sonra şifreyi değiştirin!<br><br>
    Kurulum bittikten sonra <strong>install.php dosyasını silin!</strong>
  </div>
  <a href="index.php" class="btn">🕌 Sisteme Git</a>
</div>
</body></html>';

} catch (PDOException $e) {
    echo '<div style="font-family:sans-serif;padding:30px;background:#f8d7da;border-radius:8px;max-width:600px;margin:30px auto">
        <h2>❌ Kurulum Hatası</h2>
        <p>Veritabanı bilgilerini <strong>install.php</strong> dosyasında güncelleyin:</p>
        <ul>
            <li>DB_USER: InfinityFree veritabanı kullanıcı adı</li>
            <li>DB_PASS: InfinityFree veritabanı şifresi</li>
            <li>DB_NAME: InfinityFree veritabanı adı</li>
        </ul>
        <p>Hata: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
