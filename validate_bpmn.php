<?php

$directory = '/var/www/html/web/modules/custom/aabenforms_workflows/workflows';
$files = glob($directory . '/*.bpmn');

foreach ($files as $file) {
  $filename = basename($file);
  libxml_use_internal_errors(true);
  $xml = simplexml_load_file($file);

  if ($xml === false) {
    echo "✗ $filename INVALID\n";
    foreach (libxml_get_errors() as $error) {
      echo "  Error: " . trim($error->message) . "\n";
    }
    libxml_clear_errors();
  } else {
    echo "✓ $filename valid\n";
  }
}
