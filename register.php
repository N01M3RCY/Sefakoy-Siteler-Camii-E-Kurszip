<?php
require_once 'config/db.php';
session_start_safe();

$db = getDB();
$success = $error = '';

// Aktif camileri çek
$mosques = $db->query("SELECT id, name, district, city FROM mosques WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Veli bilgileri
    $p_name    = trim($_POST['p_name'] ?? '');
    $p_surname = trim($_POST['p_surname'] ?? '');
    $p_tc      = trim($_POST['p_tc'] ?? '');
    $p_phone   = trim($_POST['p_phone'] ?? '');
    $p_email   = trim($_POST['p_email'] ?? '');
    $p_address = trim($_POST['p_address'] ?? '');

    // Öğrenci bilgileri
    $s_name       = trim($_POST['s_name'] ?? '');
    $s_surname    = trim($_POST['s_surname'] ?? '');
    $s_tc         = trim($_POST['s_tc'] ?? '');
    $s_birth      = trim($_POST['s_birth'] ?? '');
    $s_gender     = $_POST['s_gender'] ?? '';
    $mosque_id    = (int)($_POST['mosque_id'] ?? 0);
    $s_notes      = trim($_POST['s_notes'] ?? '');

    // Validasyon
    $errors = [];
    if (!$p_name)    $errors[] = 'Veli adı zorunludur.';
    if (!$p_surname) $errors[] = 'Veli soyadı zorunludur.';
    if (!$p_phone)   $errors[] = 'Veli telefonu zorunludur.';
    if (!$s_name)    $errors[] = 'Öğrenci adı zorunludur.';
    if (!$s_surname) $errors[] = 'Öğrenci soyadı zorunludur.';
    if (!in_array($s_gender, ['male','female'])) $errors[] = 'Öğrenci cinsiyeti seçilmelidir.';
    if (!$mosque_id) $errors[] = 'Cami seçimi zorunludur.';

    // Cami var mı?
    if ($mosque_id) {
        $chk = $db->prepare("SELECT id FROM mosques WHERE id=? AND status='active'");
        $chk->execute([$mosque_id]);
        if (!$chk->fetch()) { $errors[] = 'Seçilen cami geçersiz.'; $mosque_id = 0; }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Veli kaydet (TC ile zaten varsa bul)
            $parent_id = null;
            if ($p_tc) {
                $chk = $db->prepare("SELECT id FROM parents WHERE tc_no=?");
                $chk->execute([$p_tc]);
                $existing = $chk->fetchColumn();
                if ($existing) $parent_id = $existing;
            }
            if (!$parent_id) {
                $stmt = $db->prepare("INSERT INTO parents (name,surname,tc_no,phone,email,address) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$p_name, $p_surname, $p_tc ?: null, $p_phone, $p_email ?: null, $p_address ?: null]);
                $parent_id = $db->lastInsertId();
            }

            // QR kod üret (benzersiz)
            do {
                $qr = 'QR' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                $chk = $db->prepare("SELECT id FROM students WHERE qr_code=?");
                $chk->execute([$qr]);
            } while ($chk->fetch());

            // Öğrenci kaydet
            $stmt = $db->prepare("INSERT INTO students (mosque_id,parent_id,name,surname,tc_no,birth_date,gender,qr_code,notes) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $mosque_id, $parent_id,
                $s_name, $s_surname,
                $s_tc ?: null,
                $s_birth ?: null,
                $s_gender,
                $qr,
                $s_notes ?: null
            ]);
            $student_id = $db->lastInsertId();

            $db->commit();
            $success = $qr;  // QR kodunu başarı mesajında kullan

        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Kayıt sırasında hata oluştu. Lütfen tekrar deneyin.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Öğrenci Kayıt Formu · Cami Sistemi</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="register-page">
  <div class="register-box">
    <div class="register-box-header">
      <div class="mosque-icon">📋</div>
      <h1>Öğrenci Kayıt Formu</h1>
      <p>Cami Kuran Kursu Kayıt Başvurusu</p>
      <div class="register-steps">
        <div class="step active"><div class="step-num">1</div><div class="step-label">Veli Bilgileri</div></div>
        <div class="step active"><div class="step-num">2</div><div class="step-label">Öğrenci Bilgileri</div></div>
        <div class="step active"><div class="step-num">3</div><div class="step-label">Cami Seçimi</div></div>
      </div>
    </div>

    <div class="register-form-body">

      <?php if ($success): ?>
      <!-- BAŞARI EKRANI -->
      <div style="text-align:center;padding:20px 0">
        <div style="font-size:80px;margin-bottom:16px">✅</div>
        <h2 style="color:#1a7a3a;font-size:24px;margin-bottom:8px">Kayıt Başarılı!</h2>
        <p style="color:#64748b;margin-bottom:24px">Öğrenci kaydınız başarıyla oluşturuldu.</p>

        <div class="qr-card-print" style="margin:0 auto 24px">
          <div style="font-size:12px;font-weight:700;color:#1a7a3a;margin-bottom:12px;text-transform:uppercase;letter-spacing:1px">ÖĞRENCİ KİMLİK KARTI</div>
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?= urlencode($success) ?>&color=0d5c2e" width="160" height="160" alt="QR Kod">
          <div class="qr-student-name"><?= sanitize(($_POST['s_name'] ?? '').' '.($_POST['s_surname'] ?? '')) ?></div>
          <div class="qr-mosque-name">
            <?php
            $selMosque = array_filter($mosques, fn($m) => $m['id'] == ($_POST['mosque_id'] ?? 0));
            echo $selMosque ? sanitize(reset($selMosque)['name']) : '';
            ?>
          </div>
          <div class="qr-code-text"><?= sanitize($success) ?></div>
        </div>

        <div class="alert alert-warning" style="text-align:left">
          ⚠️ <strong>Önemli:</strong> Bu QR kodu kaydedin veya ekran görüntüsü alın. Camiye her gelişte yoklama için kullanılacaktır.
        </div>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:16px">
          <a href="qr.php?code=<?= urlencode($success) ?>" target="_blank" class="btn btn-primary btn-lg">🖨️ QR Kartı Yazdır</a>
          <a href="register.php" class="btn btn-secondary btn-lg">📋 Yeni Kayıt</a>
          <a href="index.php" class="btn btn-secondary btn-lg">🏠 Ana Sayfa</a>
        </div>
      </div>

      <?php else: ?>
      <!-- KAYIT FORMU -->
      <?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>
      <?php if (empty($mosques)): ?><div class="alert alert-warning">⚠️ Henüz kayıtlı aktif cami bulunmuyor.</div><?php endif; ?>

      <form method="post" id="registerForm">

        <!-- VELİ BİLGİLERİ -->
        <div class="section-title">👨‍👩‍👧 Veli Bilgileri</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Veli Adı *</label>
            <input type="text" name="p_name" class="form-control" placeholder="Adınız" required value="<?= sanitize($_POST['p_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Veli Soyadı *</label>
            <input type="text" name="p_surname" class="form-control" placeholder="Soyadınız" required value="<?= sanitize($_POST['p_surname'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">TC Kimlik No</label>
            <input type="text" name="p_tc" class="form-control" placeholder="11 haneli TC No" maxlength="11" pattern="\d{11}" value="<?= sanitize($_POST['p_tc'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Cep Telefonu *</label>
            <input type="tel" name="p_phone" class="form-control" placeholder="0555 xxx xx xx" required value="<?= sanitize($_POST['p_phone'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email" name="p_email" class="form-control" placeholder="ornek@email.com" value="<?= sanitize($_POST['p_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Adres</label>
            <input type="text" name="p_address" class="form-control" placeholder="İkametgah adresi" value="<?= sanitize($_POST['p_address'] ?? '') ?>">
          </div>
        </div>

        <!-- ÖĞRENCİ BİLGİLERİ -->
        <div class="section-title" style="margin-top:28px">📚 Öğrenci Bilgileri</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Öğrenci Adı *</label>
            <input type="text" name="s_name" class="form-control" placeholder="Öğrencinin adı" required value="<?= sanitize($_POST['s_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Öğrenci Soyadı *</label>
            <input type="text" name="s_surname" class="form-control" placeholder="Öğrencinin soyadı" required value="<?= sanitize($_POST['s_surname'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">TC Kimlik No</label>
            <input type="text" name="s_tc" class="form-control" placeholder="11 haneli TC No" maxlength="11" pattern="\d{11}" value="<?= sanitize($_POST['s_tc'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Doğum Tarihi</label>
            <input type="date" name="s_birth" class="form-control" value="<?= sanitize($_POST['s_birth'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Cinsiyet *</label>
          <div style="display:flex;gap:16px;margin-top:6px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 20px;border:2px solid <?= ($_POST['s_gender']??'')==='male'?'#1a7a3a':'#e2e8f0' ?>;border-radius:8px;flex:1;transition:.2s" id="male-label">
              <input type="radio" name="s_gender" value="male" <?= ($_POST['s_gender']??'')==='male'?'checked':'' ?> required style="accent-color:#1a7a3a;width:18px;height:18px"> 
              <span style="font-size:20px">👦</span> <strong>Erkek</strong>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:12px 20px;border:2px solid <?= ($_POST['s_gender']??'')==='female'?'#1a7a3a':'#e2e8f0' ?>;border-radius:8px;flex:1;transition:.2s" id="female-label">
              <input type="radio" name="s_gender" value="female" <?= ($_POST['s_gender']??'')==='female'?'checked':'' ?> style="accent-color:#1a7a3a;width:18px;height:18px"> 
              <span style="font-size:20px">👧</span> <strong>Kız</strong>
            </label>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notlar (Opsiyonel)</label>
          <textarea name="s_notes" class="form-control" placeholder="Özel durum, sağlık bilgisi vb."><?= sanitize($_POST['s_notes'] ?? '') ?></textarea>
        </div>

        <!-- CAMİ SEÇİMİ -->
        <div class="section-title" style="margin-top:28px">🕌 Cami Seçimi</div>
        <div class="form-group">
          <label class="form-label">Hangi camiye kayıt olmak istiyorsunuz? *</label>
          <select name="mosque_id" class="form-control" required>
            <option value="">-- Cami Seçin --</option>
            <?php foreach ($mosques as $m): ?>
            <option value="<?= $m['id'] ?>" <?= ($_POST['mosque_id']??0)==$m['id']?'selected':'' ?>>
              <?= sanitize($m['name']) ?><?= $m['district'] ? ' — '.sanitize($m['district']) : '' ?><?= $m['city'] ? ' / '.sanitize($m['city']) : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- ONAY -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:20px">
          <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer">
            <input type="checkbox" required style="margin-top:3px;accent-color:#1a7a3a;width:18px;height:18px;flex-shrink:0">
            <span style="font-size:13px;color:#475569">
              Yukarıda verdiğim bilgilerin doğru olduğunu, <strong>kişisel verilerin işlenmesine</strong> ilişkin aydınlatma metnini okuduğumu ve onayladığımı beyan ederim.
            </span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="font-size:17px;padding:16px">
          📋 Kaydı Tamamla
        </button>
      </form>

      <div style="text-align:center;margin-top:24px;font-size:13px;color:#94a3b8">
        <a href="index.php">← Ana Sayfaya Dön</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Cinsiyet seçiminde border highlight
document.querySelectorAll('input[name="s_gender"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.getElementById('male-label').style.borderColor   = this.value === 'male'   ? '#1a7a3a' : '#e2e8f0';
    document.getElementById('female-label').style.borderColor = this.value === 'female' ? '#1a7a3a' : '#e2e8f0';
  });
});
</script>
</body>
</html>
