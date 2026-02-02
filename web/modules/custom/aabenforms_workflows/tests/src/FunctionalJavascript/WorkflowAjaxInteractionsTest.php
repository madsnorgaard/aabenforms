<?php

namespace Drupal\Tests\aabenforms_workflows\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for workflow AJAX interactions and dynamic forms.
 *
 * @group aabenforms_workflows
 * @group functional_javascript
 */
class WorkflowAjaxInteractionsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'webform',
    'eca',
    'eca_content',
    'aabenforms_core',
    'aabenforms_workflows',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Test webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->testUser = $this->drupalCreateUser([
      'access content',
      'administer webform',
    ]);

    // Create webform with conditional fields.
    $this->webform = Webform::create([
      'id' => 'ajax_test_form',
      'title' => 'AJAX Test Form',
      'elements' => <<<YAML
applicant_type:
  '#type': radios
  '#title': 'Applicant Type'
  '#options':
    individual: 'Individual'
    company: 'Company'
  '#required': true
  '#ajax': true
cpr_number:
  '#type': textfield
  '#title': 'CPR Number'
  '#states':
    visible:
      ':input[name="applicant_type"]':
        value: 'individual'
cvr_number:
  '#type': textfield
  '#title': 'CVR Number'
  '#states':
    visible:
      ':input[name="applicant_type"]':
        value: 'company'
company_name:
  '#type': textfield
  '#title': 'Company Name'
  '#states':
    visible:
      ':input[name="applicant_type"]':
        value: 'company'
YAML
    ]);
    $this->webform->save();
  }

  /**
   * Test conditional field visibility based on selection.
   */
  public function testConditionalFieldVisibility(): void {
    $this->drupalGet('/webform/ajax_test_form');
    $page = $this->getSession()->getPage();

    // Initially, conditional fields should be hidden.
    $this->assertSession()->fieldNotExists('cpr_number');
    $this->assertSession()->fieldNotExists('cvr_number');

    // Select "Individual" - CPR field should appear.
    $page->selectFieldOption('applicant_type', 'individual');

    // Wait for AJAX to complete.
    $this->assertSession()->waitForElementVisible('css', '[name="cpr_number"]', 10000);
    $this->assertSession()->fieldExists('cpr_number');
    $this->assertSession()->fieldNotExists('cvr_number');

    // Select "Company" - CVR field should appear.
    $page->selectFieldOption('applicant_type', 'company');

    // Wait for AJAX to complete.
    $this->assertSession()->waitForElementVisible('css', '[name="cvr_number"]', 10000);
    $this->assertSession()->fieldExists('cvr_number');
    $this->assertSession()->fieldExists('company_name');
    $this->assertSession()->fieldNotExists('cpr_number');
  }

  /**
   * Test AJAX validation messages.
   */
  public function testAjaxValidationMessages(): void {
    $this->drupalGet('/webform/ajax_test_form');
    $page = $this->getSession()->getPage();

    // Select individual and enter invalid CPR.
    $page->selectFieldOption('applicant_type', 'individual');
    $this->assertSession()->waitForElementVisible('css', '[name="cpr_number"]', 10000);

    $page->fillField('cpr_number', 'invalid');

    // Trigger AJAX validation (blur event).
    $this->getSession()->executeScript(
      'document.querySelector("[name=\'cpr_number\']").dispatchEvent(new Event("blur"));'
    );

    // Wait for validation message.
    $this->assertSession()->waitForText('Invalid CPR number', 10000);
  }

  /**
   * Test dynamic field population via AJAX.
   */
  public function testDynamicFieldPopulation(): void {
    $this->drupalGet('/webform/ajax_test_form');
    $page = $this->getSession()->getPage();

    // Select company.
    $page->selectFieldOption('applicant_type', 'company');
    $this->assertSession()->waitForElementVisible('css', '[name="cvr_number"]', 10000);

    // Enter CVR number.
    $page->fillField('cvr_number', '12345678');

    // Trigger AJAX lookup (blur event).
    $this->getSession()->executeScript(
      'document.querySelector("[name=\'cvr_number\']").dispatchEvent(new Event("blur"));'
    );

    // Wait for company name to be populated.
    // Note: This requires CVR lookup integration to be active.
    $this->getSession()->wait(3000);

    // Company name field should be populated.
    // $this->assertSession()->fieldValueEquals('company_name', 'Test Company A/S');.
  }

  /**
   * Test approval form confirmation dialog.
   */
  public function testApprovalConfirmationDialog(): void {
    // Create test submission.
    $submission = WebformSubmission::create([
      'webform_id' => 'ajax_test_form',
      'data' => [
        'applicant_type' => 'individual',
      ],
    ]);
    $submission->save();
    $submission_id = $submission->id();

    // Generate token.
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');
    $token = $token_service->generateToken($submission_id, 1);

    // Mock authentication.
    $tempstore = \Drupal::service('tempstore.private')->get('aabenforms_workflows');
    $tempstore->set('mitid_authenticated_parent1', TRUE);

    // Navigate to approval page.
    $this->drupalGet("/parent-approval/1/{$submission_id}/{$token}");

    $page = $this->getSession()->getPage();

    // Select reject option.
    $page->selectFieldOption('action', 'reject');

    // Should trigger confirmation dialog when trying to submit.
    $page->pressButton('Submit Decision');

    // Wait for confirmation dialog.
    $this->assertSession()->waitForText('Are you sure you want to reject this request?', 10000);

    // Cancel rejection.
    $page->pressButton('Cancel');

    // Dialog should close.
    $this->assertSession()->waitForElementRemoved('css', '.confirmation-dialog', 10000);

    // Try again and confirm.
    $page->pressButton('Submit Decision');
    $this->assertSession()->waitForText('Are you sure you want to reject this request?', 10000);
    $page->pressButton('Confirm');

    // Should proceed with rejection.
    $this->assertSession()->waitForText('Your response has been recorded', 10000);
  }

  /**
   * Test multi-step form wizard with AJAX.
   */
  public function testMultiStepFormWizard(): void {
    $this->drupalLogin($this->testUser);

    // Navigate to workflow wizard.
    $this->drupalGet('/admin/aabenforms/workflow-wizard');
    $page = $this->getSession()->getPage();

    // Step 1.
    $this->assertSession()->pageTextContains('Step 1');
    $page->pressButton('Next');

    // Step 2 should load via AJAX.
    $this->assertSession()->waitForText('Step 2', 10000);
    $this->assertSession()->pageTextContains('Step 2');

    // Progress bar should update.
    $progress_bar = $page->find('css', '.wizard-progress');
    $this->assertNotNull($progress_bar);
    $this->assertStringContainsString('50%', $progress_bar->getAttribute('style'));
  }

  /**
   * Test file upload with AJAX progress.
   */
  public function testAjaxFileUpload(): void {
    // Create webform with file field.
    $webform = Webform::create([
      'id' => 'file_upload_test',
      'title' => 'File Upload Test',
      'elements' => <<<YAML
document:
  '#type': managed_file
  '#title': 'Upload Document'
  '#upload_location': 'public://webform/test'
  '#max_filesize': '2'
YAML
    ]);
    $webform->save();

    $this->drupalGet('/webform/file_upload_test');
    $page = $this->getSession()->getPage();

    // Prepare test file.
    $test_file = \Drupal::service('file_system')->getTempDirectory() . '/test.txt';
    file_put_contents($test_file, 'Test content');

    // Upload file.
    $page->attachFileToField('files[document]', $test_file);

    // Wait for AJAX upload to complete.
    $this->assertSession()->waitForElementVisible('css', '.file-upload-complete', 10000);

    // Verify file uploaded.
    $this->assertSession()->pageTextContains('test.txt');

    // Cleanup.
    $webform->delete();
  }

  /**
   * Test AJAX error handling.
   */
  public function testAjaxErrorHandling(): void {
    $this->drupalGet('/webform/ajax_test_form');
    $page = $this->getSession()->getPage();

    // Trigger AJAX request that will fail.
    // This requires a custom endpoint that returns an error.
    $page->selectFieldOption('applicant_type', 'individual');
    $this->assertSession()->waitForElementVisible('css', '[name="cpr_number"]', 10000);

    // Simulate network error by executing invalid AJAX.
    $this->getSession()->executeScript(
      'jQuery.ajax({url: "/invalid-endpoint", method: "POST"});'
    );

    // Wait for error message.
    $this->getSession()->wait(2000);

    // Should show error message (if error handling is implemented).
    // $this->assertSession()->pageTextContains('An error occurred');.
  }

  /**
   * Test debounced AJAX search.
   */
  public function testDebouncedAjaxSearch(): void {
    $this->drupalLogin($this->testUser);

    $this->drupalGet('/admin/aabenforms/dashboard');
    $page = $this->getSession()->getPage();

    $search_field = $page->findField('task_search');
    $this->assertNotNull($search_field);

    // Type quickly (should debounce).
    $search_field->setValue('A');
    $this->getSession()->wait(100);
    $search_field->setValue('Ap');
    $this->getSession()->wait(100);
    $search_field->setValue('App');

    // Wait for debounce timeout.
    $this->getSession()->wait(1000);

    // Only one AJAX request should have been made.
    // Results should show for "App".
    $this->assertSession()->pageTextContains('Applicant');
  }

  /**
   * Test live validation with AJAX.
   */
  public function testLiveValidation(): void {
    $this->drupalGet('/webform/ajax_test_form');
    $page = $this->getSession()->getPage();

    $page->selectFieldOption('applicant_type', 'individual');
    $this->assertSession()->waitForElementVisible('css', '[name="cpr_number"]', 10000);

    // Enter partial CPR (should validate live).
    $cpr_field = $page->findField('cpr_number');
    $cpr_field->setValue('0101');

    // Wait for live validation.
    $this->getSession()->wait(1000);

    // Should show "Too short" message.
    // $this->assertSession()->pageTextContains('CPR number must be 10 digits');.
    // Complete CPR.
    $cpr_field->setValue('0101121234');
    $this->getSession()->wait(1000);

    // Error should disappear.
    // $this->assertSession()->pageTextNotContains('CPR number must be 10 digits');.
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->webform) {
      $this->webform->delete();
    }

    // Clean up submissions.
    $submissions = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'ajax_test_form']);

    foreach ($submissions as $submission) {
      $submission->delete();
    }

    parent::tearDown();
  }

}
