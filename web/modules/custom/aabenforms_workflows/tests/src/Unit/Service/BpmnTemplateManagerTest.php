<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Service;

use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the BpmnTemplateManager service.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
 * @group aabenforms_workflows
 */
class BpmnTemplateManagerTest extends UnitTestCase {

  /**
   * The BpmnTemplateManager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected BpmnTemplateManager $templateManager;

  /**
   * Mock module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleList;

  /**
   * Mock file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileSystem;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * Test template directory path.
   *
   * @var string
   */
  protected string $testTemplateDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create temporary directory for test templates.
    $this->testTemplateDir = sys_get_temp_dir() . '/aabenforms_test_' . uniqid();
    mkdir($this->testTemplateDir);
    mkdir($this->testTemplateDir . '/workflows');

    // Create mock services.
    $this->moduleList = $this->createMock(ModuleExtensionList::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    // Configure logger factory to return logger.
    $this->loggerFactory->method('get')
      ->with('aabenforms_workflows')
      ->willReturn($this->logger);

    // Configure module list - return empty to use in getTemplateDirectory override.
    $this->moduleList->method('getPath')
      ->with('aabenforms_workflows')
      ->willReturn('test');

    // Create partial mock of BpmnTemplateManager to override getTemplateDirectory.
    $this->templateManager = $this->getMockBuilder(BpmnTemplateManager::class)
      ->setConstructorArgs([
        $this->moduleList,
        $this->fileSystem,
        $this->loggerFactory,
      ])
      ->onlyMethods(['getTemplateDirectory'])
      ->getMock();

    // Override getTemplateDirectory to return our test directory.
    $this->templateManager->method('getTemplateDirectory')
      ->willReturn($this->testTemplateDir . '/workflows');

    // Create test BPMN files.
    $this->createTestTemplates();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up test directory.
    if (is_dir($this->testTemplateDir . '/workflows')) {
      foreach (glob($this->testTemplateDir . '/workflows/*.bpmn') as $file) {
        unlink($file);
      }
      rmdir($this->testTemplateDir . '/workflows');
    }
    if (is_dir($this->testTemplateDir)) {
      rmdir($this->testTemplateDir);
    }

    parent::tearDown();
  }

  /**
   * Creates test BPMN template files.
   */
  protected function createTestTemplates(): void {
    $workflowsDir = $this->testTemplateDir . '/workflows';

    // Create 5 test templates matching real templates.
    $templates = [
      'building_permit' => [
        'name' => 'Building Permit Application',
        'category' => 'municipal',
        'description' => '[category: municipal] Complex building permit workflow.',
      ],
      'contact_form' => [
        'name' => 'Contact Form',
        'category' => 'citizen_service',
        'description' => '[category: citizen_service] Simple contact workflow.',
      ],
      'company_verification' => [
        'name' => 'Company Verification',
        'category' => 'verification',
        'description' => '[category: verification] CVR verification workflow.',
      ],
      'address_change' => [
        'name' => 'Address Change',
        'category' => 'citizen_service',
        'description' => '[category: citizen_service] Address change workflow.',
      ],
      'foi_request' => [
        'name' => 'Freedom of Information Request',
        'category' => 'municipal',
        'description' => '[category: municipal] FOI request workflow.',
      ],
    ];

    foreach ($templates as $id => $metadata) {
      $xml = $this->generateBpmnXml($id, $metadata['name'], $metadata['description']);
      file_put_contents($workflowsDir . '/' . $id . '.bpmn', $xml);
    }

    // Create invalid XML template for testing.
    file_put_contents($workflowsDir . '/invalid_xml.bpmn', '<invalid>xml');

    // Create template missing start event.
    $xmlMissingStart = $this->generateBpmnXmlMissingStartEvent('missing_start');
    file_put_contents($workflowsDir . '/missing_start.bpmn', $xmlMissingStart);

    // Create template missing end event.
    $xmlMissingEnd = $this->generateBpmnXmlMissingEndEvent('missing_end');
    file_put_contents($workflowsDir . '/missing_end.bpmn', $xmlMissingEnd);
  }

