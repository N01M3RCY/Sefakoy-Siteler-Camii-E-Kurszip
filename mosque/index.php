<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$stats = [
    'students'  => $db->prepare("SELECT COUNT(*) FROM students WHERE mosque_id=? AND status='active'"),
    'total'     => $db->prepare("SELECT COUNT(*) FROM students WHERE mosque_id=?"),
    'today'     => $db->prepare("SELECT COUNT(*) FROM attendance WHERE mosque_id=? AND scan_date=CURDATE()"),
    'parents'   => $db->prepare("SELECT COUNT(DISTINCT parent_id) FROM students WHERE mosque_id=? AND parent_id IS NOT NULL"),
];
foreach ($stats as $k => $s) { $s->execute([$mid]); $stats[$k] = $s->fetchColumn(); }

// Yeni özellik sayaçları
$hwCount = $db->prepare("SELECT COUNT(*) FROM homeworks WHERE mosque_id=? AND status='active'");
$hwCount->execute([$mid]); $hwCount = $hwCount->fetchColumn();

$duaCount = $db->prepare("SELECT COUNT(*) FROM duas WHERE mosque_id=?");
$duaCount->execute([$mid]); $duaCount = $duaCount->fetchColumn();

$mosque = $db->prepare("SELECT * FROM mosques WHERE id=?");
$mosque->execute([$mid]);
$mosque = $mosque->fetch();

$upcomingHoliday = getUpcomingHoliday($db, 14);
$todayHoliday = getTodayHoliday($db);

$adminAnnCount = $db->prepare("SELECT COUNT(*) FROM announcements WHERE source_type='admin' AND status='active' AND (mosque_id IS NULL OR mosque_id=?)");
$adminAnnCount->execute([$mid]); $adminAnnCount = $adminAnnCount->fetchColumn();

