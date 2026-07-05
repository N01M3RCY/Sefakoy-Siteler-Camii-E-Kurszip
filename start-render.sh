#!/usr/bin/env bash
# Render.com Docker ortamında çalışır (start.sh'in Render için uyarlanmış hali)
set -e

DB_NAME="cami_sistemi"
DB_USER="cami_user"
DB_PASS="cami_pass_2025"
PORT="${PORT:-724}"
MYSQL_DATADIR="/var/lib/mysql"

echo "🕌 Cami Yönetim Sistemi (Render) başlatılıyor..."

# ─── MariaDB veri dizinini ilk kez hazırla ───────────────
if [ ! -d "$MYSQL_DATADIR/mysql" ]; then
  echo "📦 Veritabanı dizini oluşturuluyor (ilk kurulum)..."
  mariadb-install-db --user=root --datadir="$MYSQL_DATADIR" > /dev/null 2>&1 || \
  mysql_install_db --user=root --datadir="$MYSQL_DATADIR" > /dev/null 2>&1 || true
  echo "✅ Veritabanı dizini hazır."
fi

# ─── MariaDB'yi başlat ────────────────────────────────────
echo "🔄 MariaDB başlatılıyor..."
mysqld_safe --user=root --datadir="$MYSQL_DATADIR" --bind-address=127.0.0.1 > /var/log/mysql_start.log 2>&1 &

for i in $(seq 1 45); do
  if mysqladmin ping --silent 2>/dev/null; then
    echo "✅ MariaDB hazır ($i s)."
    break
  fi
  sleep 1
done

# ─── Kullanıcı ve veritabanı oluştur ──────────────────────
echo "🔐 Kullanıcı ve veritabanı oluşturuluyor..."
mysql -u root 2>/dev/null <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "✅ Veritabanı hazır: $DB_NAME"

# ─── PHP config yaz ───────────────────────────────────────
cat > /app/config/db.local.php <<PHP
<?php
define('DB_HOST',   '127.0.0.1');
define('DB_USER',   '$DB_USER');
define('DB_PASS',   '$DB_PASS');
define('DB_NAME',   '$DB_NAME');
define('DB_SOCKET', '');
PHP
echo "✅ Config yazıldı."

# ─── Tabloları kur / güncelle (idempotent) ────────────────
cd /app
php -r "
require 'config/db.php';
\$db = getDB();
\$tables = \$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('admins', \$tables)) {
    \$db->exec(file_get_contents('schema.sql'));
    echo '✅ Tablolar oluşturuldu.' . PHP_EOL;
} else {
    echo 'ℹ️  Tablolar zaten mevcut.' . PHP_EOL;
}
\$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
\$db->prepare('UPDATE admins SET password=? WHERE username=?')->execute([\$adminHash, 'admin']);
" || true

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🔐 Admin:      admin / admin123"
echo "  🕌 Demo Cami:  merkez_camii / admin123"
echo "  🌐 Port:       $PORT"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# ─── PHP sunucuyu foreground'da başlat ────────────────────
echo "🚀 PHP sunucu port $PORT'de başlatılıyor..."
exec php -S 0.0.0.0:"$PORT" -t /app
