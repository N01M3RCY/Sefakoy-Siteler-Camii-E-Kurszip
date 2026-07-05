<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db  = getDB();
$mid = $_SESSION['mosque_id'];

$type   = $_GET['type']   ?? 'students';
$format = $_GET['format'] ?? 'csv';

switch ($type) {
    case 'students':
        $stmt = $db->prepare("
            SELECT s.name AS 'Ad', s.surname AS 'Soyad',
                   s.tc_no AS 'TC No', s.birth_date AS 'Doğum Tarihi',
                   CASE WHEN s.gender='male' THEN 'Erkek' ELSE 'Kız' END AS 'Cinsiyet',
                   s.qr_code AS 'QR Kod',
                   CASE WHEN s.status='active' THEN 'Aktif' ELSE 'Pasif' END AS 'Durum',
                   p.name AS 'Veli Adı', p.surname AS 'Veli Soyadı',
                   p.phone AS 'Veli Telefon', p.email AS 'Veli E-posta',
                   DATE_FORMAT(s.created_at,'%d.%m.%Y') AS 'Kayıt Tarihi'
            FROM students s JOIN parents p ON s.parent_id=p.id
            WHERE s.mosque_id=? ORDER BY s.surname, s.name
        ");
        $stmt->execute([$mid]);
        $data = $stmt->fetchAll();
        $filename = 'ogrencilerim';
        break;

    case 'attendance':
        $date_from = $_GET['from'] ?? date('Y-m-01');
        $date_to   = $_GET['to']   ?? date('Y-m-d');
        $stmt = $db->prepare("
            SELECT DATE_FORMAT(a.scan_date,'%d.%m.%Y') AS 'Tarih',
                   TIME_FORMAT(a.scan_time,'%H:%i') AS 'Saat',
                   s.name AS 'Öğrenci Adı', s.surname AS 'Öğrenci Soyadı',
                   s.qr_code AS 'QR Kod',
                   CASE WHEN s.gender='male' THEN 'Erkek' ELSE 'Kız' END AS 'Cinsiyet'
            FROM attendance a JOIN students s ON a.student_id=s.id
            WHERE a.mosque_id=? AND a.scan_date BETWEEN ? AND ?
            ORDER BY a.scan_date DESC, s.surname
        ");
        $stmt->execute([$mid, $date_from, $date_to]);
        $data = $stmt->fetchAll();
        $filename = 'yoklama_' . $date_from . '_' . $date_to;
        break;

    default:
        die('Geçersiz tür.');
}

$filename .= '_' . date('Ymd');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($out, array_keys($data[0]), ';');
        foreach ($data as $row) fputcsv($out, $row, ';');
    }
    fclose($out);
} else {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    echo "\xEF\xBB\xBF";
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body><table border="1">';
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
