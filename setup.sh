#!/bin/bash
# setup.sh — Setup otomatis project Kuisioner SMD Puskemas Kebon Jeruk 2026
set -uo pipefail

echo "======================================================"
echo "  Setup Kuisioner SMD - PUSKESMAS KEBON JERUK 2026"
echo "======================================================"
echo ""

# 1. Cek PHP
echo "[1/5] Cek environment..."
if ! command -v php &>/dev/null; then
    echo "ERROR: PHP tidak ditemukan. Install PHP 8.2+ (XAMPP/MAMP)."
    exit 1
fi
PHP_VER=$(php -r "echo PHP_VERSION;")
echo "PHP ditemukan: $PHP_VER"
if [[ $(php -r "echo version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0;") -eq 1 ]]; then
    echo "ERROR: PHP 8.2+ diperlukan."
    exit 1
fi
for ext in curl xml json mbstring openssl; do
    php -m | grep -qi "$ext" || { echo "WARNING: ekstensi $ext tidak terpasang."; }
done
echo "Ekstensi dicek."

# 2. Inisialisasi folder
echo ""
echo "[2/5] Membuat folder..."
SRC="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
mkdir -p "$SRC/data" "$SRC/asset" "$SRC/install"
echo "Folder siap: data/, asset/."

# 3. Copy data contoh kuesioner
if [ -f "$SRC/data/kuesioner.json" ]; then
    echo "data/kuesioner.json sudah ada, tidak di-overwrite."
else
    cp "$SRC/install/kuesioner.json.example" "$SRC/data/kuesioner.json" 2>/dev/null || true
    echo "data/kuesioner.json dibuat (kosong)."
fi

# 4. Siapkan .htaccess (kalau Apache)
echo ""
echo "[4/5] Siapkan routing..."
cat > "$SRC/.htaccess" <<'HTACCESS'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
HTACCESS
echo ".htaccess dibuat (Apache)."

# 5. Cek & selesai
echo ""
echo "[5/5] Selesai."
echo "Buka di browser:"
echo "  - Form:   http://localhost/<folder>/form.php"
echo "  - Admin:  http://localhost/<folder>/dashboard.php"
echo "  - Login:  admin / Adm#Sikda2026!rm"
