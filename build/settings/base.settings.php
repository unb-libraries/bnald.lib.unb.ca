<?php
/*
Include all settings overrides here
# require_once 'settings_file.inc';
*/

// Load environment based includes.
if (isset($_SERVER['APPLICATION_ENV'])) {
  $environment = strtolower($_SERVER['APPLICATION_ENV']);
  $environment_include = dirname(__FILE__) . "/settings.$environment.inc";
  if (file_exists($environment_include)) {
    include_once $environment_include;
  }
}

$conf['chq_redis_cache_enabled'] = TRUE;
if (isset($conf['chq_redis_cache_enabled']) && $conf['chq_redis_cache_enabled']) {
  $conf['cache_class_cache'] = 'Redis_Cache';
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['cache_prefix']['default'] = 'bnald_';
  $settings['container_yamls'][] = 'modules/redis/example.services.yml';
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = 'drupal-redis-lib-unb-ca';
  $settings['redis.connection']['port'] = '6379';
}

// Add common includes below.
$databases['migrate']['default'] = array (
  'database'  => 'bnald',
  'username'  => 'root',
  'password'  => 'import',
  'host'      => 'mysqlimport',
  'port'      => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver'    => 'mysql',
);
