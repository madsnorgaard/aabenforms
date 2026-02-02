<?php

/**
 * @file
 * Analyzes code coverage reports and provides detailed breakdown.
 */

$coverage_file = 'coverage/cobertura.xml';

if (!file_exists($coverage_file)) {
  echo "Coverage file not found. Run: phpunit --coverage-cobertura=coverage/cobertura.xml\n";
  exit(1);
}

$xml = simplexml_load_file($coverage_file);

if (!$xml) {
  echo "Failed to parse coverage XML file.\n";
  exit(1);
}

$metrics = $xml->xpath('//metrics[@statements]');

if (empty($metrics)) {
  echo "No coverage metrics found in XML.\n";
  exit(1);
}

$metrics = $metrics[0];

$total_lines = (int) $metrics['statements'];
$covered_lines = (int) $metrics['coveredstatements'];
$coverage_pct = $total_lines > 0 ? ($covered_lines / $total_lines) * 100 : 0;

echo "=== Overall Coverage ===\n";
echo "Total Lines: {$total_lines}\n";
echo "Covered Lines: {$covered_lines}\n";
echo "Coverage: " . number_format($coverage_pct, 2) . "%\n\n";

// Analyze by module.
echo "=== Coverage by Module ===\n";
$packages = $xml->xpath('//package');

$module_data = [];

foreach ($packages as $package) {
  $name = (string) $package['name'];
  $metrics = $package->metrics[0];
  $module_lines = (int) $metrics['statements'];
  $module_covered = (int) $metrics['coveredstatements'];
  $module_coverage = $module_lines > 0 ? ($module_covered / $module_lines) * 100 : 0;

  $module_data[] = [
    'name' => $name,
    'lines' => $module_lines,
    'covered' => $module_covered,
    'percentage' => $module_coverage,
  ];
}

// Sort by coverage percentage.
usort($module_data, function ($a, $b) {
  return $b['percentage'] <=> $a['percentage'];
});

foreach ($module_data as $module) {
  $status = $module['percentage'] >= 60 ? '[PASS]' : '[FAIL]';
  printf(
    "%s %-50s %5d/%5d lines (%5.1f%%)\n",
    $status,
    $module['name'],
    $module['covered'],
    $module['lines'],
    $module['percentage']
  );
}

echo "\n=== Summary ===\n";
$passing_modules = array_filter($module_data, fn($m) => $m['percentage'] >= 60);
$failing_modules = array_filter($module_data, fn($m) => $m['percentage'] < 60);

echo "Modules >= 60%: " . count($passing_modules) . "\n";
echo "Modules < 60%: " . count($failing_modules) . "\n\n";

if ($coverage_pct >= 60) {
  echo "[PASS] Overall coverage target achieved!\n";
  exit(0);
}
else {
  echo "[FAIL] Overall coverage below 60% target\n";
  echo "\nModules needing improvement:\n";
  foreach ($failing_modules as $module) {
    printf(
      "  - %s: %.1f%% (need +%.1f%%)\n",
      $module['name'],
      $module['percentage'],
      60 - $module['percentage']
    );
  }
  exit(1);
}
