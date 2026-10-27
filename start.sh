#!/bin/bash

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

# 4. Create the database and user (matches .env)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS powermagz_gym;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'powermagz'@'127.0.0.1' IDENTIFIED BY 'secret';"
mysql -u root -e "CREATE USER IF NOT EXISTS 'powermagz'@'localhost' IDENTIFIED BY 'secret';"
mysql -u root -e "GRANT ALL PRIVILEGES ON powermagz_gym.* TO 'powermagz'@'127.0.0.1';"
mysql -u root -e "GRANT ALL PRIVILEGES ON powermagz_gym.* TO 'powermagz'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# 5. Import the schema and demo data (only if the users table is missing)
if ! mysql -u root -e "USE powermagz_gym; SHOW TABLES;" | grep -q "users"; then
    echo "Importing schema.sql..."
    if mysql -u root powermagz_gym < /var/www/html/database/schema.sql; then
        echo "Database imported successfully!"
    else
        echo "ERROR: Failed to import schema.sql. Check the SQL file for errors."
        exit 1
    fi
else
    echo "Database already initialized."
fi

# 6. Start Apache in the foreground (Port is set in Dockerfile)
echo "Starting Apache..."
apache2-foreground