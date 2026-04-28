<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Service;

use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Drupal\aabenforms_workflows\Service\WorkflowTemplateMetadata;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for WorkflowTemplateMetadata service.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\WorkflowTemplateMetadata
 * @group aabenforms_workflows
 */
class WorkflowTemplateMetadataTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected WorkflowTemplateMetadata $sut;

  /**
   * Mock BPMN template manager.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $templateManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->templateManager = $this->createMock(BpmnTemplateManager::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    $this->sut = new WorkflowTemplateMetadata($this->templateManager, $loggerFactory);
  }

  /**
   * Builds a SimpleXMLElement BPMN document for fixtures.
   *
   * @param string $process_body
   *   Inner XML for the bpmn:process element.
   * @param string $prefix
   *   Either 'bpmn' or 'bpmn2', so we can exercise both namespace prefixes.
   * @param string|null $namespace
   *   Override the namespace URI; defaults to the canonical BPMN one.
   */
  protected function bpmn(string $process_body, string $prefix = 'bpmn', ?string $namespace = NULL): \SimpleXMLElement {
    $ns = $namespace ?? 'http://www.omg.org/spec/BPMN/20100524/MODEL';
    // The id attribute on <definitions> matters more than it looks: SimpleXML's
    // (bool) cast on a root element with only namespaced children evaluates to
    // FALSE - production's `if (!$xml) return [];` would early-return on every
    // fixture without it. Real BPMN files always carry id + targetNamespace,
    // which is why production never hit this. Mirror that here.
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<{$prefix}:definitions xmlns:{$prefix}="{$ns}" id="Definitions_test" targetNamespace="http://aabenforms.dk/test">
  <{$prefix}:process id="Process_1" name="Test Process">
    {$process_body}
  </{$prefix}:process>
</{$prefix}:definitions>
XML;
    return simplexml_load_string($xml);
  }

  /**
   * @covers ::getTemplateParameters
   */
  public function testGetTemplateParametersReturnsEmptyForNullId(): void {
    $this->templateManager->expects($this->never())->method('loadTemplate');
    $this->assertSame([], $this->sut->getTemplateParameters(NULL));
  }

  /**
   * @covers ::getTemplateParameters
   */
  public function testGetTemplateParametersReturnsEmptyForEmptyId(): void {
    $this->templateManager->expects($this->never())->method('loadTemplate');
    $this->assertSame([], $this->sut->getTemplateParameters(''));
  }

  /**
   * @covers ::getTemplateParameters
   */
  public function testGetTemplateParametersReturnsEmptyWhenLoadTemplateReturnsNull(): void {
    $this->templateManager->method('loadTemplate')->willReturn(NULL);
    $this->assertSame([], $this->sut->getTemplateParameters('any_id'));
  }

  /**
   * @covers ::getTemplateParameters
   */
  public function testGetTemplateParametersReturnsEmptyWhenNoBpmnNamespace(): void {
    // A document with no bpmn or bpmn2 namespace registered.
    $xml = simplexml_load_string('<root><child>x</child></root>');
    $this->templateManager->method('loadTemplate')->willReturn($xml);
    $this->assertSame([], $this->sut->getTemplateParameters('weird_template'));
  }

  /**
   * Parameters declared in documentation are extracted via the bpmn prefix.
   *
   * Also covers the parseOptions helper through a select-type parameter.
   *
   * @covers ::getTemplateParameters
   * @covers ::parseOptions
   * @covers ::addDefaultParameters
   */
  public function testGetTemplateParametersExtractsFromDocumentationBpmnNs(): void {
    $body = <<<XML
    <bpmn:documentation>
      Description.
      <parameters>
        <parameter id="topic" label="Topic" type="text" required="true" default="hello" description="The subject"/>
        <parameter id="severity" label="Severity" type="select" required="false" default="low">
          <options>
            <option value="low">Low</option>
            <option value="high">High</option>
          </options>
        </parameter>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body, 'bpmn'));

    $params = $this->sut->getTemplateParameters('unknown_id');

    // The unknown id only adds the workflow_label default, plus the two from the doc.
    $this->assertArrayHasKey('topic', $params);
    $this->assertArrayHasKey('severity', $params);
    $this->assertArrayHasKey('workflow_label', $params);
    $this->assertSame('Topic', $params['topic']['label']);
    $this->assertSame('text', $params['topic']['type']);
    $this->assertTrue($params['topic']['required']);
    $this->assertSame('hello', $params['topic']['default']);
    $this->assertSame('The subject', $params['topic']['description']);
    $this->assertSame([], $params['topic']['options']);
    $this->assertFalse($params['severity']['required']);
    $this->assertSame(['low' => 'Low', 'high' => 'High'], $params['severity']['options']);
  }

  /**
   * @covers ::getTemplateParameters
   */
  public function testGetTemplateParametersWorksWithBpmn2Prefix(): void {
    $body = <<<XML
    <bpmn2:documentation>
      <parameters>
        <parameter id="x" label="X" type="text" required="false"/>
      </parameters>
    </bpmn2:documentation>
XML;
    $this->templateManager->method('loadTemplate')
      ->willReturn($this->bpmn($body, 'bpmn2'));

    $params = $this->sut->getTemplateParameters('unknown_id');
    $this->assertArrayHasKey('x', $params);
  }

  /**
   * @covers ::getTemplateParameters
   * @covers ::addDefaultParameters
   * @covers ::getBuildingPermitDefaults
   */
  public function testGetTemplateParametersAddsBuildingPermitDefaults(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $params = $this->sut->getTemplateParameters('building_permit');

    $expected_keys = [
      'workflow_label',
      'applicant_email_field',
      'cpr_field',
      'address_field',
      'caseworker_email',
      'approval_deadline_days',
      'sbsys_integration',
    ];
    foreach ($expected_keys as $key) {
      $this->assertArrayHasKey($key, $params, "Missing $key");
    }
    $this->assertSame('integer', $params['approval_deadline_days']['type']);
    $this->assertSame('boolean', $params['sbsys_integration']['type']);
  }

  /**
   * @covers ::getTemplateParameters
   * @covers ::getContactFormDefaults
   */
  public function testGetTemplateParametersAddsContactFormDefaults(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $params = $this->sut->getTemplateParameters('contact_form');

    $this->assertArrayHasKey('submitter_email_field', $params);
    $this->assertArrayHasKey('recipient_email', $params);
    $this->assertArrayHasKey('auto_reply', $params);
    $this->assertArrayHasKey('confirmation_message', $params);
    $this->assertSame('boolean', $params['auto_reply']['type']);
    $this->assertSame('textarea', $params['confirmation_message']['type']);
  }

  /**
   * @covers ::getTemplateParameters
   * @covers ::getCompanyVerificationDefaults
   */
  public function testGetTemplateParametersAddsCompanyVerificationDefaults(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $params = $this->sut->getTemplateParameters('company_verification');

    $this->assertArrayHasKey('cvr_field', $params);
    $this->assertArrayHasKey('contact_email_field', $params);
    $this->assertArrayHasKey('verification_email', $params);
  }

  /**
   * @covers ::getTemplateParameters
   * @covers ::getFoiRequestDefaults
   */
  public function testGetTemplateParametersAddsFoiRequestDefaults(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $params = $this->sut->getTemplateParameters('foi_request');

    $this->assertArrayHasKey('requester_email_field', $params);
    $this->assertArrayHasKey('foi_officer_email', $params);
    $this->assertArrayHasKey('response_deadline_days', $params);
    $this->assertSame('integer', $params['response_deadline_days']['type']);
  }

  /**
   * @covers ::getTemplateParameters
   * @covers ::getAddressChangeDefaults
   */
  public function testGetTemplateParametersAddsAddressChangeDefaults(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $params = $this->sut->getTemplateParameters('address_change');

    $this->assertArrayHasKey('cpr_field', $params);
    $this->assertArrayHasKey('new_address_field', $params);
    $this->assertArrayHasKey('notification_email', $params);
  }

  /**
   * Unknown template id only adds the common workflow_label parameter.
   *
   * @covers ::getTemplateParameters
   * @covers ::addDefaultParameters
   */
  public function testGetTemplateParametersUnknownIdAddsOnlyWorkflowLabel(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $params = $this->sut->getTemplateParameters('not_a_template');

    $this->assertSame(['workflow_label'], array_keys($params));
    $this->assertTrue($params['workflow_label']['required']);
  }

  /**
   * @covers ::getConfigurableActions
   */
  public function testGetConfigurableActionsReturnsEmptyWhenLoadTemplateFails(): void {
    $this->templateManager->method('loadTemplate')->willReturn(NULL);
    $this->assertSame([], $this->sut->getConfigurableActions('any'));
  }

  /**
   * @covers ::getConfigurableActions
   */
  public function testGetConfigurableActionsReturnsEmptyForNoNamespace(): void {
    $xml = simplexml_load_string('<definitions><process/></definitions>');
    $this->templateManager->method('loadTemplate')->willReturn($xml);
    $this->assertSame([], $this->sut->getConfigurableActions('any'));
  }

  /**
   * Service + user tasks of every recognised type are returned.
   *
   * Non-matching tasks ('process data') are dropped because
   * determineActionType returns 'none'.
   *
   * @covers ::getConfigurableActions
   * @covers ::determineActionType
   * @covers ::getActionConfigurableFields
   */
  public function testGetConfigurableActionsClassifiesTasks(): void {
    $body = <<<XML
    <bpmn:serviceTask id="t_email" name="Send notification email"/>
    <bpmn:serviceTask id="t_lookup" name="Lookup citizen data"/>
    <bpmn:serviceTask id="t_audit" name="Log audit entry"/>
    <bpmn:userTask id="t_approval" name="Review and approval"/>
    <bpmn:userTask id="t_auth" name="MitID auth check"/>
    <bpmn:serviceTask id="t_skip" name="Process data crunching"/>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $actions = $this->sut->getConfigurableActions('any');

    $this->assertArrayHasKey('t_email', $actions);
    $this->assertArrayHasKey('t_lookup', $actions);
    $this->assertArrayHasKey('t_audit', $actions);
    $this->assertArrayHasKey('t_approval', $actions);
    $this->assertArrayHasKey('t_auth', $actions);
    // 'Process data' contains none of the trigger keywords; should be skipped.
    $this->assertArrayNotHasKey('t_skip', $actions);

    $this->assertSame('email', $actions['t_email']['type']);
    $this->assertSame('data_lookup', $actions['t_lookup']['type']);
    $this->assertSame('audit', $actions['t_audit']['type']);
    $this->assertSame('approval', $actions['t_approval']['type']);
    $this->assertSame('authentication', $actions['t_auth']['type']);

    // Email type exposes 3 configurable fields, approval exposes 2, others 0.
    $this->assertSame(['subject', 'body', 'recipient'], array_keys($actions['t_email']['configurable_fields']));
    $this->assertSame(['page_title', 'instructions'], array_keys($actions['t_approval']['configurable_fields']));
    $this->assertSame([], $actions['t_lookup']['configurable_fields']);
    $this->assertSame([], $actions['t_audit']['configurable_fields']);
    $this->assertSame([], $actions['t_auth']['configurable_fields']);
  }

  /**
   * @covers ::getConfigurableActions
   */
  public function testGetConfigurableActionsReturnsEmptyForUnnamedTasks(): void {
    // Tasks without trigger keywords resolve to 'none' and are dropped.
    $body = '<bpmn:serviceTask id="t1" name="Crunch numbers"/>';
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));
    $this->assertSame([], $this->sut->getConfigurableActions('any'));
  }

  /**
   * @covers ::validateConfiguration
   * @covers ::t
   */
  public function testValidateConfigurationFlagsMissingRequiredParameter(): void {
    $body = <<<XML
    <bpmn:documentation>
      <parameters>
        <parameter id="recipient_email" label="Recipient" type="email" required="true"/>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $errors = $this->sut->validateConfiguration('any', ['webform_id' => 'wf_1']);

    $this->assertNotEmpty($errors);
    $combined = implode("\n", $errors);
    $this->assertStringContainsString('Recipient', $combined);
  }

  /**
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationFlagsMissingWebformId(): void {
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn(''));

    $errors = $this->sut->validateConfiguration('any', [
      'parameters' => ['workflow_label' => 'Test'],
    ]);

    $combined = implode("\n", $errors);
    $this->assertStringContainsString('Webform must be selected', $combined);
  }

  /**
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationFlagsInvalidEmail(): void {
    $body = <<<XML
    <bpmn:documentation>
      <parameters>
        <parameter id="contact" label="Contact" type="email" required="false"/>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $errors = $this->sut->validateConfiguration('any', [
      'webform_id' => 'wf_1',
      'parameters' => [
        'workflow_label' => 'Test',
        'contact' => 'not-an-email',
      ],
    ]);

    $combined = implode("\n", $errors);
    $this->assertStringContainsString('Invalid email', $combined);
  }

  /**
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationFlagsInvalidInteger(): void {
    $body = <<<XML
    <bpmn:documentation>
      <parameters>
        <parameter id="days" label="Days" type="integer" required="false"/>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $errors = $this->sut->validateConfiguration('any', [
      'webform_id' => 'wf_1',
      'parameters' => [
        'workflow_label' => 'Test',
        'days' => 'abc',
      ],
    ]);

    $combined = implode("\n", $errors);
    $this->assertStringContainsString('Invalid integer', $combined);
  }

  /**
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationAcceptsValidPayload(): void {
    $body = <<<XML
    <bpmn:documentation>
      <parameters>
        <parameter id="contact" label="Contact" type="email" required="true"/>
        <parameter id="days" label="Days" type="integer" required="false"/>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $errors = $this->sut->validateConfiguration('any', [
      'webform_id' => 'wf_1',
      'parameters' => [
        'workflow_label' => 'Test',
        'contact' => 'a@b.dk',
        'days' => '7',
      ],
    ]);

    $this->assertSame([], $errors);
  }

  /**
   * Empty parameter values for typed fields skip the type validation branch.
   *
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationSkipsEmptyTypedValues(): void {
    $body = <<<XML
    <bpmn:documentation>
      <parameters>
        <parameter id="contact" label="Contact" type="email" required="false"/>
        <parameter id="days" label="Days" type="integer" required="false"/>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $errors = $this->sut->validateConfiguration('any', [
      'webform_id' => 'wf_1',
      'parameters' => [
        'workflow_label' => 'Test',
        'contact' => '',
        'days' => '',
      ],
    ]);

    $this->assertSame([], $errors);
  }

  /**
   * @covers ::getTemplatePreview
   */
  public function testGetTemplatePreviewReturnsEmptyForUnknownTemplate(): void {
    $this->templateManager->method('getAvailableTemplates')->willReturn([
      'other' => ['description' => 'Other'],
    ]);
    $this->assertSame([], $this->sut->getTemplatePreview('missing'));
  }

  /**
   * @covers ::getTemplatePreview
   */
  public function testGetTemplatePreviewReturnsEmptyWhenLoadTemplateFails(): void {
    $this->templateManager->method('getAvailableTemplates')->willReturn([
      'building_permit' => ['description' => 'Building permit flow'],
    ]);
    $this->templateManager->method('loadTemplate')->willReturn(NULL);
    $this->assertSame([], $this->sut->getTemplatePreview('building_permit'));
  }

  /**
   * @covers ::getTemplatePreview
   */
  public function testGetTemplatePreviewReturnsEmptyForNoNamespace(): void {
    $this->templateManager->method('getAvailableTemplates')->willReturn([
      'building_permit' => ['description' => 'Building permit flow'],
    ]);
    $xml = simplexml_load_string('<definitions/>');
    $this->templateManager->method('loadTemplate')->willReturn($xml);
    $this->assertSame([], $this->sut->getTemplatePreview('building_permit'));
  }

  /**
   * @covers ::getTemplatePreview
   */
  public function testGetTemplatePreviewBuildsStepsAndDescription(): void {
    $this->templateManager->method('getAvailableTemplates')->willReturn([
      'building_permit' => ['description' => 'Building permit flow'],
    ]);
    $body = <<<XML
    <bpmn:serviceTask id="t1" name="Send acknowledgement"/>
    <bpmn:userTask id="t2" name="Caseworker review"/>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $preview = $this->sut->getTemplatePreview('building_permit');

    $this->assertSame('Building permit flow', $preview['description']);
    $this->assertNull($preview['diagram']);
    $this->assertCount(2, $preview['steps']);
    $this->assertSame(1, $preview['steps'][0]['step']);
    $this->assertSame('Send acknowledgement', $preview['steps'][0]['name']);
    $this->assertSame('serviceTask', $preview['steps'][0]['type']);
    $this->assertSame(2, $preview['steps'][1]['step']);
    $this->assertSame('Caseworker review', $preview['steps'][1]['name']);
    $this->assertSame('userTask', $preview['steps'][1]['type']);
  }

  /**
   * Float values are rejected by the integer check (cast to int loses data).
   *
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationRejectsFloatForInteger(): void {
    $body = <<<XML
    <bpmn:documentation>
      <parameters>
        <parameter id="days" label="Days" type="integer" required="false"/>
      </parameters>
    </bpmn:documentation>
XML;
    $this->templateManager->method('loadTemplate')->willReturn($this->bpmn($body));

    $errors = $this->sut->validateConfiguration('any', [
      'webform_id' => 'wf_1',
      'parameters' => [
        'workflow_label' => 'Test',
        // is_numeric is TRUE but intval(7.5) != '7.5' so this should error.
        'days' => '7.5',
      ],
    ]);

    $combined = implode("\n", $errors);
    $this->assertStringContainsString('Invalid integer', $combined);
  }

}
