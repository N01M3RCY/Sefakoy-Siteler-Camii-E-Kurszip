<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$type   = $_GET['type']   ?? 'students';
$format = $_GET['format'] ?? 'csv';
$mosque_id = (int)($_GET['mosque'] ?? 0);

$allowed = ['students','parents','mosques','attendance'];
if (!in_array($type, $allowed)) die('Geçersiz tür.');

switch ($type) {
    case 'students':
        $where  = $mosque_id ? "WHERE s.mosque_id=$mosque_id" : "";
        $data   = $db->query("
            SELECT s.id, s.name AS 'Ad', s.surname AS 'Soyad',
                   s.tc_no AS 'TC No', s.birth_date AS 'Doğum Tarihi',
                   CASE WHEN s.gender='male' THEN 'Erkek' ELSE 'Kız' END AS 'Cinsiyet',
                   s.qr_code AS 'QR Kod',
                   CASE WHEN s.status='active' THEN 'Aktif' ELSE 'Pasif' END AS 'Durum',
                   m.name AS 'Cami', m.district AS 'İlçe',
                   p.name AS 'Veli Adı', p.surname AS 'Veli Soyadı', p.phone AS 'Veli Telefon',
                   DATE_FORMAT(s.created_at,'%d.%m.%Y') AS 'Kayıt Tarihi'
            FROM students s
            JOIN mosques m ON s.mosque_id=m.id
            JOIN parents p ON s.parent_id=p.id
            $where
            ORDER BY m.name, s.surname, s.name
        ")->fetchAll();
        $filename = 'ogrenciler';
        break;

    case 'parents':
        $data = $db->query("
            SELECT p.id, p.name AS 'Ad', p.surname AS 'Soyad',
                   p.tc_no AS 'TC No', p.phone AS 'Telefon', p.email AS 'E-posta',
                   COUNT(s.id) AS 'Öğrenci Sayısı',
                   DATE_FORMAT(p.created_at,'%d.%m.%Y') AS 'Kayıt Tarihi'
            FROM parents p LEFT JOIN students s ON s.parent_id=p.id
            GROUP BY p.id ORDER BY p.surname, p.name
        ")->fetchAll();
        $filename = 'veliler';
        break;

    case 'mosques':
        $data = $db->query("
            SELECT m.id, m.name AS 'Cami Adı', m.district AS 'İlçe', m.city AS 'Şehir',
                   m.imam_name AS 'İmam', m.phone AS 'Telefon', m.email AS 'E-posta',
                   m.username AS 'Kullanıcı Adı', m.capacity AS 'Kapasite',
                   CASE m.status WHEN 'active' THEN 'Aktif' WHEN 'pending' THEN 'Bekliyor' ELSE 'Pasif' END AS 'Durum',
                   COUNT(s.id) AS 'Öğrenci Sayısı',
                   DATE_FORMAT(m.created_at,'%d.%m.%Y') AS 'Kayıt Tarihi'
            FROM mosques m LEFT JOIN students s ON s.mosque_id=m.id
            GROUP BY m.id ORDER BY m.name
        ")->fetchAll();
        $filename = 'camiler';
        break;

    case 'attendance':
        $where  = $mosque_id ? "AND a.mosque_id=$mosque_id" : "";
        $data   = $db->query("
            SELECT DATE_FORMAT(a.scan_date,'%d.%m.%Y') AS 'Tarih',
                   TIME_FORMAT(a.scan_time,'%H:%i') AS 'Saat',
                   s.name AS 'Öğrenci Adı', s.surname AS 'Öğrenci Soyadı',
                   s.qr_code AS 'QR Kod',
                   m.name AS 'Cami', m.district AS 'İlçe'
            FROM attendance a
            JOIN students s ON a.student_id=s.id
            JOIN mosques m ON a.mosque_id=m.id
            WHERE 1=1 $where
            ORDER BY a.scan_date DESC, a.scan_time DESC
        ")->fetchAll();
        $filename = 'yoklama';
        break;
}

$filename .= '_' . date('Y-m-d');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: no-cache');
    // UTF-8 BOM (Excel için)
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($out, array_keys($data[0]), ';');
        foreach ($data as $row) fputcsv($out, $row, ';');
    }
    fclose($out);
} else {
    // Basit HTML tablo (Excel açabilir)
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: no-cache');
    echo "\xEF\xBB\xBF";
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    if (!empty($data)) {
        echo '<tr style="background:#0d5c2e;color:#fff;font-weight:bold">';
        foreach (array_keys($data[0]) as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
        echo '</tr>';
        foreach ($data as $i => $row) {
            echo '<tr style="background:' . ($i%2===0?'#fff':'#e8f5ee') . '">';
            foreach ($row as $cell) echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            echo '</tr>';
        }
    }
    echo '</table></body></html>';
}
exit;
