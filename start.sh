#!/bin/bash

# Database credentials come from the environment. The fallback matches the
# local development defaults, but production deployments MUST override them.
DB_NAME="${DB_NAME:-powermagz_gym}"
DB_USER="${DB_USER:-powermagz}"
DB_PASS="${DB_PASS:-secret}"

# Export so the Apache/PHP processes use the same credentials as this script.
export DB_NAME DB_USER DB_PASS

# Identifiers are interpolated into SQL below; reject anything unexpected.
case "$DB_NAME" in
    ''|*[!A-Za-z0-9_]*) echo "ERROR: DB_NAME may only contain letters, digits and underscores."; exit 1 ;;
esac
case "$DB_USER" in
    ''|*[!A-Za-z0-9_]*) echo "ERROR: DB_USER may only contain letters, digits and underscores."; exit 1 ;;
esac

# Escape backslashes/quotes before embedding the password in SQL.
DB_PASS_SQL=${DB_PASS//\\/\\\\}
DB_PASS_SQL=${DB_PASS_SQL//\'/\\\'}

# 1. Ensure MariaDB run directory exists
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld

# 2. Start MariaDB in the background with memory limits for Render Free Tier
echo "Starting MariaDB..."
mysqld_safe --innodb-buffer-pool-size=32M --key-buffer-size=8M &

# 3. WAIT until MariaDB is fully ready (force TCP connection)
echo "Waiting for MariaDB..."
for i in {1..30}; do
    if mysqladmin ping -h 127.0.0.1 --silent; then
        echo "MariaDB is up and running!"
        break
    fi
    echo "Waiting for MariaDB to accept connections... ($i/30)"
    sleep 2
done

# Final check
if ! mysqladmin ping -h 127.0.0.1 --silent; then
    echo "ERROR: MariaDB failed to start. Exiting."
    exit 1
fi

# 4. Create the database and user (matches DB_* environment variables)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;"
mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS_SQL}';"
mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS_SQL}';"
mysql -u root -e "ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS_SQL}';"
mysql -u root -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS_SQL}';"
mysql -u root -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';"
mysql -u root -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# 5. Import the schema (only if the users table is missing)
if ! mysql -u root -e "USE \`${DB_NAME}\`; SHOW TABLES;" | grep -q "users"; then
    echo "Importing schema.sql..."
    if mysql -u root "${DB_NAME}" < /var/www/html/database/schema.sql; then
        echo "Database imported successfully!"
    else
        echo "ERROR: Failed to import schema.sql. Check the SQL file for errors."
        exit 1
    fi
else
    echo "Database already initialized."
fi

# 5b. Rotate/disable the demo credentials that ship in schema.sql. This is
# idempotent: the marker in system_settings prevents re-rotating the admin
# password on every restart. If it fails, stop rather than serve known creds.
echo "Hardening seeded accounts..."
if ! php /var/www/html/scripts/harden_demo_accounts.php; then
    echo "ERROR: Could not harden seeded demo accounts. Exiting."
    exit 1
fi

# 6. Start Apache in the foreground (Port is set in Dockerfile)
echo "Starting Apache..."
apache2-foreground