$recentStudents = $db->prepare("
    SELECT s.*, p.name AS p_name, p.surname AS p_surname, p.phone AS p_phone
    FROM students s LEFT JOIN parents p ON s.parent_id=p.id
    WHERE s.mosque_id=? ORDER BY s.created_at DESC LIMIT 8
");
$recentStudents->execute([$mid]);
$recentStudents = $recentStudents->fetchAll();

// Yaş grubu dağılımı
$ageGroups = $db->prepare("
    SELECT 
        CASE 
            WHEN age BETWEEN 5 AND 7 THEN '5-7 Yaş'
            WHEN age BETWEEN 8 AND 10 THEN '8-10 Yaş'
            WHEN age BETWEEN 11 AND 13 THEN '11-13 Yaş'
            WHEN age BETWEEN 14 AND 18 THEN '14-18 Yaş'
            ELSE 'Belirtilmemiş'
        END AS age_group,
        COUNT(*) AS total
    FROM students WHERE mosque_id=? AND status='active'
    GROUP BY age_group ORDER BY age_group
");
$ageGroups->execute([$mid]);
$ageGroups = $ageGroups->fetchAll();

$page_title = 'Kontrol Paneli';
include 'layout/header.php';
?>

<!-- Cami info banner -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#0d5c2e,#1a7a3a);color:#fff;border-radius:16px">
  <div class="card-body" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
    <div style="font-size:56px">🕌</div>
    <div style="flex:1">
      <div style="font-size:22px;font-weight:800"><?= sanitize($mosque['name']) ?></div>
      <div style="opacity:.8;font-size:13px;margin-top:4px">
        <?php if ($mosque['district']): ?><?= sanitize($mosque['district']) ?> / <?= sanitize($mosque['city'] ?? '') ?> · <?php endif; ?>
        <?php if ($mosque['imam_name']): ?>İmam: <?= sanitize($mosque['imam_name']) ?> · <?php endif; ?>
        <?php if ($mosque['phone']): ?>📞 <?= sanitize($mosque['phone']) ?><?php endif; ?>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="add_student.php" class="btn btn-gold">➕ Öğrenci Ekle</a>
      <a href="attendance.php" class="btn" style="background:rgba(255,255,255,.15);color:#fff">✅ Yoklama Al</a>
    </div>
  </div>
</div>

<?php if ($todayHoliday): ?>
<div class="card" style="margin-bottom:20px;background:#fff7ed;border-left:4px solid #f97316">
  <div class="card-body" style="display:flex;gap:14px;align-items:center">
    <div style="font-size:32px">🎉</div>
    <div><strong>Bugün "<?= sanitize($todayHoliday['name']) ?>"</strong> — tatil günü, ders/yoklama planlarken dikkat edin.</div>
  </div>
</div>
<?php elseif ($upcomingHoliday): ?>
<div class="card" style="margin-bottom:20px;background:#fefce8;border-left:4px solid #c9a227">
  <div class="card-body" style="display:flex;gap:14px;align-items:center">
    <div style="font-size:32px">🗓️</div>
    <div><strong><?= sanitize($upcomingHoliday['name']) ?></strong> — <?= date('d.m.Y', strtotime($upcomingHoliday['holiday_date'])) ?> tarihinde (<?= (new DateTime())->diff(new DateTime($upcomingHoliday['holiday_date']))->days ?> gün sonra)</div>
  </div>
</div>
<?php endif; ?>

<?php if ($adminAnnCount > 0): ?>
<div class="card" style="margin-bottom:20px;background:#f5f3ff;border-left:4px solid #7c3aed">
  <div class="card-body" style="display:flex;gap:14px;align-items:center;justify-content:space-between;flex-wrap:wrap">
    <div style="display:flex;gap:14px;align-items:center"><div style="font-size:32px">🏛️</div><div><strong>Müftülükten <?= $adminAnnCount ?> aktif duyuru</strong> var.</div></div>
    <a href="announcements.php" class="btn btn-sm btn-secondary">Görüntüle →</a>
  </div>
</div>
<?php endif; ?>

<!-- Stat Kartları -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📚</div>
    <div><div class="stat-value"><?= $stats['students'] ?></div><div class="stat-label">Aktif Öğrenci</div></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon">✅</div>
    <div><div class="stat-value"><?= $stats['today'] ?></div><div class="stat-label">Bugün Yoklama</div></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">📝</div>
    <div><div class="stat-value"><?= $hwCount ?></div><div class="stat-label">Aktif Ödev</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🤲</div>
    <div><div class="stat-value"><?= $duaCount ?></div><div class="stat-label">Kayıtlı Dua</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">

  <!-- Son Öğrenciler -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📚 Son Kayıt Öğrenciler</span>
      <a href="students.php" class="btn btn-sm btn-primary">Tümünü Gör</a>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Öğrenci</th><th>Yaş</th><th>Veli</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($recentStudents as $s): ?>
          <tr>
            <td>
              <strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong><br>
              <span class="badge <?= $s['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:11px"><?= $s['gender']==='male'?'👦 Erkek':'👧 Kız' ?></span>
            </td>
            <td><?= $s['age'] ? $s['age'].' yaş' : ($s['birth_date'] ? date('Y') - date('Y', strtotime($s['birth_date'])) . ' yaş' : '—') ?></td>
            <td><?= $s['p_name'] ? sanitize($s['p_name'].' '.$s['p_surname']).'<br><small style="color:#94a3b8">📞 '.sanitize($s['p_phone']).'</small>' : '<span style="color:#94a3b8;font-size:13px">—</span>' ?></td>
            <td><span class="badge <?= $s['status']==='active'?'badge-success':'badge-danger' ?>"><?= $s['status']==='active'?'Aktif':'Pasif' ?></span></td>
            <td><a href="../qr.php?code=<?= urlencode($s['qr_code']) ?>" target="_blank" class="btn btn-sm btn-secondary">🪪 Kart</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentStudents)): ?>
          <tr><td colspan="5">
            <div class="empty-state">
              <div class="empty-state-icon">📚</div>
              <div class="empty-state-title">Henüz öğrenci yok</div>
              <div class="empty-state-desc"><a href="add_student.php" style="color:#1a7a3a">Öğrenci ekleyin →</a></div>
            </div>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Sağ kolon -->
  <div>
    <!-- Yaş Dağılımı -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <span class="card-title">📊 Yaş Dağılımı</span>
        <a href="add_student.php" class="btn btn-sm btn-gold">➕ Ekle</a>
      </div>
      <div class="card-body">
        <?php if (empty($ageGroups)): ?>
          <div style="text-align:center;color:#94a3b8;padding:20px;font-size:13px">Henüz öğrenci yok</div>
        <?php else: ?>
          <?php $total_all = array_sum(array_column($ageGroups, 'total')); ?>
          <?php foreach ($ageGroups as $g): ?>
          <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px">
              <span><?= sanitize($g['age_group']) ?></span>
              <strong><?= $g['total'] ?> öğrenci</strong>
            </div>
            <div style="background:#e2e8f0;border-radius:999px;height:8px">
              <div style="background:linear-gradient(90deg,#1a7a3a,#2ea855);height:8px;border-radius:999px;width:<?= $total_all>0?round($g['total']/$total_all*100):0 ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Hızlı Erişim -->
    <div class="card">
      <div class="card-header"><span class="card-title">⚡ Hızlı Erişim</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="duas.php" class="btn btn-secondary btn-block">🤲 Dua Sistemi</a>
        <a href="homeworks.php" class="btn btn-secondary btn-block">📝 Ödevler</a>
        <a href="attendance.php" class="btn btn-secondary btn-block">✅ Yoklama</a>
        <a href="announcements.php" class="btn btn-secondary btn-block">📢 Duyurular</a>
        <a href="export.php?type=students&format=xls" class="btn btn-secondary btn-block">📥 Excel'e Aktar</a>
      </div>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>
