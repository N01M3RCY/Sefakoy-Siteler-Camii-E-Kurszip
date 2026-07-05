<?php
require_once '../config/db.php';
requireLogin('teacher', 'login.php');
$db  = getDB();
$tid = $_SESSION['teacher_id'];
$mid = $_SESSION['teacher_mosque_id'];

$course = $db->prepare("SELECT * FROM courses WHERE teacher_id=? AND mosque_id=? AND status='active' LIMIT 1");
$course->execute([$tid, $mid]); $course = $course->fetch();
$cid = $course['id'] ?? null;

$success = $error = '';

$mems = [];
if ($cid) {
    $m = $db->prepare("SELECT * FROM memorizations WHERE course_id=? AND status='active' ORDER BY sort_order ASC, created_at ASC");
    $m->execute([$cid]); $mems = $m->fetchAll();
}
$total = count($mems);

function getCurrentIndex($progressByMem, $mems) {
    foreach ($mems as $i => $m) {
        $st = $progressByMem[$m['id']]['status'] ?? null;
        if ($st !== 'tamamlandi') return $i;
    }
    return count($mems); // hepsini bitirdi
}

// İşaretle: Ezberledi / Tekrar Gerekli
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['mark_done']) || isset($_POST['mark_repeat'])) && $cid) {
    $sid   = (int)$_POST['student_id'];
    $memId = (int)$_POST['mem_id'];

    // Öğrenci gerçekten bu kursa mı ait kontrol et
    $chkS = $db->prepare("SELECT id FROM students WHERE id=? AND course_id=? AND status='active'");
    $chkS->execute([$sid, $cid]);
    // Ezber öğesi gerçekten bu kursa mı ait kontrol et
    $chkM = $db->prepare("SELECT id FROM memorizations WHERE id=? AND course_id=? AND status='active'");
    $chkM->execute([$memId, $cid]);

    if ($chkS->fetch() && $chkM->fetch()) {
        // Sıra kontrolü: sadece öğrencinin sıradaki maddesi işaretlenebilir
        $pr = $db->prepare("SELECT memorization_id, status FROM student_progress WHERE student_id=?");
        $pr->execute([$sid]);
        $progressByMem = [];
        foreach ($pr->fetchAll() as $row) { $progressByMem[$row['memorization_id']] = $row; }
        $curIdx = getCurrentIndex($progressByMem, $mems);
        $curMemId = $mems[$curIdx]['id'] ?? null;

        if ($curMemId !== $memId) {
            $error = 'Bu öğrenci sırada bu maddede değil. Önce mevcut sıradaki ezberi işaretlemelisiniz.';
        } else {
            $newStatus = isset($_POST['mark_done']) ? 'tamamlandi' : 'tekrar';
            $existing = $db->prepare("SELECT id, attempt_count FROM student_progress WHERE student_id=? AND memorization_id=?");
            $existing->execute([$sid, $memId]);
            $ex = $existing->fetch();
            if ($ex) {
                $attempts = $newStatus === 'tekrar' ? $ex['attempt_count'] + 1 : $ex['attempt_count'];
                $db->prepare("UPDATE student_progress SET status=?, attempt_count=? WHERE id=?")->execute([$newStatus, $attempts, $ex['id']]);
            } else {
                $db->prepare("INSERT INTO student_progress (student_id, memorization_id, status, attempt_count) VALUES (?,?,?,1)")->execute([$sid, $memId, $newStatus]);
            }
            $success = $newStatus === 'tamamlandi' ? '✅ Ezber tamamlandı, öğrenci bir sonraki sıraya geçti.' : '🔁 Tekrar gerekiyor olarak işaretlendi.';
        }
    } else {
        $error = 'Geçersiz işlem.';
    }
}

$students = [];
if ($cid) {
    $s = $db->prepare("SELECT * FROM students WHERE course_id=? AND status='active' ORDER BY name");
    $s->execute([$cid]); $students = $s->fetchAll();
}

// Her öğrenci için ilerleme haritasını çıkar
$studentData = [];
foreach ($students as $s) {
    $pr = $db->prepare("SELECT memorization_id, status, attempt_count FROM student_progress WHERE student_id=?");
    $pr->execute([$s['id']]);
    $progressByMem = [];
    foreach ($pr->fetchAll() as $row) { $progressByMem[$row['memorization_id']] = $row; }
    $curIdx = getCurrentIndex($progressByMem, $mems);
    $doneCount = 0;
    foreach ($mems as $m) { if (($progressByMem[$m['id']]['status'] ?? null) === 'tamamlandi') $doneCount++; }
    $studentData[$s['id']] = [
        'student' => $s,
        'progressByMem' => $progressByMem,
        'curIdx' => $curIdx,
        'doneCount' => $doneCount,
    ];
}

$expand = isset($_GET['ogrenci']) ? (int)$_GET['ogrenci'] : null;

$typeIcons = ['sure'=>'📖','dua'=>'🤲','ayet'=>'✨','diger'=>'📋'];

$page_title = 'Ezber Takibi';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<?php if (!$course): ?>
<div class="alert alert-info">⚠️ Henüz bir kursa atanmadınız. Cami yöneticisiyle iletişime geçin.</div>
<?php elseif (empty($mems)): ?>
<div class="alert alert-info">📖 Bu kurs için henüz sıralı bir ezber listesi oluşturulmadı. Önce <a href="memorizations.php">Sure / Dua Ezberi</a> sayfasından listeyi oluşturun.</div>
<?php else: ?>

<div class="alert alert-info" style="margin-bottom:16px">📚 Kurs: <strong><?= sanitize($course['name']) ?></strong> · Toplam <?= $total ?> sure/dua sırayla ezberlenir. Öğrenci bir maddeyi tamamlamadan sıradakine geçemez.</div>

<div class="card">
  <div class="card-header"><span class="card-title">🧭 Öğrenci İlerlemesi (<?= count($students) ?>)</span></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Öğrenci</th><th>İlerleme</th><th>Sıradaki</th><th>Durum</th><th>İşlem</th></tr></thead>
      <tbody>
        <?php foreach ($studentData as $sid => $d):
          $s = $d['student'];
          $pct = $total ? round($d['doneCount'] / $total * 100) : 0;
          $finished = $d['curIdx'] >= $total;
          $curMem = $finished ? null : $mems[$d['curIdx']];
          $curProg = $curMem ? ($d['progressByMem'][$curMem['id']] ?? null) : null;
        ?>
        <tr>
          <td>
            <strong><?= sanitize($s['name'].' '.$s['surname']) ?></strong><br>
            <span class="badge <?= $s['gender']==='male'?'badge-info':'badge-warning' ?>" style="font-size:10px"><?= $s['gender']==='male'?'👦':'👧' ?></span>
          </td>
          <td style="min-width:140px">
            <div style="background:#e2e8f0;border-radius:999px;height:8px;margin-bottom:4px">
              <div style="background:linear-gradient(90deg,#1a7a3a,#2ea855);height:8px;border-radius:999px;width:<?= $pct ?>%"></div>
            </div>
            <small style="color:#64748b"><?= $d['doneCount'] ?>/<?= $total ?> (%<?= $pct ?>)</small>
          </td>
          <td>
            <?php if ($finished): ?>
              <span class="badge badge-success">🏆 Liste Tamamlandı</span>
            <?php else: ?>
              <span style="font-weight:700">#<?= $d['curIdx']+1 ?></span>
              <?= $typeIcons[$curMem['type']] ?? '📖' ?> <?= sanitize($curMem['title']) ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$finished && $curProg && $curProg['status']==='tekrar'): ?>
              <span class="badge badge-warning">🔁 Tekrar (<?= $curProg['attempt_count'] ?>. deneme)</span>
            <?php elseif (!$finished): ?>
              <span class="badge badge-info">📖 Çalışıyor</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$finished): ?>
            <div style="display:flex;gap:4px;flex-wrap:wrap">
              <form method="post" style="display:inline">
                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="mem_id" value="<?= $curMem['id'] ?>">
                <button name="mark_done" class="btn btn-sm btn-primary">✅ Ezberledi</button>
              </form>
              <form method="post" style="display:inline">
                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="mem_id" value="<?= $curMem['id'] ?>">
                <button name="mark_repeat" class="btn btn-sm btn-secondary">🔁 Tekrar Gerekli</button>
              </form>
            </div>
            <?php endif; ?>
            <a href="progress.php?ogrenci=<?= $expand === $s['id'] ? '' : $s['id'] ?>" class="btn btn-sm btn-secondary" style="margin-top:4px">
              <?= $expand === $s['id'] ? '▲ Gizle' : '▼ Tüm Liste' ?>
            </a>
          </td>
        </tr>
        <?php if ($expand === $s['id']): ?>
        <tr>
          <td colspan="5" style="background:#f8fafc">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px;padding:10px">
              <?php foreach ($mems as $i => $m):
                $st = $d['progressByMem'][$m['id']]['status'] ?? null;
                $isCur = $i === $d['curIdx'];
                if ($st === 'tamamlandi') { $badge = '✅'; $bg = '#f0fdf4'; $bc = '#86efac'; }
                elseif ($isCur && $st === 'tekrar') { $badge = '🔁'; $bg = '#fff7ed'; $bc = '#fdba74'; }
                elseif ($isCur) { $badge = '📖'; $bg = '#eff6ff'; $bc = '#93c5fd'; }
                else { $badge = '🔒'; $bg = '#fff'; $bc = '#e2e8f0'; }
              ?>
              <div style="display:flex;align-items:center;gap:6px;padding:6px 8px;border-radius:6px;border:1px solid <?= $bc ?>;background:<?= $bg ?>;font-size:12px">
                <span>#<?= $i+1 ?></span><span><?= $badge ?></span>
                <span style="<?= $st!=='tamamlandi' && !$isCur ? 'color:#94a3b8' : '' ?>"><?= sanitize($m['title']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($students)): ?>
        <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">📚</div><div class="empty-state-title">Kursta öğrenci yok</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
<?php include 'layout/footer.php'; ?>
