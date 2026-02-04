<?php

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'web/autoload.php';
$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();
\Drupal::setContainer($container);

$pluginManager = \Drupal::service('plugin.manager.eca.action');
$definitions = $pluginManager->getDefinitions();
$count = 0;

echo "All ECA Action Plugins:\n";
echo "========================\n";

foreach ($definitions as $plugin_id => $definition) {
  $label_str = '';
  if (isset($definition['label'])) {
    $label_str = (string) $definition['label'];
  }

  if (strpos($plugin_id, 'aabenforms') !== false || strpos($label_str, 'ÅbenForms') !== false) {
    echo sprintf("ID: %s\n", $plugin_id);
    echo sprintf("Label: %s\n", $label_str);
    $provider = isset($definition['provider']) ? $definition['provider'] : 'N/A';
    echo sprintf("Provider: %s\n", $provider);
    echo "---\n";
    $count++;
  }
}

echo "\nTotal ÅbenForms ECA action plugins: " . $count . "\n";
