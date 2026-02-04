# ÅbenForms Deployment Guide

**Version**: 1.0
**Last Updated**: February 2026
**Target Audience**: DevOps Engineers, System Administrators

This guide provides comprehensive instructions for deploying the ÅbenForms headless Drupal 11 backend to production environments.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Platform.sh Deployment](#platformsh-deployment)
3. [Alternative Hosting Options](#alternative-hosting-options)
4. [Environment Configuration](#environment-configuration)
5. [Database Migration](#database-migration)
6. [Cache Configuration](#cache-configuration)
7. [CDN Setup](#cdn-setup)
8. [Monitoring Setup](#monitoring-setup)
9. [Backup Procedures](#backup-procedures)
10. [Rollback Procedures](#rollback-procedures)
11. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Server Requirements

**Minimum Production Environment**:

| Resource | Minimum | Recommended | Notes |
|----------|---------|-------------|-------|
| **CPU** | 2 cores | 4 cores | 8 cores for high-traffic municipalities |
| **RAM** | 4 GB | 8 GB | 16 GB for 10,000+ daily users |
| **Storage** | 20 GB SSD | 50 GB SSD | 100 GB for long-term data retention |
| **PHP** | 8.4 | 8.4 | Required for Drupal 11.3 |
| **Database** | MariaDB 10.11 | MariaDB 10.11 | Consistent with DDEV local environment |
| **Web Server** | Nginx 1.24+ | Nginx 1.24+ | Apache 2.4+ also supported |
| **SSL/TLS** | TLS 1.2 | TLS 1.3 | Required for HTTPS |

**PHP Extensions Required**:
```bash
php8.4-cli
php8.4-fpm
php8.4-mysql
php8.4-gd
php8.4-curl
php8.4-xml
php8.4-mbstring
php8.4-zip
php8.4-opcache
php8.4-redis      # For caching
php8.4-apcu       # For additional caching
php8.4-bcmath     # For encryption
php8.4-intl       # For internationalization
```

### DNS Configuration

**A Records** (IPv4):
```
aabenforms.dk          → <production_ip>
api.aabenforms.dk      → <production_ip>
*.aabenforms.dk        → <production_ip>  # For multi-tenancy
```

**AAAA Records** (IPv6, if available):
```
aabenforms.dk          → <production_ipv6>
api.aabenforms.dk      → <production_ipv6>
```

**CNAME Records** (if using CDN):
```
cdn.aabenforms.dk      → <cloudflare_cname>
```

**CAA Records** (Certificate Authority Authorization):
```
aabenforms.dk          → 0 issue "letsencrypt.org"
aabenforms.dk          → 0 issuewild "letsencrypt.org"
```

### SSL/TLS Certificates

**Option 1: Let's Encrypt (Recommended)**:
```bash
# Install Certbot
apt-get update
apt-get install -y certbot python3-certbot-nginx

# Generate certificate
certbot --nginx -d aabenforms.dk -d api.aabenforms.dk -d "*.aabenforms.dk"

# Auto-renewal (cron job)
echo "0 3 * * * certbot renew --quiet --post-hook 'systemctl reload nginx'" | crontab -
```

**Option 2: Commercial Certificate**:
- Purchase from DigiCert, Sectigo, or GlobalSign
- Generate CSR with wildcard support
- Install certificate in `/etc/ssl/certs/`

### Required Accounts/Services

- [x] **Platform.sh Account** (if using Platform.sh)
- [x] **KOMBIT Serviceplatformen** access (for Danish integrations)
- [x] **MitID Test Environment** access
- [x] **Email Service** (SendGrid, Mailgun, or SMTP)
- [x] **CDN Account** (Cloudflare recommended)
- [x] **Monitoring Service** (New Relic, Datadog, or self-hosted)
- [x] **Backup Storage** (S3, Backblaze B2, or similar)

---

## Platform.sh Deployment

Platform.sh is the **recommended** hosting solution for ÅbenForms, providing:
- Automated scaling
- Built-in Redis caching
- Daily backups
- Git-based deployments
- EU data residency compliance

### Initial Setup

**1. Install Platform.sh CLI**:
```bash
curl -fsS https://raw.githubusercontent.com/platformsh/cli/main/installer.sh | bash
```

**2. Authenticate**:
```bash
platform login
```

**3. Create New Project**:
```bash
# Navigate to project root
cd /path/to/aabenforms/backend

# Create Platform.sh project
platform create \
  --title "ÅbenForms Production" \
  --region eu-5.platform.sh \
  --plan standard \
  --storage 5120 \
  --environments 3
```

**Region Selection**:
- `eu-5.platform.sh` - **Frankfurt, Germany** (recommended for GDPR compliance)
- `eu-3.platform.sh` - Paris, France
- `eu-4.platform.sh` - Amsterdam, Netherlands

### Configuration Files

**Create `.platform.app.yaml`** in project root:

```yaml
name: aabenforms
type: 'php:8.4'

runtime:
  extensions:
    - redis
    - apcu
    - bcmath

variables:
  php:
    memory_limit: 512M
    max_execution_time: 300
    upload_max_filesize: 100M
    post_max_size: 100M
    opcache.memory_consumption: 256
    opcache.max_accelerated_files: 20000

disk: 5120

web:
  locations:
    '/':
      root: 'web'
      passthru: '/index.php'
      index:
        - index.php
      expires: 5m
      scripts: true
      allow: false
      rules:
        '\.(jpe?g|png|gif|svgz?|css|js|map|ico|bmp|eot|woff2?|otf|ttf)$':
          allow: true
          expires: 2w
        '^/robots\.txt$':
          allow: true
        '^/sitemap\.xml$':
          allow: true
        '^/sites/sites\.php$':
          scripts: false
        '^/sites/[^/]+/settings.*?\.php$':
          scripts: false

    '/sites/default/files':
      allow: true
      expires: 1d
      passthru: '/index.php'
      root: 'web/sites/default/files'
      scripts: false
      rules:
        '^/sites/default/files/private/':
          allow: false
        '^/sites/default/files/php':
          allow: false

mounts:
  '/web/sites/default/files':
    source: 'local'
    source_path: 'files'
  '/tmp':
    source: 'local'
    source_path: 'tmp'
  '/private':
    source: 'local'
    source_path: 'private'

relationships:
  database: 'db:mysql'
  redis: 'cache:redis'

hooks:
  build: |
    set -e
    composer install --no-dev --optimize-autoloader --no-interaction

  deploy: |
    set -e
    # Run database updates
    php web/core/scripts/drupal quick-start install
    drush updatedb -y

    # Import configuration
    drush config:import -y

    # Clear caches
    drush cache:rebuild

    # Generate encryption key (first deploy only)
    if [ ! -f /app/private/encryption.key ]; then
      openssl rand -base64 32 > /app/private/encryption.key
      chmod 600 /app/private/encryption.key
    fi

crons:
  drupal:
    spec: '*/15 * * * *'
    commands:
      start: 'drush core:cron'

  backup:
    spec: '0 2 * * *'
    commands:
      start: |
        drush sql:dump --gzip --result-file=/app/private/backups/db-$(date +\%Y\%m\%d-\%H\%M\%S).sql
        find /app/private/backups -type f -mtime +7 -delete
```

**Create `.platform/services.yaml`**:

```yaml
db:
  type: mariadb:10.11
  disk: 4096
  configuration:
    schemas:
      - main
    endpoints:
      mysql:
        default_schema: main
        privileges:
          main: admin

cache:
  type: redis:7.2
  configuration:
    maxmemory_policy: allkeys-lru

search:
  type: solr:8.11
  disk: 1024
  configuration:
    cores:
      mainindex:
        conf_dir: !archive "solr/conf"
```

**Create `.platform/routes.yaml`**:

```yaml
"https://{default}/":
  type: upstream
  upstream: "aabenforms:http"
  cache:
    enabled: true
    default_ttl: 0
    cookies: ['*']
    headers: ['Accept', 'Accept-Language']

"https://www.{default}/":
  type: redirect
  to: "https://{default}/"

"https://api.{default}/":
  type: upstream
  upstream: "aabenforms:http"
  cache:
    enabled: false

# Multi-tenancy wildcard
"https://*.{default}/":
  type: upstream
  upstream: "aabenforms:http"
  cache:
    enabled: true
    default_ttl: 300
```

### Deployment Steps

**1. Push Code to Platform.sh**:
```bash
# Add Platform.sh remote
platform project:set-remote

# Deploy to production
git push platform main

# Or deploy specific branch to environment
git push platform feature/new-workflow:staging
```

**2. Configure Environment Variables**:
```bash
# Set production environment variables
platform variable:create \
  --name env:ENVIRONMENT \
  --value production \
  --level environment

platform variable:create \
  --name env:HASH_SALT \
  --value "$(openssl rand -base64 55)" \
  --level environment \
  --sensitive true

platform variable:create \
  --name env:TRUSTED_HOST_PATTERNS \
  --value '^(aabenforms\.dk|.*\.aabenforms\.dk|api\.aabenforms\.dk)$' \
  --level environment
```

**3. Database Import (First Deployment)**:
```bash
# Export local database
ddev drush sql:dump --gzip > aabenforms-db.sql.gz

# Import to Platform.sh
platform sql < aabenforms-db.sql.gz
```

**4. Files Sync**:
```bash
# Sync local files to production
platform mount:upload \
  --mount web/sites/default/files \
  --source ./web/sites/default/files

# Or download from production to local
platform mount:download \
  --mount web/sites/default/files \
  --target ./web/sites/default/files
```

**5. Verify Deployment**:
```bash
# Check deployment status
platform environment:info

# View logs
platform logs --tail app

# SSH into production
platform ssh

# Test Drupal status
drush status
```

### Post-Deployment Configuration

**Enable Production Performance Settings**:
```bash
platform ssh

# Enable production caching
drush config:set system.performance css.preprocess 1 -y
drush config:set system.performance js.preprocess 1 -y
drush config:set system.performance cache.page.max_age 3600 -y

# Enable Redis caching
drush config:set redis.settings connection.interface PhpRedis -y
drush cr
```

**Configure MitID Production Endpoint**:
```bash
# Update OpenID Connect settings
drush config:set openid_connect.client.mitid settings.authorization_endpoint \
  "https://mitid.dk/connect/authorize" -y

drush config:set openid_connect.client.mitid settings.token_endpoint \
  "https://mitid.dk/connect/token" -y
```

### Scaling Configuration

**Vertical Scaling** (increase resources):
```bash
# Upgrade plan
platform subscription:info
platform subscription:update --plan large

# Increase storage
platform project:curl -X PATCH /projects/{PROJECT_ID} \
  -d '{"storage": 10240}'
```

**Horizontal Scaling** (multiple instances):

Edit `.platform.app.yaml`:
```yaml
resources:
  base_memory: 512
  memory_ratio: 512
```

Then redeploy:
```bash
git commit -am "Enable horizontal scaling"
git push platform main
```

---

## Alternative Hosting Options

### AWS (Amazon Web Services)

**Architecture**:
```
Application Load Balancer (ALB)
  ↓
Auto Scaling Group (2+ EC2 instances)
  ↓
RDS MariaDB 10.11 (Multi-AZ)
  ↓
ElastiCache Redis
  ↓
S3 (File storage)
  ↓
CloudFront (CDN)
```

**Setup Steps**:

**1. Launch EC2 Instances** (Ubuntu 24.04 LTS):
```bash
# Instance type: t3.medium (2 vCPU, 4 GB RAM)
# AMI: Ubuntu 24.04 LTS
# Security group: Allow 22 (SSH), 80 (HTTP), 443 (HTTPS)

# Install dependencies
sudo apt update
sudo apt install -y \
  nginx \
  php8.4-fpm \
  php8.4-cli \
  php8.4-mysql \
  php8.4-gd \
  php8.4-curl \
  php8.4-xml \
  php8.4-mbstring \
  php8.4-zip \
  php8.4-opcache \
  php8.4-redis \
  composer \
  git \
  certbot \
  python3-certbot-nginx

# Clone repository
cd /var/www
git clone https://github.com/youraccount/aabenforms.git
cd aabenforms/backend
composer install --no-dev --optimize-autoloader
```

**2. Create RDS Database**:
```bash
# Via AWS Console or CLI:
aws rds create-db-instance \
  --db-instance-identifier aabenforms-prod \
  --db-instance-class db.t3.medium \
  --engine mariadb \
  --engine-version 10.11 \
  --master-username admin \
  --master-user-password <SECURE_PASSWORD> \
  --allocated-storage 100 \
  --storage-type gp3 \
  --multi-az \
  --vpc-security-group-ids sg-xxxxxxxxx \
  --backup-retention-period 7 \
  --preferred-backup-window "03:00-04:00"
```

**3. Configure ElastiCache Redis**:
```bash
aws elasticache create-replication-group \
  --replication-group-id aabenforms-cache \
  --replication-group-description "ÅbenForms cache" \
  --engine redis \
  --cache-node-type cache.t3.small \
  --num-cache-clusters 2 \
  --automatic-failover-enabled
```

**4. Nginx Configuration** (`/etc/nginx/sites-available/aabenforms`):
```nginx
upstream php_backend {
    server unix:/var/run/php/php8.4-fpm.sock;
}

server {
    listen 80;
    listen [::]:80;
    server_name aabenforms.dk api.aabenforms.dk *.aabenforms.dk;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name aabenforms.dk *.aabenforms.dk;

    root /var/www/aabenforms/backend/web;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/aabenforms.dk/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aabenforms.dk/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # CORS for API
    location /jsonapi {
        add_header Access-Control-Allow-Origin "https://aabenforms-frontend.dk" always;
        add_header Access-Control-Allow-Methods "GET, POST, PATCH, DELETE, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Content-Type, Authorization" always;

        if ($request_method = 'OPTIONS') {
            return 204;
        }

        try_files $uri /index.php?$query_string;
    }

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php_backend;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_intercept_errors on;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_read_timeout 300;
    }

    location ~ ^/sites/.*/files/styles/ {
        try_files $uri /index.php?$query_string;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        log_not_found off;
        access_log off;
    }

    location ~ /\. {
        deny all;
    }

    location ~ ^/sites/.*/private/ {
        deny all;
    }
}
```

**5. Load Balancer Setup**:
```bash
# Create target group
aws elbv2 create-target-group \
  --name aabenforms-tg \
  --protocol HTTP \
  --port 80 \
  --vpc-id vpc-xxxxxxxx \
  --health-check-path /user/login \
  --health-check-interval-seconds 30

# Create ALB
aws elbv2 create-load-balancer \
  --name aabenforms-alb \
  --subnets subnet-xxxxx subnet-yyyyy \
  --security-groups sg-xxxxxxxx \
  --scheme internet-facing
```

### DigitalOcean

**Droplet Setup** (Managed Kubernetes recommended):

```bash
# Create Kubernetes cluster
doctl kubernetes cluster create aabenforms-prod \
  --region fra1 \
  --version 1.29.0-do.0 \
  --node-pool "name=worker-pool;size=s-2vcpu-4gb;count=3;auto-scale=true;min-nodes=2;max-nodes=5"

# Create managed database
doctl databases create aabenforms-db \
  --engine mysql \
  --version 8 \
  --region fra1 \
  --size db-s-2vcpu-4gb \
  --num-nodes 2

# Create Spaces (S3-compatible storage)
doctl compute spaces create aabenforms-files \
  --region fra1
```

### Self-Hosted (Bare Metal / VPS)

**Server Stack**:
- **OS**: Ubuntu 24.04 LTS
- **Web Server**: Nginx 1.24+
- **PHP**: 8.4 (via Ondřej Surý PPA)
- **Database**: MariaDB 10.11
- **Cache**: Redis 7.2
- **Process Manager**: Supervisor
- **Firewall**: UFW

**Installation Script**:

```bash
#!/bin/bash
set -e

echo "=== ÅbenForms Production Server Setup ==="

# Update system
apt update && apt upgrade -y

# Add PHP PPA
add-apt-repository ppa:ondrej/php -y
apt update

# Install packages
apt install -y \
  nginx \
  mariadb-server \
  redis-server \
  php8.4-fpm \
  php8.4-cli \
  php8.4-mysql \
  php8.4-gd \
  php8.4-curl \
  php8.4-xml \
  php8.4-mbstring \
  php8.4-zip \
  php8.4-opcache \
  php8.4-redis \
  php8.4-apcu \
  php8.4-bcmath \
  php8.4-intl \
  composer \
  git \
  certbot \
  python3-certbot-nginx \
  supervisor \
  ufw

# Configure firewall
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Secure MariaDB
mysql_secure_installation

# Create database
mysql -e "CREATE DATABASE aabenforms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'aabenforms'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON aabenforms.* TO 'aabenforms'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Clone repository
mkdir -p /var/www
cd /var/www
git clone https://github.com/youraccount/aabenforms.git
cd aabenforms/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chown -R www-data:www-data /var/www/aabenforms
chmod -R 755 /var/www/aabenforms/backend/web/sites/default/files

# Configure PHP-FPM
cat > /etc/php/8.4/fpm/pool.d/aabenforms.conf <<EOF
[aabenforms]
user = www-data
group = www-data
listen = /var/run/php/php8.4-fpm-aabenforms.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
php_value[memory_limit] = 512M
php_value[max_execution_time] = 300
php_value[upload_max_filesize] = 100M
php_value[post_max_size] = 100M
EOF

# Restart PHP-FPM
systemctl restart php8.4-fpm

# SSL certificate
certbot --nginx -d aabenforms.dk -d api.aabenforms.dk --non-interactive --agree-tos -m admin@aabenforms.dk

# Setup cron for Drupal
echo "*/15 * * * * www-data cd /var/www/aabenforms/backend && drush core:cron" >> /etc/crontab

echo "=== Setup complete! ==="
echo "Next steps:"
echo "1. Configure Nginx (see docs)"
echo "2. Import database"
echo "3. Configure environment variables"
echo "4. Run drush config:import"
```

---

## Environment Configuration

### .env File Setup

**Never commit `.env` to version control!**

**Create `/var/www/aabenforms/backend/.env`**:

```bash
# Environment
ENVIRONMENT=production

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=aabenforms
DB_USER=aabenforms
DB_PASSWORD=SECURE_DATABASE_PASSWORD
DB_DRIVER=mysql

# Drupal
HASH_SALT=GENERATE_WITH_drush_generate_hash_salt
TRUSTED_HOST_PATTERNS=^(aabenforms\.dk|.*\.aabenforms\.dk)$

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=SECURE_REDIS_PASSWORD

# File paths
PRIVATE_FILES_PATH=/var/www/aabenforms/backend/private
TEMP_PATH=/tmp

# MitID (Production)
MITID_CLIENT_ID=YOUR_MITID_CLIENT_ID
MITID_CLIENT_SECRET=YOUR_MITID_CLIENT_SECRET
MITID_ISSUER=https://mitid.dk
MITID_AUTHORIZATION_ENDPOINT=https://mitid.dk/connect/authorize
MITID_TOKEN_ENDPOINT=https://mitid.dk/connect/token
MITID_USERINFO_ENDPOINT=https://mitid.dk/connect/userinfo

# Serviceplatformen
SP_CERTIFICATE_PATH=/var/www/aabenforms/backend/private/certs/serviceplatformen.pem
SP_CERTIFICATE_PASSWORD=YOUR_CERT_PASSWORD
SP_CPR_ENDPOINT=https://service.serviceplatformen.dk/sf1520/PersonBaseDataExtended/1
SP_CVR_ENDPOINT=https://service.serviceplatformen.dk/sf1530/CvrOnline/1
SP_DIGITAL_POST_ENDPOINT=https://service.serviceplatformen.dk/sf1601/DigitalPost/1

# Email
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=YOUR_SENDGRID_API_KEY
SMTP_FROM_EMAIL=noreply@aabenforms.dk
SMTP_FROM_NAME="ÅbenForms"

# Monitoring
NEW_RELIC_LICENSE_KEY=YOUR_NEW_RELIC_KEY
NEW_RELIC_APP_NAME="ÅbenForms Production"

# Encryption
ENCRYPTION_KEY_PATH=/var/www/aabenforms/backend/private/encryption.key

# CORS
CORS_ALLOW_ORIGIN=https://aabenforms.dk,https://api.aabenforms.dk
```

### Drupal settings.php Configuration

**Edit `web/sites/default/settings.php`**:

```php
<?php

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();

// Database configuration
$databases['default']['default'] = [
  'database' => $_ENV['DB_NAME'],
  'username' => $_ENV['DB_USER'],
  'password' => $_ENV['DB_PASSWORD'],
  'host' => $_ENV['DB_HOST'],
  'port' => $_ENV['DB_PORT'],
  'driver' => $_ENV['DB_DRIVER'],
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

// Hash salt
$settings['hash_salt'] = $_ENV['HASH_SALT'];

// Trusted host patterns
$settings['trusted_host_patterns'] = [
  $_ENV['TRUSTED_HOST_PATTERNS'],
];

// File paths
$settings['file_private_path'] = $_ENV['PRIVATE_FILES_PATH'];
$settings['file_temp_path'] = $_ENV['TEMP_PATH'];

// Redis configuration
if (extension_loaded('redis')) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = $_ENV['REDIS_HOST'];
  $settings['redis.connection']['port'] = $_ENV['REDIS_PORT'];
  if (!empty($_ENV['REDIS_PASSWORD'])) {
    $settings['redis.connection']['password'] = $_ENV['REDIS_PASSWORD'];
  }
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['cache']['bins']['bootstrap'] = 'cache.backend.chainedfast';
  $settings['cache']['bins']['discovery'] = 'cache.backend.chainedfast';
  $settings['cache']['bins']['config'] = 'cache.backend.chainedfast';
}

// Reverse proxy configuration (if using load balancer)
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = [$_SERVER['REMOTE_ADDR']];
$settings['reverse_proxy_trusted_headers'] = \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;

// Environment indicator
$config['environment_indicator.indicator']['name'] = $_ENV['ENVIRONMENT'];
$config['environment_indicator.indicator']['bg_color'] = $_ENV['ENVIRONMENT'] === 'production' ? '#ff0000' : '#00ff00';

// Disable CSS/JS aggregation in dev, enable in production
if ($_ENV['ENVIRONMENT'] === 'production') {
  $config['system.performance']['css']['preprocess'] = TRUE;
  $config['system.performance']['js']['preprocess'] = TRUE;
} else {
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
}

// Disable update module in production
if ($_ENV['ENVIRONMENT'] === 'production') {
  $settings['update_free_access'] = FALSE;
}

// Error logging
$config['system.logging']['error_level'] = $_ENV['ENVIRONMENT'] === 'production' ? 'hide' : 'verbose';

// Syslog
$config['syslog.settings']['identity'] = 'aabenforms';
$config['syslog.settings']['facility'] = LOG_LOCAL0;

// Include Platform.sh settings if available
if (file_exists(__DIR__ . '/settings.platformsh.php')) {
  include __DIR__ . '/settings.platformsh.php';
}

// Include local development settings if available
if (file_exists(__DIR__ . '/settings.local.php') && $_ENV['ENVIRONMENT'] !== 'production') {
  include __DIR__ . '/settings.local.php';
}
```

---

## Database Migration

### Pre-Migration Checklist

- [ ] Backup existing database
- [ ] Test database dump locally
- [ ] Verify disk space on production server
- [ ] Schedule maintenance window
- [ ] Notify users of downtime

### Export from Local/Staging

```bash
# DDEV local export
ddev drush sql:dump --gzip --result-file=../aabenforms-$(date +%Y%m%d-%H%M%S).sql

# Alternative: Direct MySQL dump
ddev export-db --gzip --file=aabenforms-dump.sql.gz
```

### Import to Production

**Method 1: Drush (Recommended)**:
```bash
# SSH to production
ssh user@aabenforms.dk

# Navigate to project
cd /var/www/aabenforms/backend

# Import database
drush sql:drop -y
drush sql:cli < /path/to/aabenforms-dump.sql

# Or from gzipped file
gunzip -c /path/to/aabenforms-dump.sql.gz | drush sql:cli

# Run updates
drush updatedb -y

# Import configuration
drush config:import -y

# Clear caches
drush cache:rebuild

# Verify
drush status
```

**Method 2: MySQL Direct**:
```bash
# Drop existing database
mysql -u aabenforms -p -e "DROP DATABASE IF EXISTS aabenforms; CREATE DATABASE aabenforms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import
gunzip -c aabenforms-dump.sql.gz | mysql -u aabenforms -p aabenforms

# Or for large databases (shows progress)
pv aabenforms-dump.sql.gz | gunzip | mysql -u aabenforms -p aabenforms
```

### Platform.sh Database Import

```bash
# From local machine
platform sql < aabenforms-dump.sql.gz

# Or from URL
platform sql < https://backups.example.com/aabenforms-dump.sql.gz
```

### Post-Migration Tasks

```bash
# Update database schema
drush updatedb -y

# Import configuration
drush config:import -y

# Rebuild cache
drush cache:rebuild

# Verify site UUID matches
drush config:get system.site uuid

# Update file paths if changed
drush config:set system.file path.private /new/path/to/private -y
drush config:set system.file path.temporary /new/path/to/tmp -y

# Re-index search (if using Solr)
drush search-api:reset-tracker
drush search-api:index

# Test critical functionality
drush php:eval "echo 'Database connection: OK\n';"
```

### Troubleshooting Database Migration

**Problem**: Import fails with "max_allowed_packet" error

**Solution**:
```bash
# Increase MariaDB packet size
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf

# Add under [mysqld]:
max_allowed_packet = 512M

# Restart MariaDB
sudo systemctl restart mariadb
```

**Problem**: "Table doesn't exist" errors

**Solution**:
```bash
# Drop and recreate database
drush sql:drop -y
drush sql:create
drush sql:cli < aabenforms-dump.sql
drush updatedb -y
```

**Problem**: Configuration import fails

**Solution**:
```bash
# Check config directory
ls -la config/sync/

# Force import with UUID fix
drush config:set system.site uuid $(drush config:get system.site uuid --include-overridden --format=string) -y
drush config:import -y

# Or delete and re-import specific config
drush config:delete <config_name>
drush config:import -y
```

---

## Cache Configuration

### Redis Setup

**Install Redis**:
```bash
# Ubuntu/Debian
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf

# Recommended settings:
# maxmemory 2gb
# maxmemory-policy allkeys-lru
# save "" (disable RDB snapshots)
# appendonly yes (enable AOF)

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

**Drupal Redis Integration**:

```bash
# Install PhpRedis extension
sudo apt install -y php8.4-redis

# Install Redis module
composer require drupal/redis

# Enable module
drush pm:enable redis -y

# Configure in settings.php (already shown above)
```

**Verify Redis Connection**:
```bash
# Test Redis connection
redis-cli ping
# Should return: PONG

# Check Drupal cache
drush php:eval "var_dump(\Drupal::cache()->get('test'));"

# Monitor Redis
redis-cli monitor
```

### Varnish (Optional, for high-traffic sites)

**Install Varnish**:
```bash
sudo apt install -y varnish

# Configure Varnish VCL
sudo nano /etc/varnish/default.vcl
```

**Varnish VCL for Drupal**:
```vcl
vcl 4.0;

backend default {
  .host = "127.0.0.1";
  .port = "8080";
  .connect_timeout = 600s;
  .first_byte_timeout = 600s;
  .between_bytes_timeout = 600s;
}

acl purge {
  "localhost";
  "127.0.0.1";
}

sub vcl_recv {
  # Allow purging
  if (req.method == "PURGE") {
    if (!client.ip ~ purge) {
      return (synth(405, "Not allowed."));
    }
    return (purge);
  }

  # Don't cache admin pages
  if (req.url ~ "^/admin" || req.url ~ "^/user") {
    return (pass);
  }

  # Don't cache logged-in users
  if (req.http.Cookie ~ "SESS") {
    return (pass);
  }

  # Don't cache POST requests
  if (req.method != "GET" && req.method != "HEAD") {
    return (pass);
  }

  return (hash);
}

sub vcl_backend_response {
  # Cache static files for 1 year
  if (bereq.url ~ "\.(jpg|jpeg|gif|png|ico|css|zip|tgz|gz|rar|bz2|pdf|txt|tar|wav|bmp|rtf|js|flv|swf|html|htm)$") {
    set beresp.ttl = 365d;
  }

  # Cache HTML for 5 minutes
  if (beresp.status == 200 && bereq.url !~ "^/admin" && bereq.url !~ "^/user") {
    set beresp.ttl = 5m;
  }
}

sub vcl_deliver {
  # Add cache hit header
  if (obj.hits > 0) {
    set resp.http.X-Cache = "HIT";
  } else {
    set resp.http.X-Cache = "MISS";
  }
}
```

**Start Varnish**:
```bash
sudo systemctl start varnish
sudo systemctl enable varnish

# Configure Nginx to proxy to Varnish (port 80 -> 6081)
# Update Nginx config to listen on port 8080
```

### Cache Warming

**Create cache warming script** (`/usr/local/bin/warm-cache.sh`):

```bash
#!/bin/bash

URLS=(
  "https://aabenforms.dk/"
  "https://aabenforms.dk/bruger/login"
  "https://aabenforms.dk/kontakt"
  "https://api.aabenforms.dk/jsonapi"
)

for url in "${URLS[@]}"; do
  echo "Warming cache: $url"
  curl -s -o /dev/null -w "%{http_code}\n" "$url"
done
```

**Schedule with cron**:
```bash
# Run every hour
0 * * * * /usr/local/bin/warm-cache.sh
```

---

## CDN Setup

### Cloudflare (Recommended)

**Why Cloudflare**:
- Free SSL certificates
- DDoS protection
- 300+ edge locations worldwide
- WAF (Web Application Firewall)
- GDPR compliant (EU data centers)

**Setup Steps**:

**1. Add Site to Cloudflare**:
- Sign up at https://dash.cloudflare.com
- Click "Add a Site"
- Enter `aabenforms.dk`
- Select Free plan (or Pro for $20/month)

**2. Update Nameservers**:
```
Change DNS nameservers at your registrar to:
  - NS1: bella.ns.cloudflare.com
  - NS2: ron.ns.cloudflare.com
```

**3. Configure DNS**:
```
A     aabenforms.dk          → <YOUR_SERVER_IP>  (Proxied: ON)
A     api.aabenforms.dk      → <YOUR_SERVER_IP>  (Proxied: ON)
CNAME www                    → aabenforms.dk     (Proxied: ON)
CNAME *                      → aabenforms.dk     (Proxied: ON)
```

**4. SSL/TLS Settings**:
- SSL/TLS encryption mode: **Full (strict)**
- Always Use HTTPS: **ON**
- Minimum TLS Version: **TLS 1.2**
- Automatic HTTPS Rewrites: **ON**
- Certificate Transparency Monitoring: **ON**

**5. Page Rules** (optimize for Drupal):

```
URL: api.aabenforms.dk/*
Settings:
  - Cache Level: Bypass

URL: aabenforms.dk/user/*
Settings:
  - Cache Level: Bypass
  - Security Level: High

URL: aabenforms.dk/admin/*
Settings:
  - Cache Level: Bypass
  - Security Level: High
  - Disable Performance

URL: *aabenforms.dk/*.css
Settings:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 month

URL: *aabenforms.dk/*.js
Settings:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 month

URL: *aabenforms.dk/sites/default/files/*
Settings:
  - Cache Level: Cache Everything
  - Edge Cache TTL: 1 month
```

**6. Speed Optimization**:
- Auto Minify: **HTML, CSS, JavaScript** (ON)
- Brotli: **ON**
- Early Hints: **ON**
- HTTP/3 (with QUIC): **ON**
- Rocket Loader: **OFF** (can break Drupal JS)

**7. Security**:
- Security Level: **Medium**
- Challenge Passage: **1 hour**
- Browser Integrity Check: **ON**
- Privacy Pass Support: **ON**
- Hotlink Protection: **ON**

**8. Firewall Rules**:

```yaml
# Block non-Danish traffic (optional)
Expression: (ip.geoip.country ne "DK" and ip.geoip.country ne "SE" and ip.geoip.country ne "NO")
Action: Challenge

# Block known bad bots
Expression: (cf.client.bot) or (http.user_agent contains "scrapy")
Action: Block

# Rate limiting (Pro plan)
Expression: (http.request.uri.path eq "/user/login")
Action: Rate Limit (10 requests per minute)
```

**9. Purge Cache**:

```bash
# Via Cloudflare Dashboard or API:
curl -X POST "https://api.cloudflare.com/client/v4/zones/{ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer {API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

# Or purge specific URLs:
curl -X POST "https://api.cloudflare.com/client/v4/zones/{ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer {API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"files":["https://aabenforms.dk/"]}'
```

### AWS CloudFront

**Create CloudFront Distribution**:

```bash
aws cloudfront create-distribution \
  --origin-domain-name aabenforms.dk \
  --default-root-object index.php \
  --viewer-protocol-policy redirect-to-https \
  --min-ttl 0 \
  --default-ttl 3600 \
  --max-ttl 86400 \
  --price-class PriceClass_100 \
  --compress
```

**Cache Behaviors**:
```json
{
  "PathPattern": "*/sites/default/files/*",
  "DefaultTTL": 2592000,
  "AllowedMethods": ["GET", "HEAD"],
  "Compress": true
}
```

---

## Monitoring Setup

### New Relic

**Install New Relic PHP Agent**:

```bash
# Add New Relic repository
curl -s https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
echo "deb http://apt.newrelic.com/debian/ newrelic non-free" | sudo tee /etc/apt/sources.list.d/newrelic.list

# Install agent
sudo apt update
sudo apt install -y newrelic-php5

# Configure (interactive)
sudo newrelic-install install

# Or non-interactive:
sudo NR_INSTALL_SILENT=true \
  NR_INSTALL_KEY=YOUR_LICENSE_KEY \
  newrelic-install install

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

**Configure New Relic**:

Edit `/etc/php/8.4/fpm/conf.d/newrelic.ini`:
```ini
newrelic.enabled = true
newrelic.license = "YOUR_LICENSE_KEY"
newrelic.appname = "ÅbenForms Production"
newrelic.daemon.address = "/var/run/newrelic/newrelic.sock"
newrelic.framework = "drupal"
newrelic.framework.drupal.modules = true
newrelic.transaction_tracer.detail = 1
newrelic.transaction_tracer.slow_sql = true
newrelic.transaction_tracer.stack_trace_threshold = 0.5
```

**New Relic Alerts**:

Set up alerts in New Relic dashboard:
- Response time > 2 seconds
- Error rate > 1%
- Apdex score < 0.8
- Memory usage > 80%
- Database queries > 500ms

### Datadog

**Install Datadog Agent**:

```bash
DD_API_KEY=YOUR_API_KEY DD_SITE="datadoghq.eu" bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_script_agent7.sh)"

# Configure Nginx integration
sudo cp /etc/datadog-agent/conf.d/nginx.d/conf.yaml.example /etc/datadog-agent/conf.d/nginx.d/conf.yaml

# Configure PHP-FPM integration
sudo cp /etc/datadog-agent/conf.d/php_fpm.d/conf.yaml.example /etc/datadog-agent/conf.d/php_fpm.d/conf.yaml

# Restart agent
sudo systemctl restart datadog-agent
```

**Datadog Drupal Integration**:

Install Drupal Datadog module:
```bash
composer require drupal/datadog
drush pm:enable datadog -y
drush config:set datadog.settings api_key YOUR_API_KEY -y
```

### Self-Hosted Monitoring (Prometheus + Grafana)

**Install Prometheus**:

```bash
# Download Prometheus
wget https://github.com/prometheus/prometheus/releases/download/v2.45.0/prometheus-2.45.0.linux-amd64.tar.gz
tar xvfz prometheus-2.45.0.linux-amd64.tar.gz
sudo mv prometheus-2.45.0.linux-amd64 /opt/prometheus

# Create systemd service
sudo tee /etc/systemd/system/prometheus.service <<EOF
[Unit]
Description=Prometheus
After=network.target

[Service]
User=prometheus
ExecStart=/opt/prometheus/prometheus --config.file=/opt/prometheus/prometheus.yml
Restart=always

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl start prometheus
sudo systemctl enable prometheus
```

**Install Grafana**:

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository "deb https://packages.grafana.com/oss/deb stable main"
wget -q -O - https://packages.grafana.com/gpg.key | sudo apt-key add -
sudo apt update
sudo apt install -y grafana

sudo systemctl start grafana-server
sudo systemctl enable grafana-server
```

**Drupal Metrics Exporter**:

Install Prometheus exporter for Drupal:
```bash
composer require drupal/prometheus
drush pm:enable prometheus -y

# Access metrics at:
# https://aabenforms.dk/metrics
```

---

## Backup Procedures

### Automated Backup Script

**Create `/usr/local/bin/aabenforms-backup.sh`**:

```bash
#!/bin/bash
set -e

# Configuration
PROJECT_ROOT="/var/www/aabenforms/backend"
BACKUP_DIR="/var/backups/aabenforms"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d-%H%M%S)
S3_BUCKET="s3://aabenforms-backups"

# Create backup directory
mkdir -p "$BACKUP_DIR"/{db,files,code}

# Database backup
echo "Backing up database..."
cd "$PROJECT_ROOT"
drush sql:dump --gzip --result-file="$BACKUP_DIR/db/aabenforms-db-$DATE.sql" || {
  echo "Database backup failed!"
  exit 1
}

# Files backup
echo "Backing up files..."
tar -czf "$BACKUP_DIR/files/aabenforms-files-$DATE.tar.gz" \
  "$PROJECT_ROOT/web/sites/default/files" \
  "$PROJECT_ROOT/private" || {
  echo "Files backup failed!"
  exit 1
}

# Code backup (optional)
echo "Backing up codebase..."
tar -czf "$BACKUP_DIR/code/aabenforms-code-$DATE.tar.gz" \
  --exclude="$PROJECT_ROOT/web/sites/default/files" \
  --exclude="$PROJECT_ROOT/vendor" \
  --exclude="$PROJECT_ROOT/node_modules" \
  "$PROJECT_ROOT" || {
  echo "Code backup failed!"
  exit 1
}

# Upload to S3 (if configured)
if command -v aws &> /dev/null; then
  echo "Uploading to S3..."
  aws s3 sync "$BACKUP_DIR" "$S3_BUCKET" --storage-class STANDARD_IA
fi

# Clean old backups
echo "Cleaning old backups..."
find "$BACKUP_DIR" -type f -mtime +$RETENTION_DAYS -delete

# Create backup manifest
cat > "$BACKUP_DIR/manifest-$DATE.txt" <<EOF
Backup Date: $(date)
Database: aabenforms-db-$DATE.sql.gz
Files: aabenforms-files-$DATE.tar.gz
Code: aabenforms-code-$DATE.tar.gz
Server: $(hostname)
Drupal Version: $(drush status --field=drupal-version)
EOF

echo "Backup completed successfully: $DATE"

# Send notification (optional)
if command -v mail &> /dev/null; then
  echo "Backup completed at $(date)" | mail -s "ÅbenForms Backup Success" admin@aabenforms.dk
fi
```

**Make executable and schedule**:

```bash
sudo chmod +x /usr/local/bin/aabenforms-backup.sh

# Add to crontab (daily at 2 AM)
echo "0 2 * * * /usr/local/bin/aabenforms-backup.sh >> /var/log/aabenforms-backup.log 2>&1" | sudo crontab -
```

### Platform.sh Backups

```bash
# Create manual backup
platform backup:create --yes

# List backups
platform backups

# Restore backup
platform backup:restore <backup-id>

# Download backup
platform backup:get <backup-id> --file=aabenforms-backup.tar.gz

# Automated backups are enabled by default (daily)
```

### Database-Only Quick Backup

```bash
# Quick database snapshot
drush sql:dump --gzip > /tmp/aabenforms-quick-$(date +%Y%m%d-%H%M%S).sql.gz

# Restore
gunzip -c /tmp/aabenforms-quick-20260202-140000.sql.gz | drush sql:cli
```

### Offsite Backup Destinations

**AWS S3**:
```bash
# Install AWS CLI
sudo apt install -y awscli

# Configure credentials
aws configure

# Upload backup
aws s3 cp /var/backups/aabenforms/ s3://aabenforms-backups/ --recursive

# Download backup
aws s3 cp s3://aabenforms-backups/aabenforms-db-20260202-140000.sql.gz ./
```

**Backblaze B2**:
```bash
# Install B2 CLI
pip3 install b2

# Authorize
b2 authorize-account <key-id> <application-key>

# Upload
b2 sync /var/backups/aabenforms/ b2://aabenforms-backups
```

---

## Rollback Procedures

### Code Rollback (Git)

**Identify last working commit**:
```bash
git log --oneline -20

# Example output:
# a1b2c3d Fix: Critical bug
# e4f5g6h Feature: New workflow
# i7j8k9l Last stable version <-- ROLLBACK TO THIS
```

**Rollback steps**:

```bash
# Method 1: Revert to previous commit
git revert a1b2c3d

# Method 2: Hard reset (USE WITH CAUTION)
git reset --hard i7j8k9l
git push --force origin main

# Method 3: Create rollback branch
git checkout -b rollback-to-stable i7j8k9l
git push origin rollback-to-stable

# Deploy rollback branch
platform environment:checkout rollback-to-stable
platform push
```

### Database Rollback

**Restore from backup**:

```bash
# List available backups
ls -lh /var/backups/aabenforms/db/

# Restore specific backup
gunzip -c /var/backups/aabenforms/db/aabenforms-db-20260201-020000.sql.gz | drush sql:cli

# Run updates
drush updatedb -y

# Clear cache
drush cache:rebuild

# Verify
drush status
```

### Full System Rollback

**Complete rollback procedure**:

```bash
#!/bin/bash
set -e

BACKUP_DATE="20260201-020000"
BACKUP_DIR="/var/backups/aabenforms"
PROJECT_ROOT="/var/www/aabenforms/backend"

echo "=== FULL SYSTEM ROLLBACK ==="
echo "Backup date: $BACKUP_DATE"
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
  echo "Rollback cancelled."
  exit 1
fi

# 1. Enable maintenance mode
cd "$PROJECT_ROOT"
drush state:set system.maintenance_mode TRUE -y
drush cache:rebuild

# 2. Backup current state (just in case)
drush sql:dump --gzip > /tmp/pre-rollback-$(date +%Y%m%d-%H%M%S).sql.gz

# 3. Restore database
echo "Restoring database..."
gunzip -c "$BACKUP_DIR/db/aabenforms-db-$BACKUP_DATE.sql.gz" | drush sql:cli

# 4. Restore files
echo "Restoring files..."
rm -rf "$PROJECT_ROOT/web/sites/default/files"
rm -rf "$PROJECT_ROOT/private"
tar -xzf "$BACKUP_DIR/files/aabenforms-files-$BACKUP_DATE.tar.gz" -C /

# 5. Restore code (optional)
echo "Restoring code..."
tar -xzf "$BACKUP_DIR/code/aabenforms-code-$BACKUP_DATE.tar.gz" -C /tmp/
rsync -av /tmp/var/www/aabenforms/backend/ "$PROJECT_ROOT/"

# 6. Run updates
drush updatedb -y
drush config:import -y
drush cache:rebuild

# 7. Disable maintenance mode
drush state:set system.maintenance_mode FALSE -y
drush cache:rebuild

echo "=== ROLLBACK COMPLETE ==="
echo "Verify site functionality before proceeding."
```

### Platform.sh Rollback

```bash
# List environments
platform environments

# Create rollback environment from backup
platform backup:restore --target=rollback-env

# Test rollback environment
platform url --environment=rollback-env

# If good, merge to production
platform environment:merge rollback-env --into=main
```

### Blue-Green Deployment Rollback

**If using blue-green deployment**:

```bash
# Switch traffic back to "blue" environment
# (Update load balancer / DNS)

# AWS ALB example:
aws elbv2 modify-target-group \
  --target-group-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-blue/abc \
  --weight 100

aws elbv2 modify-target-group \
  --target-group-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-green/def \
  --weight 0
```

---

## Troubleshooting

### Common Deployment Issues

**Problem**: White screen of death (WSOD)

**Solution**:
```bash
# Enable error reporting temporarily
drush php:eval "error_reporting(E_ALL); ini_set('display_errors', TRUE);"

# Check logs
drush watchdog:show --severity=Error
tail -f /var/log/nginx/error.log

# Clear cache
drush cache:rebuild

# Check file permissions
sudo chown -R www-data:www-data web/sites/default/files
```

**Problem**: "Site is in maintenance mode" after deployment

**Solution**:
```bash
drush state:set system.maintenance_mode FALSE -y
drush cache:rebuild
```

**Problem**: Configuration import fails

**Solution**:
```bash
# Check for UUID mismatch
drush config:get system.site uuid
drush config:get system.site uuid --include-overridden

# Fix UUID
drush config:set system.site uuid CORRECT_UUID -y

# Force import
drush config:import --partial -y
```

**Problem**: Database connection errors

**Solution**:
```bash
# Test database connection
mysql -u aabenforms -p -h 127.0.0.1 -e "SELECT 1;"

# Check settings.php database credentials
cat web/sites/default/settings.php | grep database

# Verify .env file
cat .env | grep DB_

# Test from Drupal
drush php:eval "var_dump(\Drupal::database()->query('SELECT 1')->fetchField());"
```

**Problem**: SSL certificate errors

**Solution**:
```bash
# Renew Let's Encrypt certificate
sudo certbot renew --force-renewal

# Test SSL configuration
openssl s_client -connect aabenforms.dk:443 -servername aabenforms.dk

# Check certificate expiration
echo | openssl s_client -connect aabenforms.dk:443 2>/dev/null | openssl x509 -noout -dates
```

**Problem**: Slow performance after deployment

**Solution**:
```bash
# Enable Twig cache
drush config:set system.performance twig.config.debug FALSE -y
drush config:set system.performance twig.config.auto_reload FALSE -y

# Enable CSS/JS aggregation
drush config:set system.performance css.preprocess TRUE -y
drush config:set system.performance js.preprocess TRUE -y

# Clear all caches
drush cache:rebuild

# Check database performance
drush sqlq "SHOW PROCESSLIST;"

# Optimize database tables
drush sqlq "OPTIMIZE TABLE cache_bootstrap, cache_config, cache_data;"
```

### Health Check Endpoint

**Create custom health check module** (`web/modules/custom/aabenforms_healthcheck`):

```php
<?php
// aabenforms_healthcheck.routing.yml
aabenforms_healthcheck.health:
  path: '/health'
  defaults:
    _controller: '\Drupal\aabenforms_healthcheck\Controller\HealthController::check'
    _title: 'Health Check'
  requirements:
    _access: 'TRUE'

// src/Controller/HealthController.php
<?php

namespace Drupal\aabenforms_healthcheck\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthController extends ControllerBase {

  public function check() {
    $health = [
      'status' => 'healthy',
      'timestamp' => time(),
      'checks' => [],
    ];

    // Database check
    try {
      \Drupal::database()->query('SELECT 1')->fetchField();
      $health['checks']['database'] = 'ok';
    } catch (\Exception $e) {
      $health['status'] = 'unhealthy';
      $health['checks']['database'] = 'error: ' . $e->getMessage();
    }

    // Redis check
    if (extension_loaded('redis')) {
      try {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->ping();
        $health['checks']['redis'] = 'ok';
      } catch (\Exception $e) {
        $health['checks']['redis'] = 'warning: ' . $e->getMessage();
      }
    }

    // File system check
    $private_path = \Drupal::service('file_system')->realpath('private://');
    $health['checks']['filesystem'] = is_writable($private_path) ? 'ok' : 'error: not writable';

    $status_code = $health['status'] === 'healthy' ? 200 : 503;
    return new JsonResponse($health, $status_code);
  }
}
```

**Test health check**:
```bash
curl https://aabenforms.dk/health
```

### Deployment Checklist

**Pre-Deployment**:
- [ ] All tests passing in CI/CD
- [ ] Code review completed
- [ ] Database backup created
- [ ] Files backup created
- [ ] Maintenance window scheduled
- [ ] Stakeholders notified
- [ ] Rollback plan prepared

**During Deployment**:
- [ ] Enable maintenance mode
- [ ] Deploy code
- [ ] Run database updates
- [ ] Import configuration
- [ ] Clear caches
- [ ] Verify health check
- [ ] Smoke test critical paths

**Post-Deployment**:
- [ ] Disable maintenance mode
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Verify integrations (MitID, Serviceplatformen)
- [ ] Send deployment notification
- [ ] Update deployment log

---

## Support and Resources

**Documentation**:
- [Drupal.org](https://www.drupal.org/docs)
- [Platform.sh Docs](https://docs.platform.sh)
- [KOMBIT Serviceplatformen](https://serviceplatformen.dk)

**Community**:
- Drupal Slack: #drupal-europe
- ÅbenForms GitHub: https://github.com/youraccount/aabenforms

**Emergency Contacts**:
- Technical Lead: tech@aabenforms.dk
- Platform.sh Support: support@platform.sh
- KOMBIT Support: support@kombit.dk

---

**Document Version**: 1.0
**Last Updated**: February 2026
**Maintained By**: ÅbenForms DevOps Team
