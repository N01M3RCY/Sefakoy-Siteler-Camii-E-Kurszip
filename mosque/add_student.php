<?php
require_once '../config/db.php';
requireLogin('mosque', 'login.php');
$db = getDB();
$mid = $_SESSION['mosque_id'];

$success = $error = '';

// Kursları getir
$coursesList = $db->prepare("SELECT * FROM courses WHERE mosque_id=? AND status='active' ORDER BY name");
$coursesList->execute([$mid]); $coursesList = $coursesList->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $surname   = trim($_POST['surname'] ?? '');
    $age       = (int)($_POST['age'] ?? 0);
    $gender    = $_POST['gender'] ?? '';
    $notes     = trim($_POST['notes'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0) ?: null;

    if (!$name || !$surname || !$age || !$gender) {
        $error = 'Ad, soyad, yaş ve cinsiyet zorunludur.';
    } elseif ($age < 5 || $age > 18) {
        $error = 'Yaş 5 ile 18 arasında olmalıdır.';
    } else {
        // QR kod üret
        $qr = 'QR' . strtoupper(substr(md5($name.$surname.time().rand()), 0, 10));
        // Doğum yılı hesapla (yaklaşık)
        $birth_year = date('Y') - $age;
        $birth_date = $birth_year . '-01-01';

        try {
            $stmt = $db->prepare("INSERT INTO students (mosque_id, parent_id, name, surname, age, birth_date, gender, qr_code, notes, course_id, status) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$mid, $name, $surname, $age, $birth_date, $gender, $qr, $notes, $course_id]);
            $sid = $db->lastInsertId();
            $success = "✅ <strong>" . sanitize($name . ' ' . $surname) . "</strong> başarıyla eklendi! QR Kod: <code>$qr</code>";
        } catch (PDOException $e) {
            $error = 'Kayıt sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}

// Yaş grupları için mevcut öğrenciler
$groups = $db->prepare("
    SELECT 
        CASE 
            WHEN age BETWEEN 5 AND 7 THEN '5-7 Yaş'
            WHEN age BETWEEN 8 AND 10 THEN '8-10 Yaş'
            WHEN age BETWEEN 11 AND 13 THEN '11-13 Yaş'
            WHEN age BETWEEN 14 AND 18 THEN '14-18 Yaş'
            ELSE 'Belirtilmemiş'
        END AS age_group,
        COUNT(*) AS total,
        SUM(CASE WHEN gender='male' THEN 1 ELSE 0 END) AS erkek,
        SUM(CASE WHEN gender='female' THEN 1 ELSE 0 END) AS kiz
    FROM students WHERE mosque_id=? AND status='active'
    GROUP BY age_group ORDER BY age_group
");
$groups->execute([$mid]);
$ageGroups = $groups->fetchAll();

$page_title = 'Öğrenci Ekle';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?> &nbsp;<a href="students.php" class="btn btn-sm btn-primary">Öğrenci Listesi →</a></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

  <!-- Form -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">➕ Yeni Öğrenci Ekle</span>
    </div>
    <div class="card-body">
      <form method="post">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Ad *</label>
            <input type="text" name="name" class="form-control" placeholder="Ahmet" required value="<?= sanitize($_POST['name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Soyad *</label>
            <input type="text" name="surname" class="form-control" placeholder="Yılmaz" required value="<?= sanitize($_POST['surname'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Yaş * <small style="color:#64748b">(5-18)</small></label>
            <input type="number" name="age" class="form-control" placeholder="10" min="5" max="18" required value="<?= (int)($_POST['age'] ?? '') ?: '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Cinsiyet *</label>
            <select name="gender" class="form-control" required>
              <option value="">Seçin...</option>
              <option value="male"   <?= ($_POST['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Erkek</option>
              <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Kız</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Not <small style="color:#64748b">(opsiyonel)</small></label>
          <input type="text" name="notes" class="form-control" placeholder="Özel not..." value="<?= sanitize($_POST['notes'] ?? '') ?>">
        </div>

        <?php if (!empty($coursesList)): ?>
        <div class="form-group">
          <label class="form-label">Kurs / Grup <small style="color:#64748b">(opsiyonel)</small></label>
          <select name="course_id" class="form-control">
            <option value="">— Kursa atama —</option>
            <?php foreach ($coursesList as $c): ?>
            <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="alert alert-info" style="margin-bottom:16px">
          ℹ️ Öğrenci otomatik QR kod alır. Veli bilgisi sonradan eklenebilir.
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">➕ Öğrenciyi Ekle</button>
      </form>
    </div>
  </div>

  <!-- Yaş Grubu İstatistikleri -->
  <div>
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><span class="card-title">📊 Yaş Grubu Dağılımı</span></div>
      <div class="card-body">
        <?php if (empty($ageGroups)): ?>
          <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <div class="empty-state-title">Henüz öğrenci yok</div>
          </div>
        <?php else: ?>
          <?php foreach ($ageGroups as $g): ?>
          <div style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
              <span style="font-weight:600;font-size:14px"><?= sanitize($g['age_group']) ?></span>
              <span style="font-size:13px;color:#64748b">
                <span style="color:#3b82f6">👦 <?= $g['erkek'] ?></span> &nbsp;
                <span style="color:#ec4899">👧 <?= $g['kiz'] ?></span> &nbsp;
                <strong>= <?= $g['total'] ?></strong>
              </span>
            </div>
            <?php
              $total_all = array_sum(array_column($ageGroups, 'total'));
              $pct = $total_all > 0 ? round($g['total']/$total_all*100) : 0;
            ?>
            <div style="background:#e2e8f0;border-radius:999px;height:10px">
              <div style="background:linear-gradient(90deg,#1a7a3a,#2ea855);height:10px;border-radius:999px;width:<?= $pct ?>%"></div>
            </div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px">%<?= $pct ?> pay</div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Hızlı erişim -->
    <div class="card">
      <div class="card-header"><span class="card-title">🔗 Hızlı Erişim</span></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <a href="students.php" class="btn btn-secondary btn-block">📚 Tüm Öğrenciler</a>
        <a href="attendance.php" class="btn btn-secondary btn-block">✅ Yoklama Al</a>
      </div>
    </div>
  </div>

</div>

<?php include 'layout/footer.php'; ?>
