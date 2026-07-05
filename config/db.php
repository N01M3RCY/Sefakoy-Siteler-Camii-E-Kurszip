<?php
// Replit ortamında local config varsa onu kullan, yoksa InfinityFree ayarları
if (file_exists(__DIR__ . '/db.local.php')) {
    require_once __DIR__ . '/db.local.php';
} else {
    // ── InfinityFree Ayarları ─────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');        // InfinityFree veritabanı kullanıcı adı
    define('DB_PASS', '');            // InfinityFree veritabanı şifresi
    define('DB_NAME', 'cami_sistemi'); // InfinityFree veritabanı adı
    define('DB_SOCKET', '');
}

define('SITE_NAME', 'Cami Öğrenci Yönetim Sistemi');
define('SITE_URL', '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $socket = defined('DB_SOCKET') && DB_SOCKET ? ';unix_socket=' . DB_SOCKET : '';
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4" . $socket,
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

function getUpcomingHoliday($db, $withinDays = 30) {
    $stmt = $db->prepare("SELECT * FROM holidays WHERE holiday_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) ORDER BY holiday_date LIMIT 1");
    $stmt->execute([$withinDays]);
    return $stmt->fetch();
}

function getTodayHoliday($db) {
    $stmt = $db->prepare("SELECT * FROM holidays WHERE holiday_date = CURDATE() LIMIT 1");
    $stmt->execute();
    return $stmt->fetch();
}

function getCourseWeekInfo($course) {
    if (empty($course['start_date']) || empty($course['duration_weeks'])) return null;
    $start = new DateTime($course['start_date']);
    $today = new DateTime('today');
    if ($today < $start) {
        return ['current_week' => 0, 'total_weeks' => (int)$course['duration_weeks'], 'status' => 'not_started'];
    }
    $diffDays = $start->diff($today)->days;
    $currentWeek = intdiv($diffDays, 7) + 1;
    $totalWeeks = (int)$course['duration_weeks'];
    return [
        'current_week' => min($currentWeek, $totalWeeks),
        'total_weeks'  => $totalWeeks,
        'status'       => $currentWeek > $totalWeeks ? 'finished' : 'ongoing',
        'percent'      => max(0, min(100, round(($currentWeek / $totalWeeks) * 100))),
    ];
}
