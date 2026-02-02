#!/usr/bin/env php
<?php

/**
 * @file
 * Manual test script for parent approval system.
 *
 * Usage:
 *   ddev drush php:script web/modules/custom/aabenforms_workflows/tests/manual/test_approval_flow.php
 */

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

// Bootstrap Drupal.
if (PHP_SAPI !== 'cli') {
  die('This script must be run from the command line.');
}

echo "=== Parent Approval System Test ===\n\n";

// Check if module is enabled.
$moduleHandler = \Drupal::service('module_handler');
if (!$moduleHandler->moduleExists('aabenforms_workflows')) {
  die("ERROR: aabenforms_workflows module is not enabled.\n");
}

echo "[OK] Module enabled\n";

// Check if webform exists.
$webform = Webform::load('parent_request_form');
if (!$webform) {
  die("ERROR: parent_request_form webform not found.\n");
}

echo "[OK] Webform exists\n";

// Check services.
$token_service = \Drupal::service('aabenforms_workflows.approval_token');
if (!$token_service) {
  die("ERROR: approval_token service not found.\n");
}

echo "[OK] Token service available\n";

// Create test submission.
echo "\nCreating test submission...\n";

$values = [
  'webform_id' => 'parent_request_form',
  'data' => [
    'child_name' => 'Test Child',
    'child_cpr' => '010120-1234',
    'parent1_email' => 'parent1@test.com',
    'parent2_email' => 'parent2@test.com',
    'parents_together' => 'together',
    'request_details' => 'Test request for school enrollment',
    'parent1_status' => 'pending',
    'parent2_status' => 'pending',
    'caseworker_status' => 'pending',
  ],
];

try {
  $submission = WebformSubmission::create($values);
  $submission->save();

  $sid = $submission->id();
  echo "[OK] Submission created: ID = $sid\n";

  // Generate approval URLs.
  echo "\nGenerating approval URLs...\n";

  $token1 = $token_service->generateToken($sid, 1);
  $url1 = \Drupal::url('aabenforms_workflows.parent_approval', [
    'parent_number' => 1,
    'submission_id' => $sid,
    'token' => $token1,
  ], ['absolute' => TRUE]);

  echo "\nParent 1 Approval URL:\n$url1\n";

  $token2 = $token_service->generateToken($sid, 2);
  $url2 = \Drupal::url('aabenforms_workflows.parent_approval', [
    'parent_number' => 2,
    'submission_id' => $sid,
    'token' => $token2,
  ], ['absolute' => TRUE]);

  echo "\nParent 2 Approval URL:\n$url2\n";

  // Validate tokens.
  echo "\nValidating tokens...\n";

  $valid1 = $token_service->validateToken($sid, 1, $token1);
  $valid2 = $token_service->validateToken($sid, 2, $token2);

  echo "[" . ($valid1 ? 'OK' : 'FAIL') . "] Parent 1 token validation\n";
  echo "[" . ($valid2 ? 'OK' : 'FAIL') . "] Parent 2 token validation\n";

  // Test cross-validation (should fail).
  $invalid = $token_service->validateToken($sid, 2, $token1);
  echo "[" . (!$invalid ? 'OK' : 'FAIL') . "] Cross-validation rejection\n";

  // Test token expiration check.
  $expired = $token_service->isTokenExpired($token1);
  echo "[" . (!$expired ? 'OK' : 'FAIL') . "] Token not expired\n";

  // Test old token (should be expired).
  $old_token = $token_service->generateToken($sid, 1, time() - (8 * 24 * 60 * 60));
  $is_old_expired = $token_service->isTokenExpired($old_token);
  echo "[" . ($is_old_expired ? 'OK' : 'FAIL') . "] Old token expired\n";

  echo "\n=== Test Complete ===\n";
  echo "\nNext Steps:\n";
  echo "1. Copy the approval URLs above\n";
  echo "2. Open them in a browser\n";
  echo "3. Verify MitID login page displays\n";
  echo "4. Test approval flow\n";
  echo "\nTo delete test submission:\n";
  echo "  ddev drush sqlq \"DELETE FROM webform_submission WHERE sid=$sid\"\n";
  echo "  ddev drush sqlq \"DELETE FROM webform_submission_data WHERE sid=$sid\"\n";

}
catch (\Exception $e) {
  echo "[ERROR] " . $e->getMessage() . "\n";
  exit(1);
}
