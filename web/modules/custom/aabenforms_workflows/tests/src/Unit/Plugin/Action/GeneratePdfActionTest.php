<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Plugin\Action\GeneratePdfAction;
use Drupal\aabenforms_workflows\Service\PdfService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Psr\Log\LoggerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for GeneratePdfAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\GeneratePdfAction
 */
class GeneratePdfActionTest extends UnitTestCase {

  /**
   * The action plugin instance.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\GeneratePdfAction
   */
  protected $action;

  /**
   * The PDF service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PdfService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pdfService;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $submission;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pdfService = $this->createMock(PdfService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $configuration = [
      'template' => 'parking_permit',
      'filename_pattern' => '{template}_{submission_id}_{timestamp}.pdf',
      'orientation' => 'portrait',
      'size' => 'A4',
      'store_file_id_in' => 'pdf_file_id',
      'data_fields' => 'name:applicant_name
plate:license_plate
address:street_address',
    ];

    $this->action = new GeneratePdfAction(
      $configuration,
      'aabenforms_generate_pdf',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

    $reflection = new \ReflectionClass($this->action);
    $property = $reflection->getProperty('pdfService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->pdfService);
  }

  /**
   * @covers ::execute
   */
  public function testPdfGeneration(): void {
    $submissionData = [
      'applicant_name' => 'John Doe',
      'license_plate' => 'AB12345',
      'street_address' => 'Main Street 1',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('500');
    $this->submission->method('getCreatedTime')->willReturn(time());

    $pdfResult = [
      'status' => 'success',
      'file_id' => 1,
      'file_uri' => 'public://pdfs/parking_permit_500.pdf',
      'file_url' => 'https://example.com/files/parking_permit_500.pdf',
      'filename' => 'parking_permit_500.pdf',
    ];

    $this->pdfService->expects($this->once())
      ->method('generatePdf')
      ->with(
        'parking_permit',
        $this->callback(function ($data) {
          return isset($data['name']) && $data['name'] === 'John Doe'
            && isset($data['plate']) && $data['plate'] === 'AB12345'
            && isset($data['address']) && $data['address'] === 'Main Street 1';
        }),
        $this->callback(function ($options) {
          return $options['orientation'] === 'portrait'
            && $options['size'] === 'A4';
        })
      )
      ->willReturn($pdfResult);

    $this->submission->expects($this->exactly(4))
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(GeneratePdfAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_generate_pdf',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('pdfService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->pdfService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   * @covers ::preparePdfData
   */
  public function testTemplateRendering(): void {
    $submissionData = [
      'first_name' => 'Jane',
      'last_name' => 'Smith',
      'certificate_type' => 'marriage',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('501');
    $this->submission->method('getCreatedTime')->willReturn(1640000000);

    $configuration = $this->action->getConfiguration();
    $configuration['template'] = 'marriage_certificate';
    $configuration['data_fields'] = '';

    $actionWithConfig = new GeneratePdfAction(
      $configuration,
      'aabenforms_generate_pdf',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

    $this->pdfService->expects($this->once())
      ->method('generatePdf')
      ->with(
        'marriage_certificate',
        $this->callback(function ($data) {
          return isset($data['submission_id'])
            && isset($data['submission_date']);
        }),
        $this->anything()
      )
      ->willReturn([
        'status' => 'success',
        'file_id' => 2,
        'file_uri' => 'public://pdfs/certificate.pdf',
        'file_url' => 'https://example.com/files/certificate.pdf',
        'filename' => 'certificate.pdf',
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(GeneratePdfAction::class)
      ->setConstructorArgs([
        $configuration,
        'aabenforms_generate_pdf',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('pdfService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->pdfService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::preparePdfData
   */
  public function testFieldMapping(): void {
    $reflection = new \ReflectionClass($this->action);
    $method = $reflection->getMethod('preparePdfData');
    $method->setAccessible(TRUE);

    $submissionData = [
      'applicant_name' => 'Test User',
      'license_plate' => 'XY999',
      'street_address' => 'Test Street 123',
      'extra_field' => 'Should not be mapped',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('502');
    $this->submission->method('getCreatedTime')->willReturn(1640000000);

    $result = $method->invoke($this->action, $this->submission);

    $this->assertArrayHasKey('name', $result);
    $this->assertArrayHasKey('plate', $result);
    $this->assertArrayHasKey('address', $result);
    $this->assertArrayHasKey('submission_id', $result);
    $this->assertArrayHasKey('submission_date', $result);

    $this->assertEquals('Test User', $result['name']);
    $this->assertEquals('XY999', $result['plate']);
    $this->assertEquals('Test Street 123', $result['address']);
    $this->assertEquals('502', $result['submission_id']);
  }

  /**
   * @covers ::execute
   */
  public function testFileEntityCreation(): void {
    $submissionData = ['test' => 'data'];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('503');
    $this->submission->method('getCreatedTime')->willReturn(time());

    $pdfResult = [
      'status' => 'success',
      'file_id' => 10,
      'file_uri' => 'public://pdfs/document.pdf',
      'file_url' => 'https://example.com/files/document.pdf',
      'filename' => 'document.pdf',
    ];

    $this->pdfService->expects($this->once())
      ->method('generatePdf')
      ->willReturn($pdfResult);

    $this->submission->expects($this->exactly(4))
      ->method('setElementData')
      ->withConsecutive(
        ['pdf_file_id', 10],
        ['pdf_file_uri', 'public://pdfs/document.pdf'],
        ['pdf_file_url', 'https://example.com/files/document.pdf'],
        ['pdf_filename', 'document.pdf']
      );

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(GeneratePdfAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_generate_pdf',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('pdfService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->pdfService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testPdfServiceIntegration(): void {
    $submissionData = [
      'applicant_name' => 'Integration Test',
      'license_plate' => 'TEST123',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('504');
    $this->submission->method('getCreatedTime')->willReturn(time());

    $this->pdfService->expects($this->once())
      ->method('generatePdf')
      ->with(
        $this->equalTo('parking_permit'),
        $this->isType('array'),
        $this->callback(function ($options) {
          $this->assertArrayHasKey('filename', $options);
          $this->assertArrayHasKey('orientation', $options);
          $this->assertArrayHasKey('size', $options);
          $this->assertStringContainsString('.pdf', $options['filename']);
          return TRUE;
        })
      )
      ->willReturn([
        'status' => 'success',
        'file_id' => 99,
        'file_uri' => 'public://pdfs/test.pdf',
        'file_url' => 'https://example.com/test.pdf',
        'filename' => 'test.pdf',
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(GeneratePdfAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_generate_pdf',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('pdfService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->pdfService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::generateFilename
   */
  public function testFilenameGeneration(): void {
    $reflection = new \ReflectionClass($this->action);
    $method = $reflection->getMethod('generateFilename');
    $method->setAccessible(TRUE);

    $this->submission->method('id')->willReturn('999');

    $filename = $method->invoke($this->action, $this->submission);

    $this->assertStringContainsString('parking_permit', $filename);
    $this->assertStringContainsString('999', $filename);
    $this->assertStringEndsWith('.pdf', $filename);
  }

}
