# Cami Öğrenci Yönetim Sistemi

PHP + MariaDB tabanlı cami eğitim yönetim uygulaması.

## Nasıl Çalıştırılır

Workflow: **Start application**  
Komut: `nix-shell -p mariadb php83 --run 'bash start.sh'`  
Port: **5000**

`start.sh` şunları yapar:
1. PHP sunucuyu hemen başlatır (port 5000)
2. Arka planda MariaDB'yi başlatır ve veritabanını kurar
3. İlk çalıştırmada tabloları oluşturur; sonrakilerde günceller

## Giriş Bilgileri

| Rol | Kullanıcı | Şifre |
|-----|-----------|-------|
| Admin | `admin` | `admin123` |
| Demo Cami | `merkez_camii` | `admin123` |

## Panel Yapısı

- `/admin/` — Müftülük / Sistem yöneticisi paneli
- `/mosque/` — Cami personeli paneli
- `/parent/` — Veli paneli

## Özellikler

### Cami Paneli
- **Öğrenci Ekle** (`/mosque/add_student.php`) — Sadece isim + yaş ile doğrudan öğrenci ekleme (veli zorunlu değil), yaş grubu dağılımı grafiği
- **Yoklama** (`/mosque/attendance.php`) — QR tarama + manuel toplu seçim ile yoklama, kayıt silme
- **Ödevler** (`/mosque/homeworks.php`) — Ödev ekle/tamamla/sil, son tarih takibi
- **Dua Sistemi** (`/mosque/duas.php`) — Kategori bazlı dua yönetimi (sabah/öğlen/ikindi/akşam/yatsı/genel/özel)
- **Öğrencilerim** (`/mosque/students.php`) — Veli bağlı veya bağımsız öğrenci listesi

### Veli Paneli
- **Devam Durumu** — Aylık takvim görünümü
- **Ödevler** (`/parent/homeworks.php`) — Camiden gelen aktif ödevleri görüntüleme

## Veritabanı

| Tablo | Açıklama |
|-------|----------|
| `admins` | Sistem yöneticileri |
| `mosques` | Cami hesapları |
| `parents` | Veli hesapları |
| `students` | Öğrenciler (parent_id NULL olabilir, age alanı var) |
| `attendance` | Yoklama kayıtları |
| `duas` | Dua sistemi kayıtları |
| `homeworks` | Ödev kayıtları |

## Teknik Notlar

- PHP 8.3 (`php83` nix paketi)
- MariaDB 10.11 (`mariadb` nix paketi)
- `--auth-root-authentication-method=normal` ile root şifresiz, normal auth
- DB bağlantısı: Unix socket (`~/mysql.sock`), `cami_user` / `cami_pass_2025`
- Eğer DB sıfırlanması gerekirse: `rm -rf ~/mysql_data ~/mysql.sock ~/mysql.pid` sonra workflow'u yeniden başlatın

## User Preferences

- Sistem Türkçe arayüzle çalışmalı
- Öğrenciler veli olmadan da eklenebilmeli (yaş bazlı kontrol uygulaması)
