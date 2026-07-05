# Cami Öğrenci Yönetim Sistemi

Müftülük / Cami Kuran Kursu Öğrenci Kayıt ve Yönetim Sistemi.

## Proje Yapısı

```
/
├── index.php              # Ana giriş sayfası (3 seçenek)
├── register.php           # Veli öğrenci kayıt formu (herkese açık)
├── qr.php                 # QR kod / öğrenci kimlik kartı
├── install.php            # Veritabanı kurulum scripti (bir kez çalıştır, sonra sil)
├── config/
│   └── db.php             # Veritabanı bağlantısı ve yardımcı fonksiyonlar
├── admin/
│   ├── login.php          # Admin giriş
│   ├── index.php          # Admin kontrol paneli
│   ├── mosques.php        # Cami yönetimi (ekle/sil/durum/şifre sıfırla)
│   ├── students.php       # Tüm öğrenciler
│   ├── parents.php        # Tüm veliler
│   ├── change_password.php
│   ├── logout.php
│   └── layout/
│       ├── header.php
│       └── footer.php
├── mosque/
│   ├── register.php       # Cami kayıt formu
│   ├── login.php          # Cami giriş
│   ├── index.php          # Cami kontrol paneli
│   ├── students.php       # Cami öğrenci listesi + QR kodlar
│   ├── attendance.php     # QR yoklama sistemi
│   ├── change_password.php
│   ├── logout.php
│   └── layout/
│       ├── header.php
│       └── footer.php
└── assets/
    ├── css/style.css
    └── js/main.js
```

## Teknoloji Yığını

- **Backend:** PHP (InfinityFree uyumlu, PDO + MySQL)
- **Frontend:** Saf HTML/CSS/JavaScript (framework yok)
- **Veritabanı:** MySQL 5.7+
- **QR Kod:** api.qrserver.com (ücretsiz, CDN tabanlı)

## Kurulum (InfinityFree)

1. Tüm dosyaları InfinityFree File Manager ile yükleyin
2. **config/db.php** dosyasını InfinityFree veritabanı bilgileriyle güncelleyin:
   - `DB_HOST`: genellikle `sql200.infinityfree.com` gibi bir şey
   - `DB_USER`: InfinityFree veritabanı kullanıcı adı
   - `DB_PASS`: InfinityFree veritabanı şifresi
   - `DB_NAME`: InfinityFree veritabanı adı
3. Aynı değerleri **install.php** dosyasına da yazın
4. `https://siteniz.infinityfreeapp.com/install.php` adresine gidin
5. Kurulum tamamlandıktan sonra **install.php dosyasını silin!**
6. Admin girişi: kullanıcı adı `admin`, şifre `admin123` (hemen değiştirin!)

## Kullanıcı Rolleri

| Rol | Giriş | Yetkiler |
|-----|-------|---------|
| **Admin** | `/admin/login.php` | Tüm camiler, öğrenciler, veliler; cami onay/red |
| **Cami** | `/mosque/login.php` | Kendi öğrencileri, QR yoklama, cami bilgileri |
| **Veli** | Hesap yok | Sadece `/register.php` üzerinden kayıt |

## Özellikler

- ✅ Admin paneli — cami yönetimi (ekle, sil, onayla, şifre sıfırla)
- ✅ Cami kaydı + cami girişi (ayrı sayfalar)
- ✅ Veli + öğrenci kayıt formu (cami seçimi ile)
- ✅ QR kod sistemi — her öğrenciye benzersiz QR
- ✅ Yazdırılabilir öğrenci kimlik kartı (qr.php)
- ✅ QR tarama ile yoklama (mosque/attendance.php)
- ✅ Yeşil/altın müftülük teması
- ✅ Mobil uyumlu responsive tasarım
- ✅ InfinityFree uyumlu (saf PHP, harici kütüphane yok)

## User Preferences

- Türkçe dil kullanımı
- InfinityFree PHP hosting uyumluluğu zorunlu
- Müftülük / resmi kuruma yakışır profesyonel tasarım
- Yeşil (#1a7a3a) ve altın (#c9a227) renk teması
