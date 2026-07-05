#!/usr/bin/env bash
# Bu script nix-shell -p mariadb php83 ortamında çalıştırılır
# Doğrudan çalıştırma: nix-shell -p mariadb php83 --run 'bash start.sh'

MYSQL_DIR="$HOME/mysql_data"
MYSQL_SOCK="$HOME/mysql.sock"
MYSQL_LOG="$HOME/mysql.log"
DB_NAME="cami_sistemi"
DB_USER="cami_user"
DB_PASS="cami_pass_2025"

echo "🕌 Cami Yönetim Sistemi başlatılıyor..."

# ─── PHP sunucuyu hemen başlat (port 5000 açılsın) ───────
echo "🚀 PHP sunucu port 5000'de başlatılıyor..."
php -S 0.0.0.0:5000 -t . &
PHP_PID=$!

# ─── MariaDB arka planda hazırla ─────────────────────────
(
  if [ ! -d "$MYSQL_DIR" ]; then
    echo "📦 Veritabanı dizini oluşturuluyor (ilk kurulum)..."
    # --auth-root-authentication-method=normal: root için şifre tabanlı auth kullan
    mysql_install_db \
      --datadir="$MYSQL_DIR" \
      --skip-test-db \
      --auth-root-authentication-method=normal \
      > /dev/null 2>&1 || \
    mysql_install_db \
      --datadir="$MYSQL_DIR" \
      --skip-test-db \
      > /dev/null 2>&1 || true
    echo "✅ Veritabanı dizini hazır."
  fi

  if ! mysqladmin --socket="$MYSQL_SOCK" ping --silent 2>/dev/null; then
    echo "🔄 MariaDB başlatılıyor..."
    mysqld_safe \
      --datadir="$MYSQL_DIR" \
      --socket="$MYSQL_SOCK" \
      --skip-networking \
      --log-error="$MYSQL_LOG" \
      --pid-file="$HOME/mysql.pid" \
      > /dev/null 2>&1 &

    for i in $(seq 1 45); do
      if mysqladmin --socket="$MYSQL_SOCK" ping --silent 2>/dev/null; then
        echo "✅ MariaDB hazır ($i s)."
        break
      fi
      sleep 1
    done
  else
    echo "✅ MariaDB zaten çalışıyor."
  fi

  # root ile bağlan (şifresiz, normal auth)
  echo "🔐 Kullanıcı ve veritabanı oluşturuluyor..."
  mysql --socket="$MYSQL_SOCK" -u root --password="" 2>/dev/null <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
  echo "✅ Veritabanı hazır: $DB_NAME"

  # PHP config
  cat > config/db.local.php <<PHP
<?php
define('DB_HOST',   'localhost');
define('DB_USER',   '$DB_USER');
define('DB_PASS',   '$DB_PASS');
define('DB_NAME',   '$DB_NAME');
define('DB_SOCKET', '$MYSQL_SOCK');
PHP
  echo "✅ Config yazıldı."

  # Tablolar (idempotent)
  php -r "
require 'config/db.php';
\$db = getDB();
\$tables = \$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('admins', \$tables)) {
    \$db->exec(file_get_contents('schema.sql'));
    echo '✅ Tablolar oluşturuldu.' . PHP_EOL;
} else {
    try { \$db->exec('ALTER TABLE students MODIFY COLUMN parent_id INT NULL'); } catch(Exception \$e) {}
    try { \$db->exec('ALTER TABLE students ADD COLUMN age INT NULL AFTER birth_date'); } catch(Exception \$e) {}
    try { \$db->exec(\"CREATE TABLE IF NOT EXISTS duas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mosque_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        category ENUM('sabah','oglen','ikindi','aksam','yatsi','genel','ozel') DEFAULT 'genel',
        scheduled_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
    ) ENGINE=InnoDB\"); } catch(Exception \$e) {}
    try { \$db->exec(\"CREATE TABLE IF NOT EXISTS homeworks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mosque_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        due_date DATE NULL,
        status ENUM('active','done') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
    ) ENGINE=InnoDB\"); } catch(Exception \$e) {}
    echo '✅ Tablolar güncellendi.' . PHP_EOL;
}
" 2>/dev/null || true

  echo ""
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "  🔐 Admin:      admin / admin123"
  echo "  🕌 Demo Cami:  merkez_camii / admin123"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
) &

# PHP process'i foreground'da tut
wait $PHP_PID
