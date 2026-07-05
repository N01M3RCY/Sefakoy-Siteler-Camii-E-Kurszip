#!/bin/bash
# Cami Yönetim Sistemi - Replit Başlatma Scripti

set -e

MYSQL_DIR="$HOME/mysql_data"
MYSQL_SOCK="$HOME/mysql.sock"
MYSQL_LOG="$HOME/mysql.log"
DB_NAME="cami_sistemi"
DB_USER="cami_user"
DB_PASS="cami_pass_2025"

echo "🕌 Cami Yönetim Sistemi başlatılıyor..."

# ─── MariaDB Başlat ───────────────────────────────────────
if [ ! -d "$MYSQL_DIR" ]; then
  echo "📦 Veritabanı ilk kez oluşturuluyor..."
  mysql_install_db --datadir="$MYSQL_DIR" --auth-root-authentication-method=normal > /dev/null 2>&1
  echo "✅ Veritabanı dizini hazır."
fi

# mysqld çalışıyor mu?
if ! mysqladmin --socket="$MYSQL_SOCK" ping --silent 2>/dev/null; then
  echo "🔄 MariaDB başlatılıyor..."
  mysqld_safe \
    --datadir="$MYSQL_DIR" \
    --socket="$MYSQL_SOCK" \
    --skip-networking \
    --log-error="$MYSQL_LOG" \
    --pid-file="$HOME/mysql.pid" \
    --user=runner \
    > /dev/null 2>&1 &

  # Hazır olana kadar bekle (max 30s)
  for i in $(seq 1 30); do
    if mysqladmin --socket="$MYSQL_SOCK" ping --silent 2>/dev/null; then
      echo "✅ MariaDB hazır."
      break
    fi
    sleep 1
    if [ $i -eq 30 ]; then
      echo "❌ MariaDB başlatılamadı. Log: $MYSQL_LOG"
      cat "$MYSQL_LOG" 2>/dev/null | tail -20
      exit 1
    fi
  done
fi

# ─── Veritabanı + Kullanıcı Oluştur ─────────────────────
mysql --socket="$MYSQL_SOCK" -u root 2>/dev/null <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "✅ Veritabanı hazır: $DB_NAME"

# ─── Local Config Oluştur ────────────────────────────────
cat > config/db.local.php <<PHP
<?php
// Replit ortamı için otomatik oluşturuldu
define('DB_HOST',   'localhost');
define('DB_USER',   '$DB_USER');
define('DB_PASS',   '$DB_PASS');
define('DB_NAME',   '$DB_NAME');
define('DB_SOCKET', '$MYSQL_SOCK');
PHP
echo "✅ Local config yazıldı."

# ─── Kurulum (install.php mantığını burada çalıştır) ─────
php -r "
require 'config/db.php';
\$db = getDB();
\$tables = \$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('admins', \$tables)) {
    echo 'Tablolar oluşturuluyor...' . PHP_EOL;
    \$db->exec(file_get_contents('schema.sql'));
    echo 'Tablolar hazır.' . PHP_EOL;
} else {
    echo 'Tablolar zaten mevcut.' . PHP_EOL;
}
" 2>/dev/null || true

# ─── PHP Sunucu Başlat ────────────────────────────────────
echo ""
echo "🚀 PHP sunucu başlatılıyor → http://0.0.0.0:5000"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🔐 Admin:  admin / admin123"
echo "  📋 Veli Kayıt: /register.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

exec php -S 0.0.0.0:5000 -t .
