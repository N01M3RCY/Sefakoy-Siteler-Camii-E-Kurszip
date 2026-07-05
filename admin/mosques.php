<?php
require_once '../config/db.php';
requireLogin('admin', 'login.php');
$db = getDB();

$success = $error = '';

// Durum güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id     = (int)$_POST['mosque_id'];
    $status = in_array($_POST['status'], ['active','inactive','pending']) ? $_POST['status'] : 'pending';
    $db->prepare("UPDATE mosques SET status=? WHERE id=?")->execute([$status, $id]);
    $success = 'Cami durumu güncellendi.';
}

// Cami ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mosque'])) {
    $fields = ['name','address','district','city','imam_name','phone','email','username','password','capacity','notes'];
    $data = [];
    foreach ($fields as $f) $data[$f] = trim($_POST[$f] ?? '');

    if (!$data['name'] || !$data['username'] || !$data['password']) {
        $error = 'Cami adı, kullanıcı adı ve şifre zorunludur.';
    } else {
        // Kullanıcı adı benzersiz mi?
        $chk = $db->prepare("SELECT id FROM mosques WHERE username=?");
        $chk->execute([$data['username']]);
        if ($chk->fetch()) {
            $error = 'Bu kullanıcı adı zaten alınmış.';
        } else {
            $stmt = $db->prepare("INSERT INTO mosques (name,address,district,city,imam_name,phone,email,username,password,capacity,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['name'], $data['address'], $data['district'], $data['city'] ?: 'İstanbul',
                $data['imam_name'], $data['phone'], $data['email'],
                $data['username'], hashPassword($data['password']),
                (int)($data['capacity'] ?: 50), 'active', $data['notes']
            ]);
            $success = 'Cami başarıyla eklendi.';
        }
    }
}

// Cami sil (POST ile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mosque'])) {
    $id = (int)$_POST['mosque_id'];
    $db->prepare("DELETE FROM mosques WHERE id=?")->execute([$id]);
    $success = 'Cami silindi.';
}

// Şifre sıfırla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $id = (int)$_POST['mosque_id'];
    $newPass = trim($_POST['new_password'] ?? '');
    if (strlen($newPass) >= 6) {
        $db->prepare("UPDATE mosques SET password=? WHERE id=?")->execute([hashPassword($newPass), $id]);
        $success = 'Cami şifresi güncellendi.';
    } else {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    }
}

