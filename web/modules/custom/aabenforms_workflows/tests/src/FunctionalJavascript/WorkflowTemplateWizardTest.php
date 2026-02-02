<?php

namespace Drupal\Tests\aabenforms_workflows\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for workflow template wizard.
 *
 * @group aabenforms_workflows
 * @group functional_javascript
 */
class WorkflowTemplateWizardTest extends WebDriverTestBase {

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
   * Administrator user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Test webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $testWebform;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer workflows',
      'administer webform',
      'access eca',
      'administer eca',
    ]);

    // Create a test webform.
    $this->testWebform = Webform::create([
      'id' => 'test_form',
      'title' => 'Test Form',
      'elements' => <<<YAML
applicant_email:
  '#type': email
  '#title': 'Applicant Email'
  '#required': true
applicant_name:
  '#type': textfield
  '#title': 'Applicant Name'
  '#required': true
YAML
    ]);
    $this->testWebform->save();
  }

  /**
   * Test complete workflow template wizard flow.
   */
  public function testWorkflowTemplateWizard(): void {
    $this->drupalLogin($this->adminUser);

    // 1. Navigate to template browser.
    $this->drupalGet('/admin/aabenforms/workflow-templates');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Workflow Templates');

    // 2. Verify templates are displayed.
    $this->assertSession()->pageTextContains('Building Permit');
    $this->assertSession()->pageTextContains('Contact Form');
    $this->assertSession()->pageTextContains('Company Verification');
    $this->assertSession()->pageTextContains('Daycare Enrollment');
    $this->assertSession()->pageTextContains('Event Registration');

    // 3. Click "Use This Template" for building permit.
    $page = $this->getSession()->getPage();

    // Find and click the first "Use This Template" button.
    $buttons = $page->findAll('css', '.template-card .use-template-button');
    $this->assertNotEmpty($buttons, 'Use template buttons found');
    $buttons[0]->click();

    // 4. Wizard Step 1: Template selected.
    $this->assertSession()->waitForText('Step 1: Select Template', 10000);
    $this->assertSession()->pageTextContains('Step 1: Select Template');
    $this->assertSession()->pageTextContains('Building Permit');
    $page->pressButton('Next');

    // 5. Wizard Step 2: Configure Webform.
    $this->assertSession()->waitForText('Step 2: Configure Webform', 10000);
    $this->assertSession()->pageTextContains('Step 2: Configure Webform');

    // Select webform.
    $page->selectFieldOption('webform_id', 'test_form');

    // Wait for AJAX to load field mappings.
    $this->assertSession()->waitForElementVisible('css', '#field-mappings', 10000);

    // Map email field.
    $page->selectFieldOption('email_field', 'applicant_email');

    $page->pressButton('Next');

    // 6. Wizard Step 3: Configure Actions.
    $this->assertSession()->waitForText('Step 3: Configure Actions', 10000);
    $this->assertSession()->pageTextContains('Step 3: Configure Actions');

    $page->fillField('caseworker_email', 'caseworker@test.dk');
    $page->pressButton('Next');

    // 7. Wizard Step 4: Data Visibility.
    $this->assertSession()->waitForText('Step 4: Data Visibility', 10000);
    $this->assertSession()->pageTextContains('Step 4: Data Visibility');

    $page->selectFieldOption('data_visibility', 'full');
    $page->pressButton('Next');

    // 8. Wizard Step 5: Preview & Activate.
    $this->assertSession()->waitForText('Step 5: Preview & Activate', 10000);
    $this->assertSession()->pageTextContains('Step 5: Preview & Activate');
    $this->assertSession()->pageTextContains('Building Permit');

    $page->fillField('workflow_name', 'Test Building Permit Workflow');
    $page->checkField('activate_now');
    $page->pressButton('Create Workflow');

    // 9. Verify workflow created.
    $this->assertSession()->waitForText('Workflow created successfully', 10000);
    $this->assertSession()->pageTextContains('Workflow created successfully');
    $this->assertSession()->pageTextContains('Test Building Permit Workflow');
  }

  /**
   * Test wizard navigation (back/next buttons).
   */
  public function testWizardNavigation(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/aabenforms/workflow-templates');
    $page = $this->getSession()->getPage();

    // Start wizard.
    $buttons = $page->findAll('css', '.template-card .use-template-button');
    $buttons[0]->click();

    // Step 1.
    $this->assertSession()->waitForText('Step 1: Select Template', 10000);
    $page->pressButton('Next');

    // Step 2.
    $this->assertSession()->waitForText('Step 2: Configure Webform', 10000);

    // Go back to step 1.
    $page->pressButton('Back');
    $this->assertSession()->waitForText('Step 1: Select Template', 10000);

    // Go forward again.
    $page->pressButton('Next');
    $this->assertSession()->waitForText('Step 2: Configure Webform', 10000);
  }

  /**
   * Test wizard validation.
   */
  public function testWizardValidation(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/aabenforms/workflow-templates');
    $page = $this->getSession()->getPage();

    // Start wizard.
    $buttons = $page->findAll('css', '.template-card .use-template-button');
    $buttons[0]->click();

    // Step 1.
    $this->assertSession()->waitForText('Step 1: Select Template', 10000);
    $page->pressButton('Next');

    // Step 2 - try to proceed without selecting webform.
    $this->assertSession()->waitForText('Step 2: Configure Webform', 10000);
    $page->pressButton('Next');

    // Should show validation error.
    $this->assertSession()->pageTextContains('Please select a webform');
  }

  /**
   * Test AJAX field mapping updates.
   */
  public function testAjaxFieldMappingUpdates(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/aabenforms/workflow-templates');
    $page = $this->getSession()->getPage();

    // Start wizard.
    $buttons = $page->findAll('css', '.template-card .use-template-button');
    $buttons[0]->click();

    // Navigate to step 2.
    $this->assertSession()->waitForText('Step 1: Select Template', 10000);
    $page->pressButton('Next');
    $this->assertSession()->waitForText('Step 2: Configure Webform', 10000);

    // Select webform - should trigger AJAX.
    $page->selectFieldOption('webform_id', 'test_form');

    // Wait for field mappings to appear.
    $this->assertSession()->waitForElementVisible('css', '#field-mappings', 10000);

    // Verify email field options are populated.
    $this->assertSession()->fieldExists('email_field');
    $this->assertSession()->optionExists('email_field', 'applicant_email');
  }

  /**
   * Test template preview functionality.
   */
  public function testTemplatePreview(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/aabenforms/workflow-templates');
    $page = $this->getSession()->getPage();

    // Click "Preview" button on a template.
    $preview_buttons = $page->findAll('css', '.template-card .preview-button');
    $this->assertNotEmpty($preview_buttons, 'Preview buttons found');

    $preview_buttons[0]->click();

    // Should open modal with BPMN diagram.
    $this->assertSession()->waitForElementVisible('css', '.bpmn-preview-modal', 10000);
    $this->assertSession()->elementExists('css', '.bpmn-diagram-container');
  }

  /**
   * Test workflow template categories filtering.
   */
  public function testTemplateCategoryFiltering(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/aabenforms/workflow-templates');
    $page = $this->getSession()->getPage();

    // Verify category filters are present.
    $this->assertSession()->fieldExists('category_filter');

    // Filter by category.
    $page->selectFieldOption('category_filter', 'municipal');

    // Wait for AJAX filter to complete.
    $this->assertSession()->waitForElement('css', '.template-card[data-category="municipal"]', 10000);

    // Should only show municipal templates.
    $this->assertSession()->pageTextContains('Building Permit');

    // Should not show citizen service templates.
    $this->assertSession()->pageTextNotContains('Contact Form');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up test data.
    if ($this->testWebform) {
      $this->testWebform->delete();
    }

    parent::tearDown();
  }

}
