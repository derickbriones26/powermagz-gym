#!/bin/bash

# 1. Ensure MariaDB run directory exists
mkdir -p /var/run/mysqld
chown mysql:mysql /var/run/mysqld

# 2. Start MariaDB in the background
mysqld_safe &
sleep 5

# 3. Create the database and user (matches your .env settings)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS powermagz_gym;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'powermagz'@'localhost' IDENTIFIED BY 'secret';"
mysql -u root -e "GRANT ALL PRIVILEGES ON powermagz_gym.* TO 'powermagz'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# 4. Import the schema and demo data (only runs if the users table is missing)
if ! mysql -u root -e "USE powermagz_gym; SHOW TABLES;" | grep -q "users"; then
    echo "Importing schema.sql..."
    mysql -u root powermagz_gym < /var/www/html/database/schema.sql
    echo "Database imported successfully!"
fi

# 5. Start Apache in the foreground (keeps the container alive)
apache2-foreground