#!/usr/bin/env bash
# School Management ERP & M50 Biometric Attendance System — Mac/Linux launcher.
# Windows equivalent: run_project.bat
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

echo "==============================================================================="
echo "       School Management ERP & M50 Biometric Attendance System"
echo "==============================================================================="
echo

OS="$(uname -s)"
DB_NAME="doorknob_school"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"

# ---------------------------------------------------------------------------
# 1. Locate/install PHP (8.2 — this app's locked dependencies don't support
#    newer PHP; a plain `brew install php` on a fresh Mac pulls the latest
#    version, which is too new here).
# ---------------------------------------------------------------------------
find_php() {
    if command -v php >/dev/null 2>&1; then
        php -v | head -1 | grep -qE "PHP (7\.[3-9]|8\.[0-2])" && { command -v php; return; }
    fi
    if [ "$OS" = "Darwin" ] && command -v /opt/homebrew/opt/php@8.2/bin/php >/dev/null 2>&1; then
        echo "/opt/homebrew/opt/php@8.2/bin/php"; return
    fi
    if [ "$OS" = "Darwin" ] && command -v /usr/local/opt/php@8.2/bin/php >/dev/null 2>&1; then
        echo "/usr/local/opt/php@8.2/bin/php"; return
    fi
    echo ""
}

PHP_EXE="$(find_php)"
if [ -z "$PHP_EXE" ]; then
    echo "[INFO] Compatible PHP (7.3–8.2) not found."
    if [ "$OS" = "Darwin" ]; then
        if ! command -v brew >/dev/null 2>&1; then
            echo "[ERROR] Homebrew not found. Install it first: https://brew.sh"
            exit 1
        fi
        echo "[INFO] Installing PHP 8.2 and Composer via Homebrew..."
        brew install php@8.2 composer
        brew link --force --overwrite php@8.2
        PHP_EXE="$(find_php)"
    elif [ "$OS" = "Linux" ]; then
        if command -v apt-get >/dev/null 2>&1; then
            echo "[INFO] Installing PHP 8.2 and Composer via apt..."
            sudo apt-get update
            sudo apt-get install -y php8.2 php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip composer mysql-server
        elif command -v dnf >/dev/null 2>&1; then
            echo "[INFO] Installing PHP 8.2 and Composer via dnf..."
            sudo dnf install -y php php-cli php-mysqlnd php-mbstring php-xml php-curl php-zip composer mysql-server
        else
            echo "[ERROR] No supported package manager found (apt/dnf). Install PHP 8.2, Composer, and MySQL manually."
            exit 1
        fi
        PHP_EXE="$(find_php)"
    else
        echo "[ERROR] Unsupported OS: $OS"
        exit 1
    fi
fi

if [ -z "$PHP_EXE" ]; then
    echo "[ERROR] PHP install failed or still not on PATH. Install PHP 8.2 manually and re-run."
    exit 1
fi
echo "[OK] Using PHP: $PHP_EXE"

# ---------------------------------------------------------------------------
# 2. Locate/install MySQL server + client.
# ---------------------------------------------------------------------------
if ! command -v mysql >/dev/null 2>&1; then
    echo "[INFO] MySQL client not found."
    if [ "$OS" = "Darwin" ]; then
        echo "[INFO] Installing MySQL via Homebrew (no Gatekeeper issues — unlike XAMPP)..."
        brew install mysql
        brew services start mysql
    elif [ "$OS" = "Linux" ]; then
        echo "[INFO] MySQL should have been installed above. If not, install mysql-server via your package manager."
        sudo systemctl start mysql 2>/dev/null || sudo systemctl start mysqld 2>/dev/null || true
    fi
elif [ "$OS" = "Darwin" ]; then
    brew services start mysql >/dev/null 2>&1 || true
elif [ "$OS" = "Linux" ]; then
    sudo systemctl start mysql 2>/dev/null || sudo systemctl start mysqld 2>/dev/null || true
fi

MYSQL_CMD="mysql -h 127.0.0.1 -P 3306 -u root"
[ -n "$DB_ROOT_PASSWORD" ] && MYSQL_CMD="$MYSQL_CMD -p$DB_ROOT_PASSWORD"

echo "[OK] Using MySQL client: $(command -v mysql)"

# ---------------------------------------------------------------------------
# 3. Composer dependencies (only if vendor/ missing — skip on repeat runs).
# ---------------------------------------------------------------------------
if [ ! -d "vendor" ]; then
    echo "[INFO] Installing Composer dependencies..."
    composer install --no-interaction
fi

# ---------------------------------------------------------------------------
# 4. .env — pinned to MySQL (single DB engine everywhere, dev = prod).
# ---------------------------------------------------------------------------
if [ ! -f ".env" ]; then
    echo "[INFO] Creating .env file from .env.example..."
    cp .env.example .env
fi

sed -i.bak \
    -e "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" \
    -e "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" \
    -e "s/^DB_PORT=.*/DB_PORT=3306/" \
    -e "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" \
    -e "s/^DB_USERNAME=.*/DB_USERNAME=root/" \
    -e "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_ROOT_PASSWORD}/" \
    .env
rm -f .env.bak

if ! grep -q "^APP_KEY=.\+" .env; then
    echo "[INFO] Generating application key..."
    "$PHP_EXE" artisan key:generate --force
fi

# ---------------------------------------------------------------------------
# 5. Database + migrations (idempotent — safe to re-run).
# ---------------------------------------------------------------------------
echo "[INFO] Ensuring database \"$DB_NAME\" exists..."
if ! $MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null; then
    echo "[ERROR] Could not reach MySQL. Make sure the MySQL service is running, then re-run this script."
    exit 1
fi

echo "[INFO] Running database migrations..."
"$PHP_EXE" artisan migrate --force

USER_COUNT="$("$PHP_EXE" artisan tinker --execute="echo \DB::table('users')->count();" 2>/dev/null | tail -1)"
if [ "$USER_COUNT" = "0" ]; then
    echo "[INFO] First run detected - seeding default accounts..."
    "$PHP_EXE" artisan db:seed --force
fi

# ---------------------------------------------------------------------------
# 6. Frontend assets (only if node_modules/ missing).
# ---------------------------------------------------------------------------
if [ ! -d "node_modules" ] && command -v npm >/dev/null 2>&1; then
    echo "[INFO] Installing npm dependencies and building assets..."
    npm install
    npm run dev
fi

# ---------------------------------------------------------------------------
# 7. Caches + serve
# ---------------------------------------------------------------------------
"$PHP_EXE" artisan config:clear >/dev/null 2>&1 || true
"$PHP_EXE" artisan route:clear >/dev/null 2>&1 || true
"$PHP_EXE" artisan view:clear >/dev/null 2>&1 || true

echo
echo "==============================================================================="
echo "  Server starting at: http://127.0.0.1:8000"
echo "  Other computers on this network can reach it at:"
if [ "$OS" = "Darwin" ]; then
    ipconfig getifaddr en0 2>/dev/null | sed 's/^/  http:\/\//; s/$/:8000/' || true
else
    hostname -I 2>/dev/null | awk '{print "  http://"$1":8000"}' || true
fi
echo "==============================================================================="
echo "  Admin Login:       admin@ut.com / password"
echo "  Super Admin Login: DOORKNOB@SU / SU@ADMINDOORKNOB"
echo "==============================================================================="
echo "  Press Ctrl+C to stop the server."
echo "==============================================================================="
echo

PHP_CLI_SERVER_WORKERS=4 "$PHP_EXE" artisan serve --host=0.0.0.0 --port=8000
