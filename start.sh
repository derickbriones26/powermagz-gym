#!/bin/bash

# 1. Ensure MariaDB run directory exists
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld

# 2. Start MariaDB in the background
echo "Starting MariaDB..."
mysqld_safe &

# 3. WAIT until MariaDB is fully ready (instead of just sleeping 5 seconds)
until mysqladmin ping -h 127.0.0.1 --silent; do
    echo "Waiting for MariaDB to accept connections..."
    sleep 2
done
echo "MariaDB is up and running!"

# 4. Create the database and user (Supports both localhost and TCP 127.0.0.1)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS powermagz_gym;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'powermagz'@'127.0.0.1' IDENTIFIED BY 'secret';"
mysql -u root -e "CREATE USER IF NOT EXISTS 'powermagz'@'localhost' IDENTIFIED BY 'secret';"
mysql -u root -e "GRANT ALL PRIVILEGES ON powermagz_gym.* TO 'powermagz'@'127.0.0.1';"
mysql -u root -e "GRANT ALL PRIVILEGES ON powermagz_gym.* TO 'powermagz'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# 5. Import the schema and demo data
if ! mysql -u root -e "USE powermagz_gym; SHOW TABLES;" | grep -q "users"; then
    echo "Importing schema.sql..."
    mysql -u root powermagz_gym < /var/www/html/database/schema.sql
    echo "Database imported successfully!"
fi

# 6. Start Apache in the foreground (keeps the container alive)
apache2-foreground