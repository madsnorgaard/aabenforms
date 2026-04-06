<?php

$databases = [];
$databases['default']['default'] = array(
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASS'),
  'prefix' => '',
  'host' => getenv('DB_HOST'),
  'port' => getenv('DB_PORT' ?: '3306'),
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
  'driver' => 'mysql',
);

$settings['hash_salt'] = getenv('HASH_SALT');
$settings['update_free_access'] = false;
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['trusted_host_patterns'][] = getenv('TRUSTED_HOSTS');

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];
$settings['entity_update_batch_size'] = 100;
$settings['entity_update_backup'] = true;
$settings['config_sync_directory'] = '../config/sync';

if (extension_loaded('redis') && file_exists('modules/contrib/redis/redis.services.yml')) {
    $settings['redis.connection']['interface'] = 'PhpRedis';
    $settings['redis.connection']['host'] = getenv('REDIS_HOST');
    $settings['redis.connection']['port'] = '6379';
    $settings['cache']['default'] = 'cache.backend.redis';
    $settings['cache_prefix'] = 'aabenforms_redis_';
}

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
    include $app_root . '/' . $site_path . '/settings.local.php';
}

# Force SSL via reverse proxy (Traefik)
$settings['reverse_proxy'] = true;
$settings['reverse_proxy_addresses'] = array(@$_SERVER['REMOTE_ADDR']);
