# ÅbenForms Maintenance Guide

**Version**: 1.0
**Last Updated**: February 2026
**Target Audience**: System Administrators, DevOps Engineers

This guide provides procedures for maintaining the ÅbenForms platform in production, including regular maintenance tasks, updates, security patches, database optimization, and monitoring.

---

## Table of Contents

1. [Maintenance Overview](#maintenance-overview)
2. [Regular Maintenance Tasks](#regular-maintenance-tasks)
3. [Drupal Core Updates](#drupal-core-updates)
4. [Module Updates](#module-updates)
5. [Security Patch Procedures](#security-patch-procedures)
6. [Database Optimization](#database-optimization)
7. [Log Rotation and Monitoring](#log-rotation-and-monitoring)
8. [Performance Monitoring](#performance-monitoring)
9. [User Support Procedures](#user-support-procedures)
10. [Troubleshooting Common Issues](#troubleshooting-common-issues)

---

## Maintenance Overview

### Maintenance Philosophy

**Proactive vs. Reactive**:
- **Proactive**: Regular scheduled maintenance prevents issues
- **Reactive**: Emergency fixes after problems occur

**Goal**: 80% proactive, 20% reactive

### Maintenance Windows

**Standard Maintenance Windows** (no user notification required):
- **Daily**: 02:00-04:00 CET (automated tasks)
- **Weekly**: Sundays 06:00-08:00 CET (optional manual tasks)
- **Monthly**: First Sunday of month 06:00-10:00 CET (major updates)

**Emergency Maintenance**: As needed (with 1-hour notice if possible)

### Notification Requirements

| Impact Level | Notice Required | Notification Channels |
|--------------|----------------|----------------------|
| **No Impact** (cache clear, log rotation) | None | None |
| **Low Impact** (< 5 min downtime) | None | None |
| **Medium Impact** (5-30 min downtime) | 24 hours | Email, status page |
| **High Impact** (> 30 min downtime) | 72 hours | Email, status page, social media |

---

## Regular Maintenance Tasks

### Daily Tasks (Automated)

**2:00 AM - Automated Backups**

```bash
#!/bin/bash
# /usr/local/bin/aabenforms-daily-backup.sh

# Database backup
/usr/local/bin/drush sql:dump --gzip --result-file=/var/backups/aabenforms/db/aabenforms-$(date +\%Y\%m\%d).sql

# File backup (incremental)
rsync -av --delete /var/www/aabenforms/backend/web/sites/default/files/ \
  /var/backups/aabenforms/files/

# Upload to S3
aws s3 sync /var/backups/aabenforms/ s3://aabenforms-backups/

# Clean old backups (retain 30 days)
find /var/backups/aabenforms -type f -mtime +30 -delete

# Verify backup integrity
gunzip -t /var/backups/aabenforms/db/aabenforms-$(date +\%Y\%m\%d).sql.gz || \
  echo "Backup verification failed!" | mail -s "Backup Error" admin@aabenforms.dk
```

**Schedule in crontab**:
```bash
0 2 * * * /usr/local/bin/aabenforms-daily-backup.sh >> /var/log/aabenforms-backup.log 2>&1
```

**3:00 AM - Drupal Cron**

```bash
# Run Drupal cron (automated cache clearing, indexing, etc.)
*/15 * * * * cd /var/www/aabenforms/backend && drush cron >> /var/log/drupal-cron.log 2>&1
```

**3:30 AM - Log Rotation**

```bash
# Rotate Nginx logs
logrotate /etc/logrotate.d/nginx

# Rotate PHP-FPM logs
logrotate /etc/logrotate.d/php8.4-fpm

# Rotate application logs
logrotate /etc/logrotate.d/aabenforms
```

**4:00 AM - Database Optimization** (Sundays only)

```bash
# Optimize frequently-used tables
0 4 * * 0 /usr/local/bin/drush sqlq "OPTIMIZE TABLE cache_bootstrap, cache_config, cache_data, cache_discovery, cache_render;" >> /var/log/db-optimization.log 2>&1
```

### Weekly Tasks (Manual Review)

**Every Sunday Morning (1 hour)**

**Checklist**:

- [ ] **Review monitoring dashboards**
  - Check New Relic/Datadog for anomalies
  - Review uptime (should be ≥ 99.9%)
  - Check error rates (should be < 0.1%)
  - Review slow queries (> 2 seconds)

- [ ] **Review backup reports**
  ```bash
  # Verify last 7 days of backups exist
  ls -lh /var/backups/aabenforms/db/ | tail -7

  # Check backup sizes (sudden changes indicate issues)
  du -sh /var/backups/aabenforms/db/aabenforms-202602* | sort
  ```

- [ ] **Check security advisories**
  ```bash
  # Check Drupal security advisories
  drush pm:security

  # Check for available updates
  drush pm:security-php
  ```

- [ ] **Review error logs**
  ```bash
  # PHP errors
  tail -100 /var/log/php8.4-fpm-error.log | grep -i "fatal\|error"

  # Drupal watchdog
  drush watchdog:show --severity=Error --count=50

  # Nginx errors
  tail -100 /var/log/nginx/error.log
  ```

- [ ] **Check disk space**
  ```bash
  # Should have > 20% free space
  df -h /var/www/aabenforms
  df -h /var/backups

  # Alert if < 20% free
  USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
  if [ $USAGE -gt 80 ]; then
    echo "Disk usage critical: $USAGE%" | mail -s "Disk Space Alert" admin@aabenforms.dk
  fi
  ```

- [ ] **Review user accounts**
  ```bash
  # Check for locked accounts
  drush user:list --status=blocked

  # Check for inactive admins (> 90 days)
  drush sqlq "SELECT name, login FROM users_field_data WHERE uid > 0 AND login < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY)) ORDER BY login;"
  ```

- [ ] **Test critical functionality**
  - Submit test form via citizen portal
  - Process test application via case worker portal
  - Verify MitID test login
  - Check email notifications

### Monthly Tasks (2-4 hours)

**First Sunday of Each Month**

**Checklist**:

- [ ] **Apply security updates** (see [Security Patch Procedures](#security-patch-procedures))

- [ ] **Database optimization** (see [Database Optimization](#database-optimization))

- [ ] **Review and update documentation**
  - Update runbooks with new procedures
  - Document any incidents from previous month
  - Update FAQ based on support tickets

- [ ] **Capacity planning review**
  ```bash
  # Generate monthly metrics report
  drush aabenforms:metrics-report --start-date="first day of last month" --end-date="last day of last month"

  # Review:
  # - Total submissions (growth rate)
  # - Peak concurrent users
  # - Average response time
  # - Database size growth
  # - Storage usage trends
  ```

- [ ] **SSL certificate check**
  ```bash
  # Check certificate expiration (warn if < 30 days)
  echo | openssl s_client -connect aabenforms.dk:443 2>/dev/null | openssl x509 -noout -dates

  # Auto-renewal test (Let's Encrypt)
  certbot renew --dry-run
  ```

- [ ] **Dependency audit**
  ```bash
  # Check for outdated npm packages (frontend)
  cd /var/www/aabenforms/frontend
  npm outdated

  # Check for vulnerable packages
  npm audit

  # Check Composer dependencies (backend)
  cd /var/www/aabenforms/backend
  composer outdated
  composer audit
  ```

- [ ] **Performance tuning**
  - Review slow queries and add indexes if needed
  - Check Redis cache hit rate (should be > 90%)
  - Review CDN cache effectiveness
  - Optimize images (compress, convert to WebP)

- [ ] **Security audit**
  - Review failed login attempts
  - Check for suspicious activity in access logs
  - Verify firewall rules still appropriate
  - Review user permissions (principle of least privilege)

### Quarterly Tasks (4-8 hours)

**Every 3 Months**

**Checklist**:

- [ ] **Disaster recovery test**
  - Simulate server failure
  - Restore from backup to staging environment
  - Verify data integrity
  - Document recovery time (should meet RTO/RPO)

- [ ] **Load testing**
  - Run load tests with expected peak traffic
  - Verify performance under load
  - Identify bottlenecks
  - Document results and recommendations

- [ ] **Security penetration test**
  - Internal or external security audit
  - Address findings
  - Update security documentation

- [ ] **Review and update incident response plan**
  - Incorporate lessons learned from incidents
  - Update contact lists
  - Test escalation procedures

- [ ] **Dependency major version updates**
  - Evaluate major version updates for Drupal core
  - Test in staging environment
  - Plan upgrade if beneficial

- [ ] **Review SLAs and KPIs**
  - Compare actual performance to SLA commitments
  - Adjust infrastructure if needed
  - Report to stakeholders

---

## Drupal Core Updates

### Update Types

**Minor Updates** (e.g., 11.3.2 → 11.3.3):
- Bug fixes and minor improvements
- **Low risk**, minimal testing required
- Apply monthly during maintenance window

**Major Updates** (e.g., 11.3.x → 11.4.0):
- New features and API changes
- **Medium risk**, thorough testing required
- Apply quarterly after evaluation

**Version Updates** (e.g., 11.x → 12.0):
- Major architectural changes
- **High risk**, extensive testing required
- Plan 3-6 months in advance

### Minor Update Procedure

**Preparation** (Week before update):

1. **Check release notes**
   - URL: https://www.drupal.org/project/drupal/releases
   - Review changes and known issues
   - Verify compatibility with contrib modules

2. **Backup production**
   ```bash
   # Full backup before update
   /usr/local/bin/aabenforms-backup.sh
   ```

3. **Test in staging**
   ```bash
   # On staging server
   cd /var/www/aabenforms/backend
   composer update drupal/core-recommended drupal/core-composer-scaffold drupal/core-project-message --with-dependencies
   drush updatedb -y
   drush cache:rebuild

   # Run tests
   vendor/bin/phpunit web/modules/custom/

   # Manual smoke tests
   ```

**Deployment** (Sunday maintenance window):

```bash
#!/bin/bash
set -e

echo "=== Drupal Core Update Script ==="
echo "Starting at: $(date)"

# 1. Enable maintenance mode
cd /var/www/aabenforms/backend
drush state:set system.maintenance_mode TRUE -y
drush cache:rebuild

# 2. Create pre-update backup
/usr/local/bin/aabenforms-backup.sh

# 3. Update via Composer
composer update drupal/core-recommended drupal/core-composer-scaffold drupal/core-project-message --with-dependencies

# 4. Run database updates
drush updatedb -y

# 5. Import configuration (if needed)
drush config:import -y

# 6. Clear caches
drush cache:rebuild

# 7. Verify update
CURRENT_VERSION=$(drush status --field=drupal-version)
echo "Current Drupal version: $CURRENT_VERSION"

# 8. Run smoke tests
drush php:eval "echo 'PHP test: OK\n';"
drush sqlq "SELECT 1;" && echo "Database test: OK"

# 9. Disable maintenance mode
drush state:set system.maintenance_mode FALSE -y
drush cache:rebuild

echo "Update completed at: $(date)"

# 10. Send notification
echo "Drupal updated to $CURRENT_VERSION" | mail -s "Drupal Update Success" admin@aabenforms.dk
```

**Post-Update Verification**:

```bash
# Check for errors
drush watchdog:show --severity=Error --count=20

# Verify critical functionality
# - Submit test form
# - Process test application
# - Check MitID login
# - Verify API endpoints

# Monitor for 24 hours
# - Check error rates in New Relic
# - Review user reports
```

### Rollback Procedure

**If update fails or causes issues**:

```bash
#!/bin/bash
set -e

echo "=== ROLLBACK INITIATED ==="

# 1. Enable maintenance mode
drush state:set system.maintenance_mode TRUE -y

# 2. Restore database from pre-update backup
BACKUP_FILE=$(ls -t /var/backups/aabenforms/db/aabenforms-*.sql.gz | head -1)
echo "Restoring from: $BACKUP_FILE"
gunzip -c "$BACKUP_FILE" | drush sql:cli

# 3. Restore codebase
git log --oneline -5
echo "Enter commit hash to rollback to:"
read COMMIT_HASH
git reset --hard $COMMIT_HASH

# 4. Restore dependencies
composer install

# 5. Clear caches
drush cache:rebuild

# 6. Verify rollback
drush status

# 7. Disable maintenance mode
drush state:set system.maintenance_mode FALSE -y

echo "=== ROLLBACK COMPLETE ==="
echo "Rolled back to commit: $COMMIT_HASH"

# Notify team
echo "Rollback completed. Please investigate update failure." | mail -s "Drupal Update Rollback" admin@aabenforms.dk
```

---

## Module Updates

### Update Strategy

**Prioritization**:

1. **Security updates**: Apply immediately (within 24 hours)
2. **Bug fixes**: Apply in next monthly maintenance window
3. **New features**: Evaluate need, apply quarterly if beneficial

### Check for Updates

```bash
# Check for security updates
drush pm:security

# Check all available updates
drush pm:list --status=enabled --format=table

# Check specific module
drush pm:list --status=enabled | grep webform
```

### Update Single Module

**Example: Update Webform module**

```bash
# On staging first
cd /var/www/aabenforms/backend

# Check current version
drush pm:list --status=enabled | grep webform
# Output: webform 6.3.0-beta7

# Update via Composer
composer update drupal/webform --with-dependencies

# Check new version
composer show drupal/webform | grep versions
# Output: versions : * 6.3.0-beta8

# Run database updates
drush updatedb -y

# Clear caches
drush cache:rebuild

# Test affected functionality
# - Create new webform
# - Submit test webform
# - Check existing submissions
# - Verify workflows using webforms

# If successful, repeat on production
```

### Update Multiple Modules

**Monthly batch update**:

```bash
#!/bin/bash
# /usr/local/bin/aabenforms-monthly-update.sh

set -e

MODULES=(
  "drupal/webform"
  "drupal/eca"
  "drupal/domain"
  "drupal/encrypt"
  "drupal/jsonapi_extras"
)

echo "=== Monthly Module Update ==="

# Enable maintenance mode
drush state:set system.maintenance_mode TRUE -y

# Create backup
/usr/local/bin/aabenforms-backup.sh

# Update each module
for MODULE in "${MODULES[@]}"; do
  echo "Updating $MODULE..."
  composer update "$MODULE" --with-dependencies || {
    echo "Failed to update $MODULE"
    exit 1
  }
done

# Run database updates
drush updatedb -y

# Import configuration
drush config:import -y

# Clear caches
drush cache:rebuild

# Disable maintenance mode
drush state:set system.maintenance_mode FALSE -y

echo "=== Update Complete ==="

# Send report
drush pm:list --status=enabled --format=csv > /tmp/modules-after-update.csv
echo "Module update completed. See attached." | mail -s "Monthly Module Update" -a /tmp/modules-after-update.csv admin@aabenforms.dk
```

### Module Update Checklist

Before updating any module:

- [ ] Read module release notes
- [ ] Check for breaking changes
- [ ] Verify compatibility with current Drupal core version
- [ ] Review issue queue for reported bugs
- [ ] Test on staging environment
- [ ] Create rollback plan
- [ ] Schedule during maintenance window
- [ ] Notify team of update

---

## Security Patch Procedures

### Security Advisory Monitoring

**Subscribe to security announcements**:
- Drupal Security Team: https://www.drupal.org/security
- Email alerts: https://www.drupal.org/security/newsletter
- RSS feed: https://www.drupal.org/security/rss.xml

**Check security status**:
```bash
# Weekly check (automated)
drush pm:security --format=json > /tmp/security-status.json

# Parse and alert if vulnerabilities found
VULN_COUNT=$(cat /tmp/security-status.json | jq 'length')
if [ $VULN_COUNT -gt 0 ]; then
  echo "Security vulnerabilities detected: $VULN_COUNT" | mail -s "SECURITY ALERT" admin@aabenforms.dk
fi
```

### Security Update Workflow

**Critical Security Update** (Apply within 24 hours):

1. **Notification received**
   - Drupal Security Advisory (SA-CORE-YYYY-NNN)
   - Severity: Critical or Highly Critical

2. **Assessment** (1 hour)
   - Read advisory details
   - Determine if ÅbenForms is affected
   - Assess exploitability and risk
   - Estimate downtime for patching

3. **Communication** (15 minutes)
   - Notify stakeholders: "Security patch required, deploying in 2 hours"
   - Update status page: "Scheduled maintenance for security update"

4. **Testing on Staging** (1 hour)
   ```bash
   # Apply patch to staging
   cd /var/www/aabenforms/backend
   composer update drupal/core-recommended --with-dependencies
   drush updatedb -y
   drush cache:rebuild

   # Run automated tests
   vendor/bin/phpunit web/modules/custom/

   # Manual smoke test
   ```

5. **Production Deployment** (30 minutes)
   ```bash
   # Production deployment
   cd /var/www/aabenforms/backend

   # Maintenance mode
   drush state:set system.maintenance_mode TRUE -y

   # Backup
   /usr/local/bin/aabenforms-backup.sh

   # Apply update
   composer update drupal/core-recommended --with-dependencies
   drush updatedb -y
   drush config:import -y
   drush cache:rebuild

   # Verify
   drush status

   # Disable maintenance mode
   drush state:set system.maintenance_mode FALSE -y
   ```

6. **Verification** (30 minutes)
   - Test critical functionality
   - Monitor error logs
   - Check performance metrics

7. **Communication** (15 minutes)
   - Notify stakeholders: "Security patch applied successfully"
   - Update status page: "All systems operational"
   - Document in security log

**Total time**: ~4 hours (from notification to completion)

### Security Incident Response

**If vulnerability is actively exploited**:

1. **Immediate Actions** (Within 15 minutes)
   ```bash
   # Take site offline
   drush state:set system.maintenance_mode TRUE -y

   # OR block all traffic at firewall level
   sudo ufw deny 80
   sudo ufw deny 443

   # Create emergency backup
   /usr/local/bin/aabenforms-backup.sh

   # Notify CRITICAL stakeholders
   echo "SECURITY INCIDENT: Site taken offline for emergency patching" | \
     mail -s "SECURITY INCIDENT" admin@aabenforms.dk
   ```

2. **Incident Analysis** (1 hour)
   - Review logs for exploitation attempts
   - Assess data breach risk
   - Document timeline of events

3. **Patching** (2 hours)
   - Apply security patch
   - Verify patch effectiveness
   - Harden security (if applicable)

4. **Recovery** (1 hour)
   - Restore service
   - Monitor closely for 24 hours

5. **Post-Incident** (Following week)
   - Complete incident report
   - Notify affected users (if data breach)
   - Notify authorities (Datatilsynet if GDPR breach)
   - Update security procedures

---

## Database Optimization

### Regular Optimization Tasks

**Weekly** (Automated, Sundays 4:00 AM):

```bash
#!/bin/bash
# /usr/local/bin/db-weekly-optimization.sh

# Optimize cache tables (most frequently accessed)
drush sqlq "OPTIMIZE TABLE
  cache_bootstrap,
  cache_config,
  cache_data,
  cache_default,
  cache_discovery,
  cache_render,
  cache_entity,
  cache_menu,
  cache_page,
  cache_rest,
  cache_toolbar;"

# Analyze tables for query optimization
drush sqlq "ANALYZE TABLE
  webform_submission,
  webform_submission_data,
  users_field_data,
  node_field_data;"

echo "Weekly database optimization completed at $(date)" >> /var/log/db-optimization.log
```

**Monthly** (First Sunday):

```bash
# Clean up old sessions (> 30 days)
drush sqlq "DELETE FROM sessions WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY));"

# Clean up old flood records
drush sqlq "DELETE FROM flood WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));"

# Clean up old cache entries (> 7 days)
drush sqlq "DELETE FROM cache_bootstrap WHERE expire > 0 AND expire < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));"

# Check for orphaned files
drush entity:delete media --bundle=image --no-interaction --chunks=50
```

### Database Performance Monitoring

**Check slow queries**:

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';

-- Review slow queries
SELECT * FROM mysql.slow_log
ORDER BY query_time DESC
LIMIT 20;
```

**Analyze query performance**:

```bash
# Install pt-query-digest (Percona Toolkit)
sudo apt install -y percona-toolkit

# Analyze slow query log
pt-query-digest /var/log/mysql/slow-query.log > /tmp/slow-query-report.txt

# Review report
less /tmp/slow-query-report.txt
```

**Add missing indexes**:

```sql
-- Example: Add index to improve query performance
-- (Only after analyzing slow queries)

ALTER TABLE webform_submission_data
ADD INDEX idx_webform_data_lookup (webform_id, sid, name);

-- Verify index usage
EXPLAIN SELECT * FROM webform_submission_data
WHERE webform_id = 'parking_permit' AND sid = 123;
```

### Database Size Management

**Monitor database growth**:

```bash
# Check database size
drush sqlq "SELECT
  table_schema AS 'Database',
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'aabenforms'
GROUP BY table_schema;"

# Check largest tables
drush sqlq "SELECT
  table_name AS 'Table',
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'aabenforms'
ORDER BY (data_length + index_length) DESC
LIMIT 10;"
```

**Archive old data**:

```bash
#!/bin/bash
# Archive webform submissions older than 5 years

ARCHIVE_DATE="2021-01-01"

# Export to archive database
drush sqlq "INSERT INTO archive_db.webform_submission
  SELECT * FROM webform_submission
  WHERE created < UNIX_TIMESTAMP('$ARCHIVE_DATE');"

# Verify export
EXPORTED_COUNT=$(drush sqlq "SELECT COUNT(*) FROM archive_db.webform_submission;")
echo "Exported $EXPORTED_COUNT submissions to archive"

# Delete from production (after verification)
drush sqlq "DELETE FROM webform_submission
  WHERE created < UNIX_TIMESTAMP('$ARCHIVE_DATE');"

# Optimize tables
drush sqlq "OPTIMIZE TABLE webform_submission, webform_submission_data;"
```

---

## Log Rotation and Monitoring

### Log Files to Monitor

| Log File | Location | Purpose | Rotation |
|----------|----------|---------|----------|
| **Nginx Access** | `/var/log/nginx/access.log` | HTTP requests | Daily |
| **Nginx Error** | `/var/log/nginx/error.log` | Server errors | Daily |
| **PHP-FPM Error** | `/var/log/php8.4-fpm/error.log` | PHP errors | Daily |
| **Drupal Watchdog** | Database (`watchdog` table) | Application errors | 7 days |
| **MySQL Error** | `/var/log/mysql/error.log` | Database errors | Daily |
| **System Log** | `/var/log/syslog` | System events | Daily |
| **Application Log** | `/var/log/aabenforms.log` | Custom app logs | Daily |

### Log Rotation Configuration

**Nginx logs** (`/etc/logrotate.d/nginx`):

```bash
/var/log/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    prerotate
        if [ -d /etc/logrotate.d/httpd-prerotate ]; then \
            run-parts /etc/logrotate.d/httpd-prerotate; \
        fi
    endscript
    postrotate
        [ -s /run/nginx.pid ] && kill -USR1 `cat /run/nginx.pid`
    endscript
}
```

**PHP-FPM logs** (`/etc/logrotate.d/php8.4-fpm`):

```bash
/var/log/php8.4-fpm/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        /usr/lib/php/php-fpm-socket-helper reload /run/php/php8.4-fpm.sock
    endscript
}
```

**Application logs** (`/etc/logrotate.d/aabenforms`):

```bash
/var/log/aabenforms*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
}
```

### Drupal Watchdog Management

**Clear old watchdog entries**:

```bash
# Keep only last 7 days
drush watchdog:delete all --severity=0,1,2,3,4,5,6,7 \
  --older-than="7 days"

# Or via cron (automated)
0 3 * * * drush watchdog:delete all --older-than="7 days" --yes
```

**Monitor critical errors**:

```bash
# Daily error report
drush watchdog:show --severity=Error --count=50 --format=csv > \
  /tmp/daily-errors-$(date +\%Y\%m\%d).csv

# Email if critical errors found
CRITICAL_COUNT=$(drush watchdog:show --severity=Error --count=1000 | wc -l)
if [ $CRITICAL_COUNT -gt 10 ]; then
  echo "Critical errors detected: $CRITICAL_COUNT" | \
    mail -s "Critical Errors Alert" -a /tmp/daily-errors-$(date +\%Y\%m\%d).csv \
    admin@aabenforms.dk
fi
```

### Centralized Logging (Optional)

**ELK Stack** (Elasticsearch, Logstash, Kibana):

```bash
# Install Filebeat to ship logs to ELK
curl -L -O https://artifacts.elastic.co/downloads/beats/filebeat/filebeat-8.11.0-amd64.deb
sudo dpkg -i filebeat-8.11.0-amd64.deb

# Configure Filebeat
sudo nano /etc/filebeat/filebeat.yml
```

**Filebeat configuration**:

```yaml
filebeat.inputs:
- type: log
  enabled: true
  paths:
    - /var/log/nginx/*.log
    - /var/log/php8.4-fpm/error.log
    - /var/log/aabenforms.log

output.elasticsearch:
  hosts: ["elasticsearch.example.com:9200"]
  username: "elastic"
  password: "changeme"

setup.kibana:
  host: "kibana.example.com:5601"
```

---

## Performance Monitoring

### Key Performance Indicators (KPIs)

| Metric | Target | Alert Threshold | Measurement |
|--------|--------|----------------|-------------|
| **Response Time** | < 500ms | > 2s | New Relic APM |
| **Throughput** | > 100 req/s | < 50 req/s | Nginx logs |
| **Error Rate** | < 0.1% | > 1% | Error logs |
| **Database Query Time** | < 50ms | > 200ms | New Relic DB monitoring |
| **CPU Usage** | < 60% | > 80% | Server monitoring |
| **Memory Usage** | < 70% | > 85% | Server monitoring |
| **Disk I/O Wait** | < 5% | > 20% | iostat |
| **Cache Hit Rate** | > 90% | < 70% | Redis INFO |

### Monitoring Tools Setup

**New Relic APM** (Application Performance Monitoring):

Already configured via Deployment Guide. Review dashboards:

- **Application Dashboard**: Response times, throughput, errors
- **Database Dashboard**: Slow queries, query volume
- **Browser Dashboard**: Real user monitoring (RUM)
- **Infrastructure Dashboard**: Server metrics

**Custom Monitoring Script**:

```bash
#!/bin/bash
# /usr/local/bin/aabenforms-health-check.sh

# Website health check
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://aabenforms.dk)
if [ $HTTP_CODE -ne 200 ]; then
  echo "Website down! HTTP code: $HTTP_CODE" | mail -s "ALERT: Website Down" admin@aabenforms.dk
fi

# Database health check
drush sqlq "SELECT 1;" || {
  echo "Database connection failed!" | mail -s "ALERT: Database Down" admin@aabenforms.dk
}

# Redis health check
redis-cli ping || {
  echo "Redis connection failed!" | mail -s "ALERT: Redis Down" admin@aabenforms.dk
}

# Disk space check
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
  echo "Disk usage critical: $DISK_USAGE%" | mail -s "ALERT: Disk Space" admin@aabenforms.dk
fi

# CPU check
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
  echo "CPU usage high: $CPU_USAGE%" | mail -s "WARNING: High CPU" admin@aabenforms.dk
fi

# Memory check
MEM_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100)}')
if [ $MEM_USAGE -gt 85 ]; then
  echo "Memory usage high: $MEM_USAGE%" | mail -s "WARNING: High Memory" admin@aabenforms.dk
fi

echo "Health check completed at $(date)" >> /var/log/health-check.log
```

**Schedule health checks**:

```bash
# Run every 5 minutes
*/5 * * * * /usr/local/bin/aabenforms-health-check.sh
```

### Performance Tuning

**Identify bottlenecks**:

```bash
# Top 10 slowest pages
drush watchdog:show --type=page_load --count=100 --format=csv | \
  awk -F',' '{print $5}' | sort | uniq -c | sort -rn | head -10

# Most memory-intensive processes
ps aux --sort=-%mem | head -10

# Disk I/O bottlenecks
iostat -x 1 10
```

**Optimize based on findings**:

1. **Slow pages**: Add caching, optimize queries
2. **High memory**: Increase PHP memory limit, optimize code
3. **Disk I/O**: Add SSD, optimize database queries

---

## User Support Procedures

### Support Tiers

**Tier 1: Helpdesk** (Municipal IT)
- Password resets
- Account creation
- Basic "how-to" questions
- Browser compatibility issues

**Tier 2: Application Support** (ÅbenForms Support Team)
- Workflow configuration
- Integration issues
- Bug reports
- Feature requests

**Tier 3: Development** (ÅbenForms Dev Team)
- Code bugs requiring fixes
- Architecture issues
- Emergency patches

### Support Request Workflow

```
User Report
  ↓
Tier 1 Helpdesk
  ↓ (if cannot resolve)
Tier 2 Application Support
  ↓ (if technical issue)
Tier 3 Development
  ↓
Resolution
  ↓
User Notification
```

### Common Support Requests

**Password Reset**:

```bash
# Generate one-time login link
drush user:login USERNAME

# Send to user via email
```

**Account Locked**:

```bash
# Unblock user account
drush user:unblock USERNAME

# Clear flood records
drush sqlq "DELETE FROM flood WHERE identifier LIKE '%USERNAME%';"
```

**Workflow Not Executing**:

```bash
# Check workflow status
drush aabenforms:workflow-status WORKFLOW_ID

# Re-trigger workflow
drush aabenforms:trigger-workflow WORKFLOW_ID --submission=SUBMISSION_ID

# Check logs
drush watchdog:show --type=aabenforms --count=50
```

---

## Troubleshooting Common Issues

### Issue: Website is Slow

**Diagnosis**:

```bash
# Check server load
uptime

# Check CPU usage
top

# Check database performance
drush sqlq "SHOW FULL PROCESSLIST;"

# Check Redis cache
redis-cli INFO stats | grep keyspace_hits
```

**Solutions**:

1. Clear Drupal cache: `drush cr`
2. Restart PHP-FPM: `sudo systemctl restart php8.4-fpm`
3. Restart Redis: `sudo systemctl restart redis`
4. Optimize database: `drush sqlq "OPTIMIZE TABLE ..."`

### Issue: 500 Internal Server Error

**Diagnosis**:

```bash
# Check PHP error log
tail -50 /var/log/php8.4-fpm/error.log

# Check Nginx error log
tail -50 /var/log/nginx/error.log

# Check Drupal errors
drush watchdog:show --severity=Error --count=20
```

**Common Causes**:

- **Out of memory**: Increase PHP `memory_limit`
- **File permissions**: Fix with `chmod` and `chown`
- **Database connection**: Verify credentials in `settings.php`
- **Module error**: Disable problematic module

### Issue: Cron Not Running

**Diagnosis**:

```bash
# Check last cron run
drush core:cron

# Check cron schedule
crontab -l

# Check cron logs
grep CRON /var/log/syslog
```

**Solutions**:

1. Manually trigger cron: `drush cron`
2. Verify crontab entry exists
3. Check file permissions on cron script
4. Check for errors in cron log

---

## Conclusion

Regular maintenance is critical for the health, security, and performance of the ÅbenForms platform. By following this guide and establishing consistent maintenance routines, you'll ensure:

- **High availability** through proactive monitoring
- **Security** through timely updates and patches
- **Performance** through database optimization and tuning
- **User satisfaction** through responsive support

**Remember**:
- **Daily**: Automated backups, log monitoring
- **Weekly**: Manual review of metrics and logs
- **Monthly**: Security updates, database optimization
- **Quarterly**: DR testing, load testing, security audits

---

**Document Version**: 1.0
**Last Updated**: February 2026
**Maintained By**: ÅbenForms Operations Team
