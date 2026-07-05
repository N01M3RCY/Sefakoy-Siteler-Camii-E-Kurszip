// =============================================
// CAMİ YÖNETİM SİSTEMİ - ANA JS
// =============================================

// Modal işlemleri
function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.remove('show');
    document.body.style.overflow = '';
  }
}

// Overlay'e tıklayınca kapat
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
    document.body.style.overflow = '';
  }
});

// ESC ile modal kapat
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(function(m) {
      m.classList.remove('show');
      document.body.style.overflow = '';
    });
  }
});

// Alert'leri otomatik kapat (3 saniye)
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.alert-success, .alert-error').forEach(function(alert) {
    // Başarı mesajlarını 4 saniyede kapat
    if (alert.classList.contains('alert-success')) {
      setTimeout(function() {
        alert.style.transition = 'opacity .5s';
        alert.style.opacity = '0';
        setTimeout(function() { alert.style.display = 'none'; }, 500);
      }, 4000);
    }
  });
});

// QR Kod otomatik büyük harf
document.querySelectorAll('input[name="qr_code"]').forEach(function(input) {
  input.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
  });
  // Enter tuşunda form submit (QR tarayıcı enter basabilir)
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      this.closest('form').submit();
    }
  });
});

// TC No sadece rakam
document.querySelectorAll('input[name="p_tc"], input[name="s_tc"]').forEach(function(input) {
  input.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 11);
  });
});

// Telefon formatlama
document.querySelectorAll('input[type="tel"]').forEach(function(input) {
  input.addEventListener('input', function() {
    let val = this.value.replace(/\D/g, '');
    this.value = val;
  });
});

// Tablo satırı tıklanınca highlight
document.querySelectorAll('.table tbody tr').forEach(function(row) {
  row.style.cursor = 'pointer';
});

// Form submit loading state
document.querySelectorAll('form').forEach(function(form) {
  form.addEventListener('submit', function() {
    const btns = form.querySelectorAll('button[type="submit"]');
    btns.forEach(function(btn) {
      if (!btn.dataset.noLoading) {
        btn.disabled = true;
        btn.innerHTML = '⏳ ' + btn.innerHTML;
      }
    });
  });
});