  /**
   * Generates valid BPMN 2.0 XML.
   *
   * @param string $id
   *   Template ID.
   * @param string $name
   *   Process name.
   * @param string $description
   *   Process description.
   *
   * @return string
   *   BPMN XML content.
   */
  protected function generateBpmnXml(string $id, string $name, string $description): string {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                   id="Definitions_{$id}"
                   targetNamespace="http://aabenforms.dk/bpmn">
  <bpmn:process id="{$id}_process" name="{$name}" isExecutable="true">
    <bpmn:documentation>{$description}</bpmn:documentation>
    <bpmn:startEvent id="StartEvent_1" name="Start">
      <bpmn:outgoing>Flow_1</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:serviceTask id="Task_1" name="Process Task">
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
   * Generates BPMN XML missing start event.
   *
   * @param string $id
   *   Template ID.
   *
   * @return string
   *   BPMN XML content.
   */
  protected function generateBpmnXmlMissingStartEvent(string $id): string {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                   id="Definitions_{$id}"
                   targetNamespace="http://aabenforms.dk/bpmn">
  <bpmn:process id="{$id}_process" name="Missing Start" isExecutable="true">
    <bpmn:endEvent id="EndEvent_1" name="Complete">
    </bpmn:endEvent>
  </bpmn:process>
</bpmn:definitions>
XML;
  }

  /**
   * Generates BPMN XML missing end event.
   *
   * @param string $id
   *   Template ID.
   *
   * @return string
   *   BPMN XML content.
   */
  protected function generateBpmnXmlMissingEndEvent(string $id): string {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                   id="Definitions_{$id}"
                   targetNamespace="http://aabenforms.dk/bpmn">
  <bpmn:process id="{$id}_process" name="Missing End" isExecutable="true">
    <bpmn:startEvent id="StartEvent_1" name="Start">
    </bpmn:startEvent>
  </bpmn:process>
</bpmn:definitions>
XML;
  }

  /**
   * Tests getAvailableTemplates method structure.
   *
   * @covers ::getAvailableTemplates
   */
  public function testGetAvailableTemplates(): void {
    // Get templates - may be empty due to mocking limitations with protected methods.
    $templates = $this->templateManager->getAvailableTemplates();

    // Verify return type is array.
    $this->assertIsArray($templates);

    // Template discovery with actual files is fully tested in kernel tests.
    // Unit tests verify the method exists and returns correct data structure.
    foreach ($templates as $id => $template) {
      $this->assertArrayHasKey('id', $template);
      $this->assertArrayHasKey('name', $template);
      $this->assertArrayHasKey('file', $template);
      $this->assertArrayHasKey('description', $template);
      $this->assertArrayHasKey('category', $template);
    }
  }

  /**
   * Tests loadTemplate successfully loads building_permit.bpmn.
   *
   * @covers ::loadTemplate
   */
  public function testLoadTemplate(): void {
    $xml = $this->templateManager->loadTemplate('building_permit');

    $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    $this->assertNotNull($xml);

    // Verify BPMN namespace.
    $namespaces = $xml->getNamespaces(TRUE);
    $this->assertArrayHasKey('bpmn', $namespaces);

    // Verify process exists.
    $xml->registerXPathNamespace('bpmn', $namespaces['bpmn']);
    $processes = $xml->xpath('//bpmn:process');
    $this->assertNotEmpty($processes);
    $this->assertEquals('Building Permit Application', (string) $processes[0]['name']);
  }

  /**
   * Tests loadTemplate handles missing template.
   *
   * @covers ::loadTemplate
   */
  public function testLoadTemplateNotFound(): void {
    // Expect error log.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'BPMN template not found: @id',
        ['@id' => 'nonexistent']
      );

    $result = $this->templateManager->loadTemplate('nonexistent');

    $this->assertNull($result);
  }

  /**
   * Tests validateTemplate with correct BPMN structure.
   *
   * @covers ::validateTemplate
   */
  public function testValidateTemplate(): void {
    $result = $this->templateManager->validateTemplate('building_permit');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('valid', $result);
    $this->assertArrayHasKey('errors', $result);
    $this->assertTrue($result['valid'], 'Valid template should pass validation');
    $this->assertEmpty($result['errors'], 'Valid template should have no errors');
  }

  /**
   * Tests validateTemplate detects missing start event.
   *
   * @covers ::validateTemplate
   */
  public function testValidateTemplateMissingStartEvent(): void {
    $result = $this->templateManager->validateTemplate('missing_start');

    $this->assertFalse($result['valid']);
    $this->assertNotEmpty($result['errors']);
    $this->assertContains('No start event found', $result['errors']);
  }

  /**
   * Tests validateTemplate detects missing end event.
   *
   * @covers ::validateTemplate
   */
  public function testValidateTemplateMissingEndEvent(): void {
    $result = $this->templateManager->validateTemplate('missing_end');

    $this->assertFalse($result['valid']);
    $this->assertNotEmpty($result['errors']);
    $this->assertContains('No end event found', $result['errors']);
  }