// Filtre
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['search'] ?? '');
$where = '1=1';
$params = [];
if ($filter === 'pending') { $where .= " AND status='pending'"; }
if ($filter === 'active')  { $where .= " AND status='active'"; }
if ($search) {
    $where .= " AND (name LIKE ? OR district LIKE ? OR imam_name LIKE ? OR username LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s]);
}
$stmt = $db->prepare("SELECT m.*, (SELECT COUNT(*) FROM students WHERE mosque_id=m.id) as student_count FROM mosques m WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$mosques = $stmt->fetchAll();

$page_title = 'Cami Yönetimi';
include 'layout/header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <form method="get" style="display:flex;gap:8px;flex:1;min-width:240px">
      <input type="text" name="search" class="form-control" placeholder="🔍 Cami adı, ilçe, imam..." value="<?= sanitize($search) ?>" style="max-width:300px">
      <button class="btn btn-primary">Ara</button>
      <?php if ($search || $filter): ?><a href="mosques.php" class="btn btn-secondary">Temizle</a><?php endif; ?>
    </form>
    <div style="display:flex;gap:8px">
      <a href="mosques.php?filter=pending" class="btn btn-sm btn-gold <?= $filter==='pending'?'':'btn-secondary' ?>">⏳ Bekleyenler</a>
      <a href="mosques.php?filter=active"  class="btn btn-sm <?= $filter==='active'?'btn-primary':'btn-secondary' ?>">✅ Aktifler</a>
      <button onclick="openModal('addModal')" class="btn btn-primary">➕ Cami Ekle</button>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">🕌 Camiler (<?= count($mosques) ?>)</span>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Cami Adı</th>
          <th>İlçe / Şehir</th>
          <th>İmam</th>
          <th>Kullanıcı Adı</th>
          <th>Öğrenci</th>
          <th>Durum</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mosques as $m): ?>
        <tr>
          <td style="color:#94a3b8;font-size:12px"><?= $m['id'] ?></td>
          <td>
            <strong><?= sanitize($m['name']) ?></strong>
            <?php if ($m['phone']): ?><br><small style="color:#94a3b8">📞 <?= sanitize($m['phone']) ?></small><?php endif; ?>
          </td>
          <td><?= sanitize($m['district'] ?? '—') ?> / <?= sanitize($m['city'] ?? '—') ?></td>
          <td><?= sanitize($m['imam_name'] ?? '—') ?></td>
          <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px"><?= sanitize($m['username']) ?></code></td>
          <td><span class="badge badge-info"><?= $m['student_count'] ?></span></td>
          <td>
            <form method="post" style="display:flex;gap:4px;align-items:center">
              <input type="hidden" name="mosque_id" value="<?= $m['id'] ?>">
              <select name="status" class="form-control" style="width:120px;padding:4px 8px;font-size:13px">
                <option value="active"   <?= $m['status']==='active'  ?'selected':'' ?>>✅ Aktif</option>
                <option value="pending"  <?= $m['status']==='pending' ?'selected':'' ?>>⏳ Bekliyor</option>
                <option value="inactive" <?= $m['status']==='inactive'?'selected':'' ?>>❌ Pasif</option>
              </select>
              <button name="update_status" class="btn btn-sm btn-primary">Kaydet</button>
            </form>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <button onclick="openResetModal(<?= $m['id'] ?>, '<?= addslashes(sanitize($m['name'])) ?>')" class="btn btn-sm btn-secondary" title="Şifre Sıfırla">🔑</button>
              <form method="post" style="display:inline" onsubmit="return confirm('Bu camiyi ve tüm verilerini silmek istediğinize emin misiniz?')">
                <input type="hidden" name="mosque_id" value="<?= $m['id'] ?>">
                <button type="submit" name="delete_mosque" class="btn btn-sm btn-danger" title="Sil">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($mosques)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🕌</div>
            <div class="empty-state-title">Cami bulunamadı</div>
            <div class="empty-state-desc">Arama kriterlerinizi değiştirin veya yeni cami ekleyin.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Cami Ekle Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">🕌 Yeni Cami Ekle</span>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="post">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Cami Adı *</label>
            <input type="text" name="name" class="form-control" placeholder="Merkez Camii" required>
          </div>
          <div class="form-group">
            <label class="form-label">İmam Adı</label>
            <input type="text" name="imam_name" class="form-control" placeholder="Ahmet Yılmaz">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">İlçe</label>
            <input type="text" name="district" class="form-control" placeholder="Kadıköy">
          </div>
          <div class="form-group">
            <label class="form-label">Şehir</label>
            <input type="text" name="city" class="form-control" placeholder="İstanbul" value="İstanbul">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Adres</label>
          <textarea name="address" class="form-control" placeholder="Mahalle, sokak, kapı no..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Telefon</label>
            <input type="tel" name="phone" class="form-control" placeholder="0212 xxx xx xx">
          </div>
          <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" placeholder="cami@ornek.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Kullanıcı Adı *</label>
            <input type="text" name="username" class="form-control" placeholder="merkez_camii" required>
          </div>
          <div class="form-group">
            <label class="form-label">Şifre *</label>
            <input type="password" name="password" class="form-control" placeholder="En az 6 karakter" required minlength="6">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Kapasite</label>
            <input type="number" name="capacity" class="form-control" value="50" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Notlar</label>
            <input type="text" name="notes" class="form-control" placeholder="Opsiyonel not...">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">İptal</button>
        <button type="submit" name="add_mosque" class="btn btn-primary">✅ Camiyi Kaydet</button>
      </div>
    </form>
  </div>
</div>

<!-- Şifre Sıfırla Modal -->
<div class="modal-overlay" id="resetModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="resetModalTitle">🔑 Şifre Sıfırla</span>
      <button class="modal-close" onclick="closeModal('resetModal')">✕</button>
    </div>
    <form method="post">
      <div class="modal-body">
        <input type="hidden" name="mosque_id" id="resetMosqueId">
        <div class="form-group">
          <label class="form-label">Yeni Şifre</label>
          <input type="password" name="new_password" class="form-control" placeholder="En az 6 karakter" required minlength="6">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('resetModal')" class="btn btn-secondary">İptal</button>
        <button type="submit" name="reset_password" class="btn btn-primary">Şifreyi Güncelle</button>
      </div>
    </form>
  </div>
</div>

<script>
function openResetModal(id, name) {
  document.getElementById('resetMosqueId').value = id;
  document.getElementById('resetModalTitle').textContent = '🔑 Şifre Sıfırla: ' + name;
  openModal('resetModal');
}
<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
document.addEventListener('DOMContentLoaded', () => openModal('addModal'));
<?php endif; ?>
</script>

<?php include 'layout/footer.php'; ?>
