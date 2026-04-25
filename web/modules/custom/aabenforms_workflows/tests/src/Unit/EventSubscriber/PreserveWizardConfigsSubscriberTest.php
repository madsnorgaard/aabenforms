<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_workflows\Unit\EventSubscriber;

use Drupal\aabenforms_workflows\EventSubscriber\PreserveWizardConfigsSubscriber;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests PreserveWizardConfigsSubscriber.
 *
 * The contract is "wizard-instantiated runtime configs survive
 * `drush cim --yes` even though they aren't in config/sync". Three
 * branches matter:
 *   1. A wizard config in active but missing from source is copied over.
 *   2. A wizard config already present in source is left alone.
 *   3. A non-wizard config is ignored entirely.
 *
 * Plus the corruption guard: if active->read() returns FALSE we must not
 * write anything to source.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\EventSubscriber\PreserveWizardConfigsSubscriber
 * @group aabenforms_workflows
 */
class PreserveWizardConfigsSubscriberTest extends UnitTestCase {

  /**
   * A wizard ECA config in active but missing from source is copied to source.
   */
  public function testWizardEcaConfigCopiedWhenMissingFromSource(): void {
    $name = 'eca.eca.parking_permit_1714145000';
    $payload = ['id' => 'parking_permit_1714145000', 'label' => 'Parking permit'];

    $active = $this->createMock(StorageInterface::class);
    $active->method('listAll')->willReturn([$name]);
    $active->method('read')->with($name)->willReturn($payload);

    $source = $this->createMock(StorageInterface::class);
    $source->method('exists')->with($name)->willReturn(FALSE);
    $source->expects($this->once())
      ->method('write')
      ->with($name, $payload);

    $this->dispatchOn($active, $source);
  }

  /**
   * A wizard template_instance config in active but missing from source is copied.
   */
  public function testWizardTemplateInstanceConfigCopiedWhenMissingFromSource(): void {
    $name = 'aabenforms_workflows.template_instance.parking_permit_1714145000';
    $payload = ['template_id' => 'parking_permit', 'instance_id' => 'parking_permit_1714145000'];

    $active = $this->createMock(StorageInterface::class);
    $active->method('listAll')->willReturn([$name]);
    $active->method('read')->with($name)->willReturn($payload);

    $source = $this->createMock(StorageInterface::class);
    $source->method('exists')->with($name)->willReturn(FALSE);
    $source->expects($this->once())
      ->method('write')
      ->with($name, $payload);

    $this->dispatchOn($active, $source);
  }

  /**
   * Wizard configs already present in source are left alone (no overwrite).
   */
  public function testAlreadyPresentInSourceNotOverwritten(): void {
    $name = 'eca.eca.parking_permit_1714145000';

    $active = $this->createMock(StorageInterface::class);
    $active->method('listAll')->willReturn([$name]);
    $active->expects($this->never())->method('read');

    $source = $this->createMock(StorageInterface::class);
    $source->method('exists')->with($name)->willReturn(TRUE);
    $source->expects($this->never())->method('write');

    $this->dispatchOn($active, $source);
  }

  /**
   * Non-wizard configs are ignored regardless of presence in source.
   */
  public function testNonWizardConfigsIgnored(): void {
    $names = [
      // System config - not ours.
      'system.site',
      // ECA flow shipped in config/sync (no trailing _<digits>).
      'eca.eca.address_change_flow',
      // Webform we ship.
      'webform.webform.contact',
      // Random user role.
      'user.role.aabenforms_employee',
    ];

    // Exercise both source-presence states. The pattern-mismatch path
    // must short-circuit before either branch is reached, so neither
    // exists()=TRUE nor exists()=FALSE should trigger a read or write.
    foreach ([FALSE, TRUE] as $exists_in_source) {
      $active = $this->createMock(StorageInterface::class);
      $active->method('listAll')->willReturn($names);
      $active->expects($this->never())->method('read');

      $source = $this->createMock(StorageInterface::class);
      $source->method('exists')->willReturn($exists_in_source);
      $source->expects($this->never())->method('write');

      $this->dispatchOn($active, $source);
    }
  }

  /**
   * Corrupt active read (returns FALSE) does not write anything to source.
   */
  public function testReadFalseDoesNotWriteToSource(): void {
    $name = 'eca.eca.parking_permit_1714145000';

    $active = $this->createMock(StorageInterface::class);
    $active->method('listAll')->willReturn([$name]);
    $active->method('read')->with($name)->willReturn(FALSE);

    $source = $this->createMock(StorageInterface::class);
    $source->method('exists')->with($name)->willReturn(FALSE);
    $source->expects($this->never())->method('write');

    $this->dispatchOn($active, $source);
  }

  /**
   * Mixed batch: copies the wizard configs, ignores everything else.
   */
  public function testMixedBatchRoutesCorrectly(): void {
    $wizard1 = 'eca.eca.address_change_1714000000';
    $wizard2 = 'aabenforms_workflows.template_instance.address_change_1714000000';
    $shipped = 'eca.eca.address_change_flow';
    $unrelated = 'system.site';

    $active = $this->createMock(StorageInterface::class);
    $active->method('listAll')->willReturn([$wizard1, $wizard2, $shipped, $unrelated]);
    $active->method('read')->willReturnCallback(
      static fn (string $n) => ['payload-of' => $n],
    );

    $source = $this->createMock(StorageInterface::class);
    // Wizard configs missing from source, others present.
    $source->method('exists')->willReturnCallback(
      static fn (string $n) => !in_array($n, [$wizard1, $wizard2], TRUE),
    );
    $source->expects($this->exactly(2))
      ->method('write')
      ->willReturnCallback(function (string $n, array $data) use ($wizard1, $wizard2): void {
        $this->assertContains($n, [$wizard1, $wizard2]);
        $this->assertSame(['payload-of' => $n], $data);
      });

    $this->dispatchOn($active, $source);
  }

  /**
   * The subscriber registers exactly one listener on the import transform event.
   *
   * Asserts the entire returned array equals the expected single-entry
   * map so adding a stray subscription (e.g. someone hooks
   * STORAGE_TRANSFORM_EXPORT later by mistake) breaks this test
   * deliberately rather than slipping through.
   */
  public function testSubscribedEventsRegistration(): void {
    $events = PreserveWizardConfigsSubscriber::getSubscribedEvents();
    $this->assertSame(
      [ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform', 0]],
      $events,
    );
  }

  /**
   * Builds the subscriber and fires onImportTransform.
   *
   * Assertions are pre-staged on the active and source mocks before
   * dispatch.
   */
  private function dispatchOn(StorageInterface $active, StorageInterface $source): void {
    $subscriber = new PreserveWizardConfigsSubscriber($active);
    $event = new StorageTransformEvent($source);
    $subscriber->onImportTransform($event);
  }

}