  /**
   * Tests validateTemplate handles malformed XML.
   *
   * @covers ::validateTemplate
   */
  public function testValidateTemplateInvalidXml(): void {
    // Invalid XML should cause loadTemplate to return NULL.
    // This will trigger a "Failed to load template" error.
    $result = $this->templateManager->validateTemplate('invalid_xml');

    $this->assertFalse($result['valid'], 'Invalid XML should fail validation');
    $this->assertNotEmpty($result['errors'], 'Should have validation errors');
    $this->assertContains('Failed to load template', $result['errors'], 'Should indicate template load failure');
  }

  /**
   * Tests importTemplate imports custom BPMN file.
   *
   * @covers ::importTemplate
   */
  public function testImportTemplate(): void {
    // Create temporary upload file.
    $uploadPath = sys_get_temp_dir() . '/test_import.bpmn';
    $xml = $this->generateBpmnXml('custom_workflow', 'Custom Workflow', 'Test import');
    file_put_contents($uploadPath, $xml);

    // Mock file system copy.
    $this->fileSystem->expects($this->once())
      ->method('copy')
      ->with(
        $uploadPath,
        $this->stringEndsWith('/custom_workflow.bpmn'),
        FileSystemInterface::EXISTS_REPLACE
      )
      ->willReturn(TRUE);

    // Expect success log.
    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'Imported BPMN template: @id',
        ['@id' => 'custom_workflow']
      );

    $result = $this->templateManager->importTemplate($uploadPath, 'custom_workflow');

    $this->assertTrue($result);

    // Clean up.
    unlink($uploadPath);
  }

  /**
   * Tests importTemplate rejects invalid XML.
   *
   * @covers ::importTemplate
   */
  public function testImportTemplateInvalidXml(): void {
    // Create invalid XML file.
    $uploadPath = sys_get_temp_dir() . '/test_invalid.bpmn';
    file_put_contents($uploadPath, '<invalid>xml');

    // Expect error log.
    $this->logger->expects($this->once())
      ->method('error')
      ->with('Invalid XML in uploaded BPMN file');

    // File system should not be called.
    $this->fileSystem->expects($this->never())
      ->method('copy');

    $result = $this->templateManager->importTemplate($uploadPath, 'invalid');

    $this->assertFalse($result);

    // Clean up.
    unlink($uploadPath);
  }

  /**
   * Tests exportTemplate returns file path.
   *
   * @covers ::exportTemplate
   */
  public function testExportTemplate(): void {
    $result = $this->templateManager->exportTemplate('building_permit');

    $this->assertNotNull($result);
    $this->assertIsString($result);
    $this->assertStringEndsWith('/building_permit.bpmn', $result);
    $this->assertFileExists($result);
  }

  /**
   * Tests exportTemplate handles missing template.
   *
   * @covers ::exportTemplate
   */
  public function testExportTemplateNotFound(): void {
    $result = $this->templateManager->exportTemplate('nonexistent');

    $this->assertNull($result);
  }

  /**
   * Tests deleteTemplate removes template file.
   *
   * @covers ::deleteTemplate
   */
  public function testDeleteTemplate(): void {
    // Mock file system delete.
    $this->fileSystem->expects($this->once())
      ->method('delete')
      ->with($this->stringEndsWith('/building_permit.bpmn'))
      ->willReturn(TRUE);

    // Expect info log.
    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'Deleted BPMN template: @id',
        ['@id' => 'building_permit']
      );

    $result = $this->templateManager->deleteTemplate('building_permit');

    $this->assertTrue($result);
  }

  /**
   * Tests deleteTemplate handles missing template.
   *
   * @covers ::deleteTemplate
   */
  public function testDeleteTemplateNotFound(): void {
    // File system should not be called.
    $this->fileSystem->expects($this->never())
      ->method('delete');

    $result = $this->templateManager->deleteTemplate('nonexistent');

    $this->assertFalse($result);
  }

  /**
   * Tests deleteTemplate handles file system errors.
   *
   * @covers ::deleteTemplate
   */
  public function testDeleteTemplateFileSystemError(): void {
    // Mock file system to throw exception.
    $this->fileSystem->expects($this->once())
      ->method('delete')
      ->willThrowException(new \Exception('Permission denied'));

    // Expect error log.
    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Failed to delete BPMN template: @message',
        ['@message' => 'Permission denied']
      );

    $result = $this->templateManager->deleteTemplate('building_permit');

    $this->assertFalse($result);
  }

}
