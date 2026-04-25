<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_workflows\Unit\EventSubscriber;

use Drupal\aabenforms_workflows\EventSubscriber\PreserveWizardConfigsSubscriber;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests the PreserveWizardConfigsSubscriber.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\EventSubscriber\PreserveWizardConfigsSubscriber
 * @group aabenforms_workflows
 */
class PreserveWizardConfigsSubscriberTest extends TestCase {

  /**
   * Mock active storage.
   */
  private StorageInterface $activeStorage;

  /**
   * Mock source storage (from event).
   */
  private StorageInterface $sourceStorage;

  /**
   * Mock StorageTransformEvent.
   */
  private StorageTransformEvent $event;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->activeStorage = $this->createMock(StorageInterface::class);
    $this->sourceStorage = $this->createMock(StorageInterface::class);
    $this->event = $this->createMock(StorageTransformEvent::class);

    $this->event->method('getStorage')->willReturn($this->sourceStorage);
  }

  /**
   * Creates the subscriber with mocked active storage.
   */
  private function createSubscriber(): PreserveWizardConfigsSubscriber {
    return new PreserveWizardConfigsSubscriber($this->activeStorage);
  }

  /**
   * Tests getSubscribedEvents() returns correct event mapping.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents(): void {
    $events = PreserveWizardConfigsSubscriber::getSubscribedEvents();

    $this->assertArrayHasKey(ConfigEvents::STORAGE_TRANSFORM_IMPORT, $events);
    $this->assertEquals(['onImportTransform', 0], $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT]);
  }

  /**
   * Tests that wizard configs are copied when missing from source.
   *
   * @covers ::onImportTransform
   */
  public function testWizardConfigsCopiedWhenMissingFromSource(): void {
    $subscriber = $this->createSubscriber();

    $wizardConfig1 = 'eca.eca.building_permit_1700000000';
    $wizardConfig2 = 'aabenforms_workflows.template_instance.my_workflow';
    $configData1 = ['id' => 'building_permit_1700000000'];
    $configData2 = ['template' => 'my_workflow'];

    $this->activeStorage
      ->method('listAll')
      ->willReturn([$wizardConfig1, $wizardConfig2]);

    $this->sourceStorage
      ->method('exists')
      ->willReturnMap([
        [$wizardConfig1, FALSE],
        [$wizardConfig2, FALSE],
      ]);

    $this->activeStorage
      ->method('read')
      ->willReturnMap([
        [$wizardConfig1, $configData1],
        [$wizardConfig2, $configData2],
      ]);

    // Expect both configs to be written to source.
    $this->sourceStorage
      ->expects($this->exactly(2))
      ->method('write')
      ->with(
        $this->callback(fn($name) => in_array($name, [$wizardConfig1, $wizardConfig2])),
        $this->anything(),
      );

    $subscriber->onImportTransform($this->event);
  }

  /**
   * Tests that non-wizard configs are not copied.
   *
   * @covers ::onImportTransform
   */
  public function testNonWizardConfigsNotCopied(): void {
    $subscriber = $this->createSubscriber();

    $normalConfig = 'system.site';
    // No timestamp.
    $otherEcaConfig = 'eca.eca.manual_workflow';
    $otherModuleConfig = 'webform.webform.contact';

    $this->activeStorage
      ->method('listAll')
      ->willReturn([$normalConfig, $otherEcaConfig, $otherModuleConfig]);

    $this->sourceStorage
      ->method('exists')
      // None exist in source.
      ->willReturn(FALSE);

    // No configs should be written since none match wizard patterns.
    $this->sourceStorage
      ->expects($this->never())
      ->method('write');

    $subscriber->onImportTransform($this->event);
  }

  /**
   * Tests that already-present configs are not overwritten.
   *
   * @covers ::onImportTransform
   */
  public function testAlreadyPresentConfigsNotOverwritten(): void {
    $subscriber = $this->createSubscriber();

    $wizardConfig = 'eca.eca.existing_workflow_1700000000';

    $this->activeStorage
      ->method('listAll')
      ->willReturn([$wizardConfig]);

    // Config already exists in source.
    $this->sourceStorage
      ->method('exists')
      ->with($wizardConfig)
      ->willReturn(TRUE);

    // Should not be written.
    $this->sourceStorage
      ->expects($this->never())
      ->method('write');

    $subscriber->onImportTransform($this->event);
  }

  /**
   * Tests that eca.eca.<id>_<timestamp> pattern matches.
   *
   * @covers ::onImportTransform
   * @dataProvider wizardPatternProvider
   */
  public function testWizardPatternMatches(string $configName, bool $shouldMatch): void {
    $subscriber = $this->createSubscriber();

    $this->activeStorage
      ->method('listAll')
      ->willReturn([$configName]);

    $this->sourceStorage
      ->method('exists')
      ->willReturn(FALSE);

    $this->activeStorage
      ->method('read')
      ->willReturn(['some' => 'data']);

    if ($shouldMatch) {
      $this->sourceStorage
        ->expects($this->once())
        ->method('write');
    }
    else {
      $this->sourceStorage
        ->expects($this->never())
        ->method('write');
    }

    $subscriber->onImportTransform($this->event);
  }

  /**
   * Data provider for wizard pattern matching.
   */
  public static function wizardPatternProvider(): array {
    return [
      // ECA wizard configs.
      ['eca.eca.building_permit_1700000000', TRUE],
      ['eca.eca.contact_form_1699999999', TRUE],
      ['eca.eca.workflow_12345', TRUE],
      // Template instance configs.
      ['aabenforms_workflows.template_instance.my_workflow', TRUE],
      ['aabenforms_workflows.template_instance.test_123', TRUE],
      // Non-matching.
      // No timestamp.
      ['eca.eca.manual_workflow', FALSE],
      // Empty after underscore.
      ['eca.eca.workflow_', FALSE],
      ['system.site', FALSE],
      ['webform.webform.contact', FALSE],
      ['aabenforms_workflows.settings', FALSE],
    ];
  }

  /**
   * Tests that configs with FALSE data are not written.
   *
   * @covers ::onImportTransform
   */
  public function testConfigsWithFalseDataNotWritten(): void {
    $subscriber = $this->createSubscriber();

    $wizardConfig = 'eca.eca.corrupt_workflow_1700000000';

    $this->activeStorage
      ->method('listAll')
      ->willReturn([$wizardConfig]);

    $this->sourceStorage
      ->method('exists')
      ->willReturn(FALSE);

    // Active storage returns FALSE (corrupt/unreadable config).
    $this->activeStorage
      ->method('read')
      ->with($wizardConfig)
      ->willReturn(FALSE);

    // Should not be written.
    $this->sourceStorage
      ->expects($this->never())
      ->method('write');

    $subscriber->onImportTransform($this->event);
  }

  /**
   * Tests mixed configs - some wizard, some not.
   *
   * @covers ::onImportTransform
   */
  public function testMixedConfigs(): void {
    $subscriber = $this->createSubscriber();

    $wizardConfig = 'eca.eca.wizard_1700000000';
    $normalConfig = 'system.site';
    $templateConfig = 'aabenforms_workflows.template_instance.test';

    $this->activeStorage
      ->method('listAll')
      ->willReturn([$wizardConfig, $normalConfig, $templateConfig]);

    $this->sourceStorage
      ->method('exists')
      ->willReturn(FALSE);

    $this->activeStorage
      ->method('read')
      ->willReturnMap([
        [$wizardConfig, ['id' => 'wizard_1700000000']],
        [$templateConfig, ['template' => 'test']],
      ]);

    // Only wizard configs should be written (2 times).
    $this->sourceStorage
      ->expects($this->exactly(2))
      ->method('write');

    $subscriber->onImportTransform($this->event);
  }

}
