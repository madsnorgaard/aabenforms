# ÅbenForms Scaling Guide

**Version**: 1.0
**Last Updated**: February 2026
**Target Audience**: DevOps Engineers, Technical Architects

This guide provides strategies and procedures for scaling the ÅbenForms platform to handle increased traffic and data volumes as adoption grows across Danish municipalities.

---

## Table of Contents

1. [Scaling Overview](#scaling-overview)
2. [Traffic Estimation and Capacity Planning](#traffic-estimation-and-capacity-planning)
3. [Horizontal Scaling](#horizontal-scaling)
4. [Vertical Scaling](#vertical-scaling)
5. [Database Scaling](#database-scaling)
6. [Caching Strategies](#caching-strategies)
7. [Load Balancing Configuration](#load-balancing-configuration)
8. [Multi-Region Deployment](#multi-region-deployment)
9. [Scaling Checklist](#scaling-checklist)
10. [Performance Testing](#performance-testing)

---

## Scaling Overview

### When to Scale

**Indicators that scaling is needed**:

- **Performance degradation**:
  - Response time > 2 seconds (target: < 500ms)
  - Database queries > 200ms (target: < 50ms)
  - Error rate > 1% (target: < 0.1%)

- **Resource saturation**:
  - CPU usage > 80% sustained
  - Memory usage > 85% sustained
  - Disk I/O wait > 20%

- **Growth metrics**:
  - Traffic increased > 50% over baseline
  - Number of municipalities > 50
  - Daily submissions > 10,000

### Scaling Approaches

**Vertical Scaling (Scale Up)**:
- **What**: Increase resources (CPU, RAM, disk) on existing servers
- **Pros**: Simple, no architecture changes
- **Cons**: Limited by hardware, single point of failure, downtime required
- **Best for**: Quick fixes, small-to-medium growth

**Horizontal Scaling (Scale Out)**:
- **What**: Add more servers to distribute load
- **Pros**: Better redundancy, no theoretical limit, no downtime
- **Cons**: Complex architecture, requires load balancer, session management
- **Best for**: Large growth, high availability requirements

**Recommended Strategy**: **Hybrid**
- Start with vertical scaling for quick wins
- Transition to horizontal scaling for sustainable growth
- Use both approaches based on specific bottlenecks

---

## Traffic Estimation and Capacity Planning

### Current Baseline (Pilot Phase)

**Assumptions**:
- 3 pilot municipalities
- ~150,000 total citizens served
- 5% digital adoption rate (7,500 active users)
- Average 2 interactions/user/year

**Estimated Traffic**:
```
Daily active users: 7,500 / 365 = ~20 users/day
Peak concurrent users: 20 * 0.2 = 4 concurrent
Daily API requests: 20 * 10 = 200 requests/day
Peak requests/second: 200 / (8 hours * 3600) = 0.007 req/s
```

**Current Capacity**:
- **Server**: 2 CPU cores, 4 GB RAM
- **Database**: MariaDB 10.11, 20 GB storage
- **Handles**: ~100 concurrent users, ~50 req/s

### Growth Projections

**Year 1 (10 municipalities)**:
```
Total citizens: 500,000
Digital adoption: 10%
Active users: 50,000
Daily users: 137
Peak concurrent: 27
Daily requests: 1,370
Peak req/s: 0.05
```

**Year 2 (50 municipalities)**:
```
Total citizens: 2,500,000
Digital adoption: 25%
Active users: 625,000
Daily users: 1,712
Peak concurrent: 342
Daily requests: 17,120
Peak req/s: 0.6
```

**Year 3 (All 98 municipalities)**:
```
Total citizens: 5,800,000 (entire Denmark)
Digital adoption: 50%
Active users: 2,900,000
Daily users: 7,945
Peak concurrent: 1,589
Daily requests: 79,450
Peak req/s: 2.8
```

### Resource Planning

**Year 1 (10 municipalities)**:
- **Servers**: 1 web server (4 CPU, 8 GB RAM)
- **Database**: 1 DB server (4 CPU, 8 GB RAM, 50 GB storage)
- **Estimated cost**: $200-300/month

**Year 2 (50 municipalities)**:
- **Servers**: 2-3 web servers (8 CPU, 16 GB RAM each)
- **Database**: 1 DB server (8 CPU, 16 GB RAM, 200 GB storage) + read replica
- **Load balancer**: AWS ALB or Nginx
- **CDN**: Cloudflare or AWS CloudFront
- **Estimated cost**: $800-1,200/month

**Year 3 (All 98 municipalities)**:
- **Servers**: 5-10 web servers (16 CPU, 32 GB RAM each)
- **Database**: Primary DB (16 CPU, 32 GB RAM, 500 GB storage) + 2 read replicas
- **Load balancer**: Multi-region ALB
- **CDN**: Cloudflare Pro with advanced features
- **Caching**: Redis cluster (3 nodes)
- **Estimated cost**: $3,000-5,000/month

### Capacity Planning Formula

**Rule of Thumb**:
```
Required servers = (Peak concurrent users / 100) * Safety factor (1.5)

Example for Year 3:
Required servers = (1,589 / 100) * 1.5 = 24 servers
With optimization (caching, CDN): ~10 servers
```

---

## Horizontal Scaling

### Architecture for Horizontal Scaling

**Basic Setup** (2-3 web servers):

```
                        ┌─────────────┐
                        │ Load Balancer│
                        └──────┬───────┘
                               │
          ┌────────────────────┼────────────────────┐
          │                    │                    │
    ┌─────▼─────┐        ┌─────▼─────┐      ┌─────▼─────┐
    │ Web Server │        │ Web Server │      │ Web Server │
    │     #1     │        │     #2     │      │     #3     │
    └─────┬─────┘        └─────┬─────┘      └─────┬─────┘
          │                    │                    │
          └────────────────────┼────────────────────┘
                               │
                         ┌─────▼─────┐
                         │  Database  │
                         │  (Primary) │
                         └─────┬─────┘
                               │
                         ┌─────▼─────┐
                         │  Database  │
                         │  (Replica) │
                         └───────────┘

    ┌───────────┐        ┌───────────┐
    │   Redis   │        │    S3     │
    │  (Cache)  │        │  (Files)  │
    └───────────┘        └───────────┘
```

### Prerequisites for Horizontal Scaling

**1. Stateless Web Servers**:

All web servers must be identical and stateless. No local state stored on servers.

**Drupal Configuration** (`settings.php`):

```php
<?php

// Use centralized file storage (S3 or NFS)
$settings['file_public_path'] = 's3://aabenforms-files/public';
$settings['file_private_path'] = 's3://aabenforms-files/private';

// Use centralized session storage (database or Redis)
$settings['session_storage'] = 'redis';

// Disable file-based cache (use Redis instead)
$settings['cache']['default'] = 'cache.backend.redis';
```

**2. Centralized File Storage**:

**Option A: S3-compatible storage** (AWS S3, Backblaze B2, DigitalOcean Spaces):

```bash
# Install S3FS module
composer require drupal/s3fs

# Configure S3FS
drush config:set s3fs.settings bucket 'aabenforms-files' -y
drush config:set s3fs.settings region 'eu-central-1' -y
drush config:set s3fs.settings use_https 1 -y

# Enable S3FS
drush pm:enable s3fs -y

# Sync existing files to S3
drush s3fs-refresh-cache
```

**Option B: NFS shared storage**:

```bash
# On NFS server
apt install -y nfs-kernel-server
mkdir -p /export/aabenforms-files
chown www-data:www-data /export/aabenforms-files

# Export NFS share
echo "/export/aabenforms-files 10.0.0.0/24(rw,sync,no_subtree_check,no_root_squash)" >> /etc/exports
exportfs -a
systemctl restart nfs-kernel-server

# On each web server
apt install -y nfs-common
mkdir -p /var/www/aabenforms/backend/web/sites/default/files
mount -t nfs nfs-server:/export/aabenforms-files /var/www/aabenforms/backend/web/sites/default/files

# Add to /etc/fstab for persistence
echo "nfs-server:/export/aabenforms-files /var/www/aabenforms/backend/web/sites/default/files nfs defaults 0 0" >> /etc/fstab
```

**3. Centralized Session Storage**:

**Redis for sessions** (`settings.php`):

```php
<?php

// Use Redis for sessions
if (extension_loaded('redis')) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = '10.0.0.100'; // Redis server IP
  $settings['redis.connection']['port'] = 6379;

  // Use Redis for session storage
  $settings['session_storage'] = [
    'class' => 'Drupal\Core\Session\SessionManager',
    'storage' => 'Drupal\redis\Session\RedisSessionHandler',
  ];
}
```

### Adding Web Servers

**Step-by-Step Process**:

**1. Provision new server**:

```bash
# Launch EC2 instance or equivalent
aws ec2 run-instances \
  --image-id ami-xxxxxxxxx \
  --instance-type t3.medium \
  --key-name aabenforms-key \
  --security-group-ids sg-xxxxxxxx \
  --subnet-id subnet-xxxxxxxx \
  --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=aabenforms-web-04}]'
```

**2. Configure server** (same as primary web server):

```bash
# SSH to new server
ssh ubuntu@<new-server-ip>

# Install dependencies
sudo apt update
sudo apt install -y nginx php8.4-fpm php8.4-mysql php8.4-redis git composer

# Clone repository
cd /var/www
git clone https://github.com/youraccount/aabenforms.git
cd aabenforms/backend
composer install --no-dev --optimize-autoloader

# Configure Nginx (same config as other servers)
sudo cp /etc/nginx/sites-available/aabenforms.conf /etc/nginx/sites-available/aabenforms
sudo ln -s /etc/nginx/sites-available/aabenforms /etc/nginx/sites-enabled/
sudo systemctl reload nginx

# Mount NFS share (or configure S3FS)
sudo mount -t nfs nfs-server:/export/aabenforms-files /var/www/aabenforms/backend/web/sites/default/files
```

**3. Add to load balancer**:

```bash
# AWS ALB
aws elbv2 register-targets \
  --target-group-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc123 \
  --targets Id=i-xxxxxxxxx

# Verify health check
aws elbv2 describe-target-health \
  --target-group-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc123
```

**4. Test new server**:

```bash
# Direct test to new server IP
curl -I http://<new-server-ip>/user/login

# Test through load balancer
for i in {1..20}; do curl -I https://aabenforms.dk | grep X-Served-By; done
# Should show distribution across all servers
```

### Auto-Scaling Configuration

**AWS Auto Scaling Group**:

```bash
# Create launch template
aws ec2 create-launch-template \
  --launch-template-name aabenforms-web-server \
  --version-description "v1" \
  --launch-template-data '{
    "ImageId": "ami-xxxxxxxxx",
    "InstanceType": "t3.medium",
    "KeyName": "aabenforms-key",
    "SecurityGroupIds": ["sg-xxxxxxxx"],
    "UserData": "<base64-encoded-bootstrap-script>",
    "TagSpecifications": [{
      "ResourceType": "instance",
      "Tags": [{"Key": "Name", "Value": "aabenforms-web-auto"}]
    }]
  }'

# Create Auto Scaling Group
aws autoscaling create-auto-scaling-group \
  --auto-scaling-group-name aabenforms-asg \
  --launch-template LaunchTemplateName=aabenforms-web-server \
  --min-size 2 \
  --max-size 10 \
  --desired-capacity 3 \
  --target-group-arns arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc123 \
  --health-check-type ELB \
  --health-check-grace-period 300 \
  --vpc-zone-identifier "subnet-xxxxxxxx,subnet-yyyyyyyy"

# Create scaling policy (target tracking)
aws autoscaling put-scaling-policy \
  --auto-scaling-group-name aabenforms-asg \
  --policy-name cpu-target-tracking \
  --policy-type TargetTrackingScaling \
  --target-tracking-configuration '{
    "PredefinedMetricSpecification": {
      "PredefinedMetricType": "ASGAverageCPUUtilization"
    },
    "TargetValue": 70.0
  }'
```

**Bootstrap script** (UserData):

```bash
#!/bin/bash
set -e

# Update system
apt update && apt upgrade -y

# Install dependencies
apt install -y nginx php8.4-fpm php8.4-mysql php8.4-redis git composer nfs-common

# Clone repository
cd /var/www
git clone https://github.com/youraccount/aabenforms.git
cd aabenforms/backend
composer install --no-dev --optimize-autoloader

# Configure Nginx
cat > /etc/nginx/sites-available/aabenforms <<'EOF'
server {
    listen 80;
    server_name aabenforms.dk;
    root /var/www/aabenforms/backend/web;
    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
EOF

ln -s /etc/nginx/sites-available/aabenforms /etc/nginx/sites-enabled/
systemctl reload nginx

# Mount NFS
mkdir -p /var/www/aabenforms/backend/web/sites/default/files
mount -t nfs nfs-server:/export/aabenforms-files /var/www/aabenforms/backend/web/sites/default/files
echo "nfs-server:/export/aabenforms-files /var/www/aabenforms/backend/web/sites/default/files nfs defaults 0 0" >> /etc/fstab

# Set permissions
chown -R www-data:www-data /var/www/aabenforms

# Signal success to Auto Scaling
aws autoscaling complete-lifecycle-action \
  --lifecycle-action-result CONTINUE \
  --lifecycle-hook-name aabenforms-launch-hook \
  --auto-scaling-group-name aabenforms-asg \
  --lifecycle-action-token $LIFECYCLE_TOKEN
```

---

## Vertical Scaling

### Scaling Individual Components

**When to use vertical scaling**:
- Quick fix for immediate capacity needs
- Specific bottleneck identified (CPU, memory, disk)
- Fewer than 5 servers (simpler than horizontal scaling)

### Scaling Web Servers

**CPU-bound** (high PHP processing):

```bash
# Increase PHP-FPM workers
sudo nano /etc/php/8.4/fpm/pool.d/www.conf

# Adjust:
pm.max_children = 100 (from 50)
pm.start_servers = 20 (from 10)
pm.min_spare_servers = 10 (from 5)
pm.max_spare_servers = 40 (from 20)

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# OR upgrade server instance
# AWS: t3.medium → t3.large (2 CPU → 4 CPU)
```

**Memory-bound** (high memory usage):

```bash
# Increase PHP memory limit
sudo nano /etc/php/8.4/fpm/php.ini

# Adjust:
memory_limit = 512M (from 256M)

# Increase OpCache memory
opcache.memory_consumption = 256 (from 128)

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# OR upgrade server instance
# AWS: t3.medium → t3.large (4 GB → 8 GB)
```

### Scaling Database Server

**Storage scaling** (disk space running low):

```bash
# AWS RDS: Modify storage
aws rds modify-db-instance \
  --db-instance-identifier aabenforms-prod \
  --allocated-storage 200 \
  --apply-immediately

# Self-hosted: Add disk and extend volume
# (Requires downtime or live migration)
```

**CPU/Memory scaling** (slow queries, high load):

```bash
# AWS RDS: Modify instance class
aws rds modify-db-instance \
  --db-instance-identifier aabenforms-prod \
  --db-instance-class db.t3.large \
  --apply-immediately

# Self-hosted: Increase server resources
# (Usually requires downtime)
```

**MariaDB configuration tuning**:

```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf

# Adjust based on available RAM:
# For 8 GB RAM:
innodb_buffer_pool_size = 5G     # 60-70% of RAM
query_cache_size = 512M
tmp_table_size = 512M
max_heap_table_size = 512M
max_connections = 200

# Restart MariaDB
sudo systemctl restart mariadb
```

### Scaling Redis Cache

**Memory scaling**:

```bash
# Increase Redis memory limit
sudo nano /etc/redis/redis.conf

# Adjust:
maxmemory 4gb (from 2gb)
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis-server

# OR upgrade to Redis cluster
# (See Caching Strategies section)
```

---

## Database Scaling

### Read Replicas

**When to use**:
- Read-heavy workload (typical for ÅbenForms: 80% reads, 20% writes)
- Reporting and analytics queries slowing down production
- Geographic distribution for faster regional access

**Setup MySQL/MariaDB Read Replica** (AWS RDS):

```bash
# Create read replica
aws rds create-db-instance-read-replica \
  --db-instance-identifier aabenforms-replica-1 \
  --source-db-instance-identifier aabenforms-prod \
  --db-instance-class db.t3.medium \
  --availability-zone eu-central-1b \
  --publicly-accessible

# Wait for replica to be available
aws rds describe-db-instances \
  --db-instance-identifier aabenforms-replica-1 \
  --query 'DBInstances[0].DBInstanceStatus'
```

**Configure Drupal to use read replica** (`settings.php`):

```php
<?php

$databases['default']['default'] = [
  'database' => 'aabenforms',
  'username' => 'admin',
  'password' => 'SECURE_PASSWORD',
  'host' => 'aabenforms-prod.xxxxxxxx.eu-central-1.rds.amazonaws.com',
  'port' => 3306,
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

// Add read replica for read-only queries
$databases['default']['replica'] = [
  'database' => 'aabenforms',
  'username' => 'admin',
  'password' => 'SECURE_PASSWORD',
  'host' => 'aabenforms-replica-1.xxxxxxxx.eu-central-1.rds.amazonaws.com',
  'port' => 3306,
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
  'replica' => TRUE, // Mark as replica
];

// Drupal will automatically use replica for SELECT queries
```

### Database Sharding (Advanced)

**When to use**:
- Database size > 500 GB
- Queries still slow despite read replicas
- Specific datasets can be isolated (e.g., per municipality)

**Sharding Strategy for ÅbenForms**:

**Option 1: Shard by Municipality**:

```
Database 1: Municipalities 1-30
Database 2: Municipalities 31-60
Database 3: Municipalities 61-98
```

**Drupal Multi-Database Configuration** (`settings.php`):

```php
<?php

// Get municipality from domain
$municipality_id = \Drupal::service('aabenforms_tenant.tenant_detector')->getMunicipalityId();

// Shard assignment logic
$shard = ceil($municipality_id / 30); // 30 municipalities per shard

$databases['default']['default'] = [
  'database' => 'aabenforms',
  'username' => 'admin',
  'password' => 'SECURE_PASSWORD',
  'host' => "aabenforms-shard-{$shard}.rds.amazonaws.com",
  'port' => 3306,
  'driver' => 'mysql',
];
```

**Option 2: Shard by Data Type**:

```
Database 1 (Primary): User accounts, configuration
Database 2 (Submissions): Webform submissions
Database 3 (Audit): Audit logs, analytics
```

**Note**: Sharding is complex. Only implement if absolutely necessary (> 100 municipalities).

### Database Connection Pooling

**PgBouncer (for PostgreSQL) or ProxySQL (for MySQL)**:

```bash
# Install ProxySQL
wget https://github.com/sysown/proxysql/releases/download/v2.5.0/proxysql_2.5.0-ubuntu22_amd64.deb
sudo dpkg -i proxysql_2.5.0-ubuntu22_amd64.deb

# Configure ProxySQL
sudo nano /etc/proxysql.cnf
```

**ProxySQL Configuration**:

```conf
datadir="/var/lib/proxysql"

admin_variables=
{
    admin_credentials="admin:admin"
    mysql_ifaces="0.0.0.0:6032"
}

mysql_variables=
{
    threads=4
    max_connections=2048
    default_query_delay=0
    default_query_timeout=36000000
    have_compress=true
    poll_timeout=2000
    interfaces="0.0.0.0:3306"
    default_schema="information_schema"
    stacksize=1048576
    server_version="5.7.20"
    connect_timeout_server=3000
    monitor_history=600000
    monitor_connect_interval=60000
    monitor_ping_interval=10000
    monitor_read_only_interval=1500
    monitor_read_only_timeout=500
    ping_interval_server_msec=120000
    ping_timeout_server=500
    commands_stats=true
    sessions_sort=true
    connect_retries_on_failure=10
}

mysql_servers =
(
    {
        address="aabenforms-prod.rds.amazonaws.com"
        port=3306
        hostgroup=0
        max_connections=200
    },
    {
        address="aabenforms-replica-1.rds.amazonaws.com"
        port=3306
        hostgroup=1
        max_connections=200
    }
)

mysql_users =
(
    {
        username = "aabenforms"
        password = "SECURE_PASSWORD"
        default_hostgroup = 0
        max_connections=200
        active = 1
    }
)

mysql_query_rules =
(
    {
        rule_id=1
        active=1
        match_pattern="^SELECT .* FOR UPDATE$"
        destination_hostgroup=0
        apply=1
    },
    {
        rule_id=2
        active=1
        match_pattern="^SELECT"
        destination_hostgroup=1
        apply=1
    }
)
```

**Update Drupal settings to use ProxySQL**:

```php
<?php

// Connect to ProxySQL instead of direct database
$databases['default']['default'] = [
  'database' => 'aabenforms',
  'username' => 'aabenforms',
  'password' => 'SECURE_PASSWORD',
  'host' => '127.0.0.1', // ProxySQL running locally
  'port' => 3306,
  'driver' => 'mysql',
];
```

---

## Caching Strategies

### Multi-Layer Caching Architecture

```
Request
  ↓
1. CDN (Cloudflare) - Static assets, cached pages
  ↓ (if miss)
2. Varnish (optional) - Full page cache
  ↓ (if miss)
3. Drupal Internal Cache - Render cache, dynamic page cache
  ↓ (if miss)
4. Redis - Cache bins (bootstrap, config, data)
  ↓ (if miss)
5. Database - Original data
```

### Level 1: CDN Caching (Cloudflare)

**Already configured** (see Deployment Guide).

**Optimization**:

```javascript
// Cloudflare Workers for advanced caching
addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const cacheUrl = new URL(request.url)
  const cacheKey = new Request(cacheUrl.toString(), request)
  const cache = caches.default

  // Try cache first
  let response = await cache.match(cacheKey)

  if (!response) {
    // Fetch from origin
    response = await fetch(request)

    // Cache for 1 hour if successful
    if (response.status === 200) {
      response = new Response(response.body, response)
      response.headers.set('Cache-Control', 'public, max-age=3600')
      await cache.put(cacheKey, response.clone())
    }
  }

  return response
}
```

### Level 2: Varnish (Optional)

**Install Varnish**:

```bash
sudo apt install -y varnish

# Configure Varnish VCL
sudo nano /etc/varnish/default.vcl
```

**Varnish VCL for Drupal** (see Deployment Guide for full config):

```vcl
vcl 4.0;

backend default {
  .host = "127.0.0.1";
  .port = "8080";
}

sub vcl_recv {
  # Don't cache admin pages
  if (req.url ~ "^/admin" || req.url ~ "^/user" || req.url ~ "^/jsonapi") {
    return (pass);
  }

  # Don't cache logged-in users
  if (req.http.Cookie ~ "SESS") {
    return (pass);
  }

  return (hash);
}

sub vcl_backend_response {
  # Cache for 5 minutes
  if (beresp.status == 200) {
    set beresp.ttl = 5m;
  }
}
```

### Level 3: Redis Cluster

**When to use**:
- Single Redis instance saturated (> 80% CPU or memory)
- Need high availability for cache
- Supporting > 50 municipalities

**Redis Cluster Setup** (3 master nodes, 3 replica nodes):

```bash
# On each of 6 servers, install Redis
sudo apt install -y redis-server

# Configure Redis for cluster mode
sudo nano /etc/redis/redis.conf

# Adjust:
bind 0.0.0.0
protected-mode no
port 7000
cluster-enabled yes
cluster-config-file nodes.conf
cluster-node-timeout 5000
appendonly yes

# Start Redis on each server
sudo systemctl start redis-server

# Create cluster (run on one server)
redis-cli --cluster create \
  10.0.0.1:7000 10.0.0.2:7000 10.0.0.3:7000 \
  10.0.0.4:7000 10.0.0.5:7000 10.0.0.6:7000 \
  --cluster-replicas 1
```

**Configure Drupal to use Redis Cluster** (`settings.php`):

```php
<?php

if (extension_loaded('redis')) {
  $settings['redis.connection']['interface'] = 'PhpRedis';

  // Redis Cluster nodes
  $settings['redis.connection']['host'] = [
    '10.0.0.1:7000',
    '10.0.0.2:7000',
    '10.0.0.3:7000',
    '10.0.0.4:7000',
    '10.0.0.5:7000',
    '10.0.0.6:7000',
  ];

  $settings['cache']['default'] = 'cache.backend.redis';
}
```

### Cache Warming

**Automated cache warming after deployment**:

```bash
#!/bin/bash
# /usr/local/bin/warm-cache.sh

URLS=(
  "https://aabenforms.dk/"
  "https://aabenforms.dk/bruger/login"
  "https://api.aabenforms.dk/jsonapi"
  "https://api.aabenforms.dk/jsonapi/webform/webform"
)

for url in "${URLS[@]}"; do
  echo "Warming cache: $url"
  curl -s -o /dev/null -w "%{http_code}\n" "$url"
done

# Warm municipality-specific pages
for municipality in ballerup odense aarhus; do
  echo "Warming cache: https://$municipality.aabenforms.dk/"
  curl -s -o /dev/null "https://$municipality.aabenforms.dk/"
done
```

**Schedule cache warming**:

```bash
# After deployments
*/30 * * * * /usr/local/bin/warm-cache.sh

# After cache clear
@reboot sleep 60 && /usr/local/bin/warm-cache.sh
```

---

## Load Balancing Configuration

### AWS Application Load Balancer (ALB)

**Create ALB**:

```bash
# Create load balancer
aws elbv2 create-load-balancer \
  --name aabenforms-alb \
  --subnets subnet-xxxxxxxx subnet-yyyyyyyy \
  --security-groups sg-xxxxxxxx \
  --scheme internet-facing \
  --tags Key=Name,Value=aabenforms-alb

# Create target group
aws elbv2 create-target-group \
  --name aabenforms-tg \
  --protocol HTTP \
  --port 80 \
  --vpc-id vpc-xxxxxxxx \
  --health-check-protocol HTTP \
  --health-check-path /health \
  --health-check-interval-seconds 30 \
  --health-check-timeout-seconds 5 \
  --healthy-threshold-count 2 \
  --unhealthy-threshold-count 3

# Register targets
aws elbv2 register-targets \
  --target-group-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc \
  --targets Id=i-xxxxxxxx Id=i-yyyyyyyy Id=i-zzzzzzzz

# Create listener (HTTP)
aws elbv2 create-listener \
  --load-balancer-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:loadbalancer/app/aabenforms-alb/abc \
  --protocol HTTP \
  --port 80 \
  --default-actions Type=forward,TargetGroupArn=arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc

# Create listener (HTTPS)
aws elbv2 create-listener \
  --load-balancer-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:loadbalancer/app/aabenforms-alb/abc \
  --protocol HTTPS \
  --port 443 \
  --certificates CertificateArn=arn:aws:acm:eu-central-1:123456789:certificate/abc \
  --default-actions Type=forward,TargetGroupArn=arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc
```

### Nginx Load Balancer (Self-Hosted)

**Install Nginx on dedicated load balancer server**:

```bash
sudo apt install -y nginx

sudo nano /etc/nginx/sites-available/aabenforms-lb
```

**Nginx Load Balancer Configuration**:

```nginx
upstream aabenforms_backend {
    # Load balancing method: least_conn (fewest active connections)
    least_conn;

    # Backend servers
    server 10.0.0.10:80 max_fails=3 fail_timeout=30s;
    server 10.0.0.11:80 max_fails=3 fail_timeout=30s;
    server 10.0.0.12:80 max_fails=3 fail_timeout=30s;

    # Health check (requires nginx-plus or custom module)
    # health_check interval=10s fails=3 passes=2;

    # Sticky sessions (optional, if needed)
    # ip_hash;
}

server {
    listen 80;
    listen [::]:80;
    server_name aabenforms.dk *.aabenforms.dk;

    # Redirect HTTP to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name aabenforms.dk *.aabenforms.dk;

    ssl_certificate /etc/letsencrypt/live/aabenforms.dk/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aabenforms.dk/privkey.pem;

    # SSL optimization
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Logging
    access_log /var/log/nginx/aabenforms-lb-access.log;
    error_log /var/log/nginx/aabenforms-lb-error.log;

    # Proxy to backend
    location / {
        proxy_pass http://aabenforms_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;

        # Buffering
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        proxy_busy_buffers_size 8k;

        # Error handling
        proxy_next_upstream error timeout invalid_header http_500 http_502 http_503;
        proxy_next_upstream_tries 2;
    }

    # Health check endpoint (bypass load balancer)
    location /health {
        access_log off;
        return 200 "OK\n";
        add_header Content-Type text/plain;
    }
}
```

**Enable and test**:

```bash
sudo ln -s /etc/nginx/sites-available/aabenforms-lb /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Test load balancing
for i in {1..10}; do curl -I https://aabenforms.dk | grep X-Served-By; done
```

### Session Persistence (Sticky Sessions)

**If required** (usually not needed with Redis session storage):

**AWS ALB**:

```bash
# Enable stickiness on target group
aws elbv2 modify-target-group-attributes \
  --target-group-arn arn:aws:elasticloadbalancing:eu-central-1:123456789:targetgroup/aabenforms-tg/abc \
  --attributes Key=stickiness.enabled,Value=true Key=stickiness.type,Value=lb_cookie Key=stickiness.lb_cookie.duration_seconds,Value=3600
```

**Nginx**:

```nginx
upstream aabenforms_backend {
    # Use ip_hash for sticky sessions
    ip_hash;

    server 10.0.0.10:80;
    server 10.0.0.11:80;
    server 10.0.0.12:80;
}
```

---

## Multi-Region Deployment

### When to Use Multi-Region

- **High availability**: Disaster recovery, zero downtime
- **Geographic distribution**: Serve users from closest region
- **Regulatory compliance**: Data residency requirements (GDPR)

### Multi-Region Architecture

**2-Region Setup** (Primary: Frankfurt, Secondary: Paris):

```
                      ┌────────────────┐
                      │  Global DNS    │
                      │  (Route 53)    │
                      └────────┬───────┘
                               │
                 ┌─────────────┴─────────────┐
                 │                           │
          ┌──────▼─────┐              ┌──────▼─────┐
          │  Frankfurt  │              │   Paris    │
          │  (Primary)  │◄──────────┐  │ (Secondary)│
          └──────┬─────┘           │  └──────┬─────┘
                 │                 │         │
         ┌───────┴───────┐        │ ┌───────┴───────┐
         │               │        │ │               │
    ┌────▼────┐     ┌────▼────┐  │ ┌────▼────┐┌────▼────┐
    │ Web (×3)│     │Database │──┘ │ Web (×2)││Database │
    └─────────┘     │(Primary)│    └─────────┘│(Replica)│
                    └─────────┘               └─────────┘
```

### DNS-Based Routing (AWS Route 53)

**Geolocation Routing**:

```bash
# Create hosted zone
aws route53 create-hosted-zone \
  --name aabenforms.dk \
  --caller-reference $(date +%s)

# Create record for EU (Frankfurt)
aws route53 change-resource-record-sets \
  --hosted-zone-id Z1234567890ABC \
  --change-batch '{
    "Changes": [{
      "Action": "CREATE",
      "ResourceRecordSet": {
        "Name": "aabenforms.dk",
        "Type": "A",
        "SetIdentifier": "EU-Frankfurt",
        "GeoLocation": {
          "ContinentCode": "EU"
        },
        "AliasTarget": {
          "HostedZoneId": "Z215JYRZR1TBD5",
          "DNSName": "aabenforms-alb-frankfurt.eu-central-1.elb.amazonaws.com",
          "EvaluateTargetHealth": true
        }
      }
    }]
  }'

# Create record for default (Paris)
aws route53 change-resource-record-sets \
  --hosted-zone-id Z1234567890ABC \
  --change-batch '{
    "Changes": [{
      "Action": "CREATE",
      "ResourceRecordSet": {
        "Name": "aabenforms.dk",
        "Type": "A",
        "SetIdentifier": "Default",
        "GeoLocation": {
          "ContinentCode": "*"
        },
        "AliasTarget": {
          "HostedZoneId": "Z215JYRZR1TBD5",
          "DNSName": "aabenforms-alb-paris.eu-west-3.elb.amazonaws.com",
          "EvaluateTargetHealth": true
        }
      }
    }]
  }'
```

### Database Replication Across Regions

**AWS RDS Cross-Region Read Replica**:

```bash
# Create read replica in Paris region
aws rds create-db-instance-read-replica \
  --db-instance-identifier aabenforms-paris-replica \
  --source-db-instance-identifier arn:aws:rds:eu-central-1:123456789:db:aabenforms-prod \
  --db-instance-class db.t3.medium \
  --availability-zone eu-west-3a \
  --region eu-west-3

# Promote replica to standalone in case of DR
# (Only in emergency)
aws rds promote-read-replica \
  --db-instance-identifier aabenforms-paris-replica \
  --region eu-west-3
```

### File Storage Replication

**S3 Cross-Region Replication**:

```bash
# Create replication rule
aws s3api put-bucket-replication \
  --bucket aabenforms-files-frankfurt \
  --replication-configuration '{
    "Role": "arn:aws:iam::123456789:role/s3-replication-role",
    "Rules": [{
      "Status": "Enabled",
      "Priority": 1,
      "Filter": {},
      "Destination": {
        "Bucket": "arn:aws:s3:::aabenforms-files-paris",
        "ReplicationTime": {
          "Status": "Enabled",
          "Time": {
            "Minutes": 15
          }
        },
        "Metrics": {
          "Status": "Enabled",
          "EventThreshold": {
            "Minutes": 15
          }
        }
      }
    }]
  }'
```

---

## Scaling Checklist

**Before Scaling**:

- [ ] Identify bottleneck (CPU, memory, disk, network, database)
- [ ] Review monitoring dashboards (past 30 days)
- [ ] Estimate required capacity
- [ ] Calculate costs of scaling options
- [ ] Choose scaling approach (vertical vs. horizontal)
- [ ] Create scaling plan and timeline
- [ ] Get stakeholder approval

**During Scaling**:

- [ ] Schedule maintenance window (if needed)
- [ ] Notify users of potential impact
- [ ] Create full backup
- [ ] Test scaling in staging environment
- [ ] Document scaling steps
- [ ] Implement scaling incrementally
- [ ] Monitor performance during scaling
- [ ] Run smoke tests after each change

**After Scaling**:

- [ ] Verify performance improvement
- [ ] Monitor for 24-48 hours
- [ ] Update documentation (architecture diagrams, runbooks)
- [ ] Update monitoring alerts and thresholds
- [ ] Review costs vs. performance gains
- [ ] Plan next scaling milestone

---

## Performance Testing

### Load Testing Tools

**Recommended**: **Apache JMeter** or **k6**

**Apache JMeter Setup**:

```bash
# Download JMeter
wget https://dlcdn.apache.org//jmeter/binaries/apache-jmeter-5.6.3.tgz
tar -xzf apache-jmeter-5.6.3.tgz
cd apache-jmeter-5.6.3

# Run GUI
./bin/jmeter

# Or headless (CLI)
./bin/jmeter -n -t aabenforms-load-test.jmx -l results.jtl -e -o report/
```

**k6 Load Test Script** (`load-test.js`):

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 50 },  // Ramp up to 50 users
    { duration: '5m', target: 50 },  // Stay at 50 users
    { duration: '2m', target: 100 }, // Ramp up to 100 users
    { duration: '5m', target: 100 }, // Stay at 100 users
    { duration: '2m', target: 0 },   // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests < 2s
    http_req_failed: ['rate<0.01'],    // Error rate < 1%
  },
};

export default function () {
  // Homepage
  let res = http.get('https://aabenforms.dk/');
  check(res, { 'homepage status 200': (r) => r.status === 200 });

  sleep(1);

  // Login page
  res = http.get('https://aabenforms.dk/bruger/login');
  check(res, { 'login page status 200': (r) => r.status === 200 });

  sleep(2);

  // API endpoint
  res = http.get('https://api.aabenforms.dk/jsonapi');
  check(res, { 'API status 200': (r) => r.status === 200 });

  sleep(3);
}
```

**Run k6 test**:

```bash
k6 run load-test.js
```

### Test Scenarios

**Baseline Test** (Normal load):
- Concurrent users: 50
- Duration: 15 minutes
- Goal: Establish baseline performance

**Load Test** (Expected peak):
- Concurrent users: 200
- Duration: 30 minutes
- Goal: Verify system handles expected peak

**Stress Test** (Beyond capacity):
- Concurrent users: Start at 100, increase by 50 every 5 minutes until failure
- Duration: Until system breaks
- Goal: Identify breaking point

**Soak Test** (Sustained load):
- Concurrent users: 100
- Duration: 4 hours
- Goal: Detect memory leaks, degradation over time

### Analyzing Results

**Key Metrics**:

- **Response Time**:
  - Average: < 500ms
  - 95th percentile: < 2s
  - 99th percentile: < 5s

- **Throughput**:
  - Requests per second: > 50 req/s

- **Error Rate**:
  - < 1% errors

**k6 Output Example**:

```
          /\      |‾‾| /‾‾/   /‾‾/
     /\  /  \     |  |/  /   /  /
    /  \/    \    |     (   /   ‾‾\
   /          \   |  |\  \ |  (‾)  |
  / __________ \  |__| \__\ \_____/ .io

  execution: local
     script: load-test.js
     output: -

  scenarios: (100.00%) 1 scenario, 100 max VUs, 16m30s max duration (incl. graceful stop):
           * default: Up to 100 looping VUs for 16m0s over 5 stages (gracefulRampDown: 30s, gracefulStop: 30s)

     ✓ homepage status 200
     ✓ login page status 200
     ✓ API status 200

     checks.........................: 100.00% ✓ 9000      ✗ 0
     data_received..................: 180 MB  190 kB/s
     data_sent......................: 1.2 MB  1.3 kB/s
     http_req_blocked...............: avg=1.2ms    min=0s     med=0s      max=120ms   p(90)=0s      p(95)=0s
     http_req_connecting............: avg=0.8ms    min=0s     med=0s      max=80ms    p(90)=0s      p(95)=0s
   ✓ http_req_duration..............: avg=428ms    min=120ms  med=380ms   max=1.8s    p(90)=720ms   p(95)=980ms
       { expected_response:true }...: avg=428ms    min=120ms  med=380ms   max=1.8s    p(90)=720ms   p(95)=980ms
   ✓ http_req_failed................: 0.00%   ✓ 0         ✗ 9000
     http_req_receiving.............: avg=2.1ms    min=0s     med=1.2ms   max=50ms    p(90)=4.5ms   p(95)=6.2ms
     http_req_sending...............: avg=0.1ms    min=0s     med=0s      max=5ms     p(90)=0.2ms   p(95)=0.4ms
     http_req_tls_handshaking.......: avg=0.4ms    min=0s     med=0s      max=40ms    p(90)=0s      p(95)=0s
     http_req_waiting...............: avg=425.8ms  min=118ms  med=377ms   max=1.79s   p(90)=715ms   p(95)=975ms
     http_reqs......................: 9000    9.4/s
     iteration_duration.............: avg=7.5s     min=6.1s   med=7.4s    max=10.2s   p(90)=8.9s    p(95)=9.5s
     iterations.....................: 3000    3.1/s
     vus............................: 1       min=1       max=100
     vus_max........................: 100     min=100     max=100
```

**Interpretation**:
- ✅ All checks passed (100% success rate)
- ✅ 95th percentile < 2s (980ms, within threshold)
- ✅ Error rate 0% (target: < 1%)
- ✅ System stable under 100 concurrent users

---

## Conclusion

Scaling ÅbenForms is a journey, not a destination. As adoption grows, continuously:

1. **Monitor** performance and resource utilization
2. **Plan** capacity based on growth projections
3. **Test** scaling strategies in staging before production
4. **Implement** scaling incrementally
5. **Optimize** code, queries, and caching
6. **Repeat** as needed

**Key Takeaways**:

- **Start simple**: Vertical scaling for initial growth
- **Go horizontal**: Add web servers as traffic increases
- **Optimize first**: Caching and code optimization before adding servers
- **Test everything**: Load test before and after scaling
- **Monitor continuously**: Early detection prevents outages

---

**Document Version**: 1.0
**Last Updated**: February 2026
**Maintained By**: ÅbenForms Architecture Team
