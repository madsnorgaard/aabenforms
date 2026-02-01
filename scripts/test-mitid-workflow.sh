#!/bin/bash

echo "=== Testing ECA Workflow with Mock MitID Authentication ==="
echo ""

# Step 1: Create mock MitID session
echo "Step 1: Creating mock MitID session..."
ddev drush php-eval "
\$sessionManager = \Drupal::service('aabenforms_mitid.session_manager');
\$workflowId = 'test_workflow_' . time();

\$mockMitIdData = [
  'cpr' => '0101701234',
  'name' => 'Jane Doe',
  'authenticated_at' => time(),
  'expires_at' => time() + 900,
  'access_token' => 'mock_token_' . bin2hex(random_bytes(16)),
  'assurance_level' => 'substantial',
];

\$sessionManager->storeSession(\$workflowId, \$mockMitIdData);
echo '✓ MitID session created: ' . \$workflowId . PHP_EOL;
echo '  CPR: ' . \$mockMitIdData['cpr'] . PHP_EOL;
echo '  Name: ' . \$mockMitIdData['name'] . PHP_EOL;
file_put_contents('/tmp/workflow_id.txt', \$workflowId);
"

echo ""
echo "Step 2: Creating test webform..."
ddev drush php-eval "
use Drupal\webform\Entity\Webform;

\$webform = Webform::load('test_parent_form');
if (!\$webform) {
  \$webform = Webform::create([
    'id' => 'test_parent_form',
    'title' => 'Test Parent Form',
    'status' => 'open',
    'elements' => <<<YAML
child_name:
  '#type': textfield
  '#title': 'Child Name'
  '#required': true

child_age:
  '#type': number
  '#title': 'Child Age'
  '#required': true

parent_email:
  '#type': email
  '#title': 'Parent Email'
  '#required': true
YAML
  ]);
  \$webform->save();
  echo '✓ Created test webform: test_parent_form' . PHP_EOL;
} else {
  echo '✓ Using existing webform: test_parent_form' . PHP_EOL;
}
"

echo ""
echo "Step 3: Submitting webform (triggers ECA workflow)..."
ddev drush php-eval "
use Drupal\webform\Entity\WebformSubmission;

\$submission = WebformSubmission::create([
  'webform_id' => 'test_parent_form',
  'data' => [
    'child_name' => 'Emma Doe',
    'child_age' => 8,
    'parent_email' => 'jane.doe@example.com',
  ],
  'uid' => 1,
]);
\$submission->save();

echo '✓ Webform submitted: SID ' . \$submission->id() . PHP_EOL;
echo '  Child: Emma Doe (age 8)' . PHP_EOL;
echo '  Parent: jane.doe@example.com' . PHP_EOL;
echo '' . PHP_EOL;
echo '→ ECA workflow \"Parent Submission (Simple)\" should now execute!' . PHP_EOL;
"

echo ""
echo "Step 4: Checking workflow execution..."
WORKFLOW_ID=$(cat /tmp/workflow_id.txt)
echo "Workflow ID: $WORKFLOW_ID"

ddev drush php-eval "
\$workflowId = file_get_contents('/tmp/workflow_id.txt');
\$sessionManager = \Drupal::service('aabenforms_mitid.session_manager');
\$session = \$sessionManager->getSession(\$workflowId);

if (\$session) {
  echo '✓ MitID session still active' . PHP_EOL;
  echo '  CPR: ' . substr(\$session['cpr'], 0, 6) . 'XXXX (masked)' . PHP_EOL;
  echo '  Name: ' . \$session['name'] . PHP_EOL;
} else {
  echo '✗ Session not found' . PHP_EOL;
}
"

echo ""
echo "=== Workflow Test Complete ==="
echo ""
echo "Next steps:"
echo "1. Check ECA logs: ddev drush watchdog:show --type=eca --count=5"
echo "2. Check audit logs: ddev drush watchdog:show --type=aabenforms_core --count=5"
echo "3. View submission: Navigate to /admin/structure/webform/manage/test_parent_form/results"
echo "4. View ECA model: Navigate to /admin/config/workflow/eca"
echo ""
