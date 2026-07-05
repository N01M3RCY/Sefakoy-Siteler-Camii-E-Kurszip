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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cid) {
    $name    = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $age     = (int)($_POST['age'] ?? 0);
    $gender  = $_POST['gender'] ?? '';
    $notes   = trim($_POST['notes'] ?? '');

    if (!$name || !$surname || !$age || !$gender) {
        $error = 'Ad, soyad, yaş ve cinsiyet zorunludur.';
    } elseif ($age < 5 || $age > 18) {
        $error = 'Yaş 5 ile 18 arasında olmalıdır.';
    } else {
        $qr = 'QR' . strtoupper(substr(md5($name.$surname.time().rand()), 0, 10));
        $birth_year = date('Y') - $age;
        $birth_date = $birth_year . '-01-01';
        try {
            $stmt = $db->prepare("INSERT INTO students (mosque_id, parent_id, name, surname, age, birth_date, gender, qr_code, notes, course_id, status) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$mid, $name, $surname, $age, $birth_date, $gender, $qr, $notes, $cid]);
            $success = "✅ <strong>" . sanitize($name.' '.$surname) . "</strong> \"" . sanitize($course['name']) . "\" kursuna eklendi! QR Kod: <code>$qr</code>";
        } catch (PDOException $e) {
            $error = 'Kayıt sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}

$page_title = 'Öğrenci Ekle';
include 'layout/header.php';
?>

<?php if (!$course): ?>
<div class="alert alert-info">⚠️ Henüz bir kursa atanmadınız. Cami yöneticisiyle iletişime geçin. Öğrenci eklemek için önce bir kursa atanmanız gerekir.</div>
<?php else: ?>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?> &nbsp;<a href="students.php" class="btn btn-sm btn-primary">Öğrenci Listesi →</a></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div class="alert alert-info" style="margin-bottom:16px">📚 Öğrenci doğrudan <strong><?= sanitize($course['name']) ?></strong> kursuna eklenecek.</div>

<div class="card" style="max-width:560px">
  <div class="card-header"><span class="card-title">➕ Yeni Öğrenci Ekle</span></div>
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
      <div class="alert alert-info" style="margin-bottom:16px">ℹ️ Öğrenci otomatik QR kod alır. Veli bilgisi sonradan cami yöneticisi tarafından eklenebilir.</div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">➕ Öğrenciyi Ekle</button>
    </form>
  </div>
</div>

<?php endif; ?>
<?php include 'layout/footer.php'; ?>
