<?php
// Veritabanı Ayarları - InfinityFree için düzenleyin
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // InfinityFree'de kendi kullanıcı adınız
define('DB_PASS', '');            // InfinityFree'de kendi şifreniz
define('DB_NAME', 'cami_sistemi'); // InfinityFree'de kendi DB adınız

define('SITE_NAME', 'Cami Öğrenci Yönetim Sistemi');
define('SITE_URL', '');  // Örn: https://siteniz.infinityfreeapp.com

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin:20px;">
                <h3>⚠️ Veritabanı Bağlantı Hatası</h3>
                <p>Lütfen <strong>config/db.php</strong> dosyasındaki veritabanı bilgilerini kontrol edin.</p>
                <p>Önce <code>install.php</code> dosyasını çalıştırın.</p>
                <small>Hata: ' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}

function generateQRCode($studentId) {
    return 'QR' . strtoupper(substr(md5($studentId . time() . rand()), 0, 10));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn($role) {
    session_start_safe();
    return isset($_SESSION[$role . '_id']);
}

function requireLogin($role, $loginPage) {
    session_start_safe();
    if (!isset($_SESSION[$role . '_id'])) {
        redirect($loginPage);
    }
}

function session_start_safe() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
