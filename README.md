## Prerequisites
### System
- FFMpeg / FFProbe
- Composer

### PHP
- php-curl
- php-gd
- php-pdo
- php-mysql
- php-memcached

### Apache Modules
- php
- rewrite
- ssl (optional, recommended)

## Setup
Run a composer install with `composer install` from the project base directory to install dependencies

Go to backend/config.php and set up your instance

Esnure the configured callback hostname is pointed to `127.0.0.1` in the system hosts file for speedy callbacks locally