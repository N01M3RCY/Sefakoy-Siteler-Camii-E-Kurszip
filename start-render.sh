#!/usr/bin/env bash
# Render.com Docker ortam\u0131nda \u00e7al\u0131\u015f\u0131r \u2014 harici TiDB Cloud Serverless (MySQL uyumlu) veritaban\u0131na ba\u011flan\u0131r
set -e

PORT="${PORT:-724}"

: "${TIDB_HOST:?TIDB_HOST ortam de\u011fi\u015fkeni ayarlanmal\u0131 (TiDB Cloud ba\u011flant\u0131 bilgileri)}"
: "${TIDB_PORT:=4000}"
: "${TIDB_USER:?TIDB_USER ortam de\u011fi\u015fkeni ayarlanmal\u0131}"
: "${TIDB_PASSWORD:?TIDB_PASSWORD ortam de\u011fi\u015fkeni ayarlanmal\u0131}"
: "${TIDB_DATABASE:=cami_sistemi}"

echo "\ud83d\udd4c Cami Y\u00f6netim Sistemi (Render + TiDB Cloud) ba\u015flat\u0131l\u0131yor..."

# Debian sistem CA bundle'\u0131 TiDB Cloud sertifikas\u0131n\u0131 do\u011frulamak i\u00e7in yeterli (herkese a\u00e7\u0131k CA ile imzal\u0131)
SSL_CA_PATH="/etc/ssl/certs/ca-certificates.crt"

cat > /app/config/db.local.php <<PHP
<?php
define('DB_HOST',   '$TIDB_HOST');
define('DB_PORT',   $TIDB_PORT);
define('DB_USER',   '$TIDB_USER');
define('DB_PASS',   '$TIDB_PASSWORD');
define('DB_NAME',   '$TIDB_DATABASE');
define('DB_SOCKET', '');
define('DB_SSL_CA', '$SSL_CA_PATH');
PHP
echo "\u2705 Config yaz\u0131ld\u0131 ($TIDB_HOST:$TIDB_PORT)."

echo "\ud83d\udd0c TiDB Cloud ba\u011flant\u0131s\u0131 test ediliyor..."
cd /app

# Veritaban\u0131n\u0131n varl\u0131\u011f\u0131n\u0131 kontrol et, yoksa olu\u015ftur
php -r "
require 'config/db.php';
try {
    \$db = getDB();
    echo '\u2705 Ba\u011flant\u0131 ba\u015far\u0131l\u0131.' . PHP_EOL;
} catch (PDOException \$e) {
    if (strpos(\$e->getMessage(), 'database') !== false || strpos(\$e->getMessage(), 'Database') !== false) {
        echo '\ud83d\udcdd Veritaban\u0131 bulunamad\u0131, olu\u015fturuluyor...' . PHP_EOL;
    } else {
        throw \$e;
    }
}
" 2>/tmp/db_check.log

if grep -q "Veritaban\u0131 bulunamad\u0131" /tmp/db_check.log 2>/dev/null; then
  echo "\ud83d\udcdd Veritaban\u0131 olu\u015fturuluyor..."
  php -r "
  require 'config/db.php';
  \$pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);
  \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS \`$TIDB_DATABASE\`\");
  echo '\u2705 Veritaban\u0131 olu\u015fturuldu.' . PHP_EOL;
  " 2>/tmp/db_create.log
  if [ $? -ne 0 ]; then
    echo "\u274c Veritaban\u0131 olu\u015fturulamad\u0131. TiDB Cloud konsolundan manuel olu\u015fturabilirsiniz."
    cat /tmp/db_create.log
    exit 1
  fi
fi

# \u2500\u2500\u2500 Tablolar\u0131 kur / g\u00fcncelle (idempotent) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
php -r "
require 'config/db.php';
\$db = getDB();
\$tables = \$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('admins', \$tables)) {
    \$db->exec(file_get_contents('schema.sql'));
    echo '\u2705 Tablolar olu\u015fturuldu.' . PHP_EOL;
} else {
    echo '\u2139\ufe0f  Tablolar zaten mevcut, veriler korunuyor.' . PHP_EOL;
}
"

echo ""
echo "\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501"
echo "  \ud83d\uddc4\ufe0f  Veritaban\u0131: TiDB Cloud ($TIDB_DATABASE)"
echo "  \ud83c\udf10 Port:        $PORT"
echo "\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501"

echo "\ud83d\ude80 PHP sunucu port $PORT'de ba\u015flat\u0131l\u0131yor..."
exec php -S 0.0.0.0:"$PORT" -t /app
