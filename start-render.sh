#!/usr/bin/env bash
# Render.com Docker ortamında çalışır — harici TiDB Cloud Serverless (MySQL uyumlu) veritabanına bağlanır
set -e

PORT="${PORT:-724}"

: "${TIDB_HOST:?TIDB_HOST ortam değişkeni ayarlanmalı (TiDB Cloud bağlantı bilgileri)}"
: "${TIDB_PORT:=4000}"
: "${TIDB_USER:?TIDB_USER ortam değişkeni ayarlanmalı}"
: "${TIDB_PASSWORD:?TIDB_PASSWORD ortam değişkeni ayarlanmalı}"
: "${TIDB_DATABASE:=cami_sistemi}"

echo "🕌 Cami Yönetim Sistemi (Render + TiDB Cloud) başlatılıyor..."

# Debian sistem CA bundle'ı TiDB Cloud sertifikasını doğrulamak için yeterli (herkese açık CA ile imzalı)
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
echo "✅ Config yazıldı ($TIDB_HOST:$TIDB_PORT)."

echo "🔌 TiDB Cloud bağlantısı test ediliyor..."
cd /app
for i in $(seq 1 10); do
  if php -r "require 'config/db.php'; getDB();" 2>/tmp/db_err.log; then
    echo "✅ Bağlantı başarılı."
    break
  fi
  if [ "$i" -eq 10 ]; then
    echo "❌ TiDB Cloud'a bağlanılamadı. TIDB_HOST / TIDB_USER / TIDB_PASSWORD değerlerini kontrol edin."
    cat /tmp/db_err.log
    exit 1
  fi
  sleep 2
done

# ─── Tabloları kur / güncelle (idempotent) ────────────────
php -r "
require 'config/db.php';
\$db = getDB();
\$tables = \$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('admins', \$tables)) {
    \$db->exec(file_get_contents('schema.sql'));
    echo '✅ Tablolar oluşturuldu.' . PHP_EOL;
} else {
    echo 'ℹ️  Tablolar zaten mevcut, veriler korunuyor.' . PHP_EOL;
}
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  🗄️  Veritabanı: TiDB Cloud ($TIDB_DATABASE)"
echo "  🌐 Port:        $PORT"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "🚀 PHP sunucu port $PORT'de başlatılıyor..."
exec php -S 0.0.0.0:"$PORT" -t /app
