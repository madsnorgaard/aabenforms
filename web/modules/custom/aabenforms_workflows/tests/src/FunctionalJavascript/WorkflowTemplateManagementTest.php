<?php

namespace Drupal\Tests\aabenforms_workflows\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests for workflow template management operations.
 *
 * @group aabenforms_workflows
 * @group functional_javascript
 */
class WorkflowTemplateManagementTest extends WebDriverTestBase {

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
   * BPMN template manager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected $templateManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer workflows',
      'administer eca',
    ]);

    $this->templateManager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');
  }

  /**
   * Test template import functionality.
   */
  public function testTemplateImport(): void {
    $this->drupalLogin($this->adminUser);

    // Navigate to template management page.
    $this->drupalGet('/admin/config/workflow/bpmn-templates');
    $this->assertSession()->statusCodeEquals(200);

    // Click import button.
    $page = $this->getSession()->getPage();
    $page->clickLink('Import Template');

    // Wait for import form.
    $this->assertSession()->waitForText('Import BPMN Template', 10000);

    // Create a valid BPMN XML.
    $bpmn_xml = $this->getValidBpmnXml();

    // Fill in template details.
    $page->fillField('template_id', 'custom_test_workflow');
    $page->fillField('template_name', 'Custom Test Workflow');
    $page->fillField('template_xml', $bpmn_xml);

    // Submit import.
    $page->pressButton('Import');

    // Verify success message.
    $this->assertSession()->waitForText('Template imported successfully', 10000);
    $this->assertSession()->pageTextContains('Template imported successfully');

    // Verify template appears in list.
    $this->assertSession()->pageTextContains('Custom Test Workflow');
  }

  /**
   * Test template export functionality.
   */
  public function testTemplateExport(): void {
    $this->drupalLogin($this->adminUser);

    // Navigate to template management page.
    $this->drupalGet('/admin/config/workflow/bpmn-templates');

    // Click export on a template.
    $page = $this->getSession()->getPage();
    $export_links = $page->findAll('css', '.template-export-link');
    $this->assertNotEmpty($export_links, 'Export links found');

    // Click first export link.
    $export_links[0]->click();

    // Should trigger download or show XML in modal.
    $this->assertSession()->waitForElement('css', '.bpmn-export-modal, .download-trigger', 10000);
  }

  /**
   * Test template deletion.
   */
  public function testTemplateDeletion(): void {
    $this->drupalLogin($this->adminUser);

    // First import a test template.
    $bpmn_xml = $this->getValidBpmnXml();
    $this->templateManager->saveTemplate('delete_test_workflow', $bpmn_xml);

    // Navigate to template management page.
    $this->drupalGet('/admin/config/workflow/bpmn-templates');

    // Find and click delete button for the test template.
    $page = $this->getSession()->getPage();
    $delete_buttons = $page->findAll('css', '.template-delete-button');
    $this->assertNotEmpty($delete_buttons, 'Delete buttons found');

    // Click delete.
    $delete_buttons[0]->click();

    // Confirm deletion in modal.
    $this->assertSession()->waitForText('Are you sure you want to delete this template?', 10000);
    $page->pressButton('Confirm Delete');

    // Verify deletion success.
    $this->assertSession()->waitForText('Template deleted successfully', 10000);
    $this->assertSession()->pageTextNotContains('delete_test_workflow');
  }

  /**
   * Test template validation on import.
   */
  public function testTemplateValidation(): void {
    $this->drupalLogin($this->adminUser);

    // Navigate to import form.
    $this->drupalGet('/admin/config/workflow/bpmn-templates/import');

    $page = $this->getSession()->getPage();

    // Try to import invalid XML.
    $invalid_xml = '<invalid>This is not BPMN XML</invalid>';

    $page->fillField('template_id', 'invalid_workflow');
    $page->fillField('template_name', 'Invalid Workflow');
    $page->fillField('template_xml', $invalid_xml);
    $page->pressButton('Import');

    // Should show validation error.
    $this->assertSession()->waitForText('Invalid BPMN XML', 10000);
    $this->assertSession()->pageTextContains('BPMN');
  }

  /**
   * Test template duplication prevention.
   */
  public function testDuplicateTemplateId(): void {
    $this->drupalLogin($this->adminUser);

    // Create a template.
    $bpmn_xml = $this->getValidBpmnXml();
    $this->templateManager->saveTemplate('duplicate_test', $bpmn_xml);

    // Navigate to import form.
    $this->drupalGet('/admin/config/workflow/bpmn-templates/import');

    $page = $this->getSession()->getPage();

    // Try to import with same ID.
    $page->fillField('template_id', 'duplicate_test');
    $page->fillField('template_name', 'Duplicate Test');
    $page->fillField('template_xml', $bpmn_xml);
    $page->pressButton('Import');

    // Should show error about duplicate ID.
    $this->assertSession()->waitForText('already exists', 10000);
    $this->assertSession()->pageTextContains('already exists');
  }

  /**
   * Test template preview in management interface.
   */
  public function testTemplatePreviewInManagement(): void {
    $this->drupalLogin($this->adminUser);

    // Navigate to template management page.
    $this->drupalGet('/admin/config/workflow/bpmn-templates');

    $page = $this->getSession()->getPage();

    // Click preview on a template.
    $preview_links = $page->findAll('css', '.template-preview-link');
    $this->assertNotEmpty($preview_links, 'Preview links found');

    $preview_links[0]->click();

    // Should show BPMN diagram in modal.
    $this->assertSession()->waitForElementVisible('css', '.bpmn-preview-modal', 10000);
    $this->assertSession()->elementExists('css', '.bpmn-diagram-container');

    // Diagram should be rendered (check for SVG).
    $this->assertSession()->elementExists('css', '.bpmn-diagram-container svg');
  }

  /**
   * Test template search/filter functionality.
   */
  public function testTemplateSearch(): void {
    $this->drupalLogin($this->adminUser);

    // Navigate to template management page.
    $this->drupalGet('/admin/config/workflow/bpmn-templates');

    $page = $this->getSession()->getPage();

    // Use search field.
    $this->assertSession()->fieldExists('template_search');
    $page->fillField('template_search', 'Building');

    // Wait for AJAX filter.
    $this->getSession()->wait(2000);

    // Should show only matching templates.
    $this->assertSession()->pageTextContains('Building Permit');
    $this->assertSession()->pageTextNotContains('Contact Form');
  }

  /**
   * Test template editing.
   */
  public function testTemplateEditing(): void {
    $this->drupalLogin($this->adminUser);

    // Create a test template.
    $bpmn_xml = $this->getValidBpmnXml();
    $this->templateManager->saveTemplate('edit_test_workflow', $bpmn_xml);

    // Navigate to template management page.
    $this->drupalGet('/admin/config/workflow/bpmn-templates');

    $page = $this->getSession()->getPage();

    // Click edit button.
    $edit_links = $page->findAll('css', '.template-edit-link');
    $this->assertNotEmpty($edit_links, 'Edit links found');

    $edit_links[0]->click();

    // Wait for edit form.
    $this->assertSession()->waitForText('Edit Template', 10000);

    // Modify template name.
    $page->fillField('template_name', 'Edited Workflow Name');

    // Save changes.
    $page->pressButton('Save');

    // Verify success.
    $this->assertSession()->waitForText('Template updated successfully', 10000);
    $this->assertSession()->pageTextContains('Edited Workflow Name');
  }

  /**
   * Returns a valid BPMN XML for testing.
   *
   * @return string
   *   Valid BPMN 2.0 XML.
   */
  protected function getValidBpmnXml(): string {
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
                  id="Definitions_test"
                  targetNamespace="http://aabenforms.dk/bpmn">
  <bpmn:process id="Process_Test" name="Test Workflow" isExecutable="true">
    <bpmn:documentation>[category: test]
    A test workflow for validation.
    </bpmn:documentation>

    <bpmn:startEvent id="StartEvent_1" name="Start">
      <bpmn:outgoing>Flow_1</bpmn:outgoing>
    </bpmn:startEvent>

    <bpmn:serviceTask id="Task_1" name="Test Task">
      <bpmn:incoming>Flow_1</bpmn:incoming>
      <bpmn:outgoing>Flow_2</bpmn:outgoing>
    </bpmn:serviceTask>

    <bpmn:endEvent id="EndEvent_1" name="Complete">
      <bpmn:incoming>Flow_2</bpmn:incoming>
    </bpmn:endEvent>

    <bpmn:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="Task_1"/>
    <bpmn:sequenceFlow id="Flow_2" sourceRef="Task_1" targetRef="EndEvent_1"/>
  </bpmn:process>
</bpmn:definitions>
XML;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up any test templates created.
    $test_templates = [
      'custom_test_workflow',
      'delete_test_workflow',
      'duplicate_test',
      'edit_test_workflow',
    ];

    foreach ($test_templates as $template_id) {
      try {
        $this->templateManager->deleteTemplate($template_id);
      }
      catch (\Exception $e) {
        // Template may not exist, ignore.
      }
    }

    parent::tearDown();
  }

}
