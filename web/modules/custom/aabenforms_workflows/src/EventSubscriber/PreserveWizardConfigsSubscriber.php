<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preserves wizard-created workflow configs across drush cim.
 *
 * Wizard-created workflow instances live as runtime config entities
 * (eca.eca.<workflow_id>, aabenforms_workflows.template_instance.<workflow_id>)
 * but never get exported to config/sync. Without this subscriber,
 * `drush cim --yes` would delete them on every deploy because they're
 * not in the import source.
 *
 * Strategy: on STORAGE_TRANSFORM_IMPORT (fires before the importer
 * compares source vs active), copy every active-config item matching
 * our wizard-instance patterns into the source. The importer then sees
 * no diff and leaves them alone. Configs that ARE in config/sync still
 * import normally - this only protects items missing from sync.
 *
 * NOTE: this replaces the prior hook_storage_transform_import() which
 * was a no-op - that hook does not exist in Drupal 11. The mechanism is
 * an event (ConfigEvents::STORAGE_TRANSFORM_IMPORT) dispatched by
 * \Drupal\Core\Config\ImportStorageTransformer::transform().
 */
class PreserveWizardConfigsSubscriber implements EventSubscriberInterface {

  private const PATTERNS = [
    // ECA entities created from BPMN templates: <template_id>_<timestamp>.
    '/^eca\.eca\..+_\d+$/',
    // Custom template-instance metadata config.
    '/^aabenforms_workflows\.template_instance\..+$/',
  ];

  public function __construct(
    private readonly StorageInterface $activeStorage,
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform', 0],
    ];
  }

  public function onImportTransform(StorageTransformEvent $event): void {
    $source = $event->getStorage();
    foreach ($this->activeStorage->listAll() as $name) {
      if ($source->exists($name)) {
        continue;
      }
      foreach (self::PATTERNS as $regex) {
        if (preg_match($regex, $name)) {
          $data = $this->activeStorage->read($name);
          if ($data !== FALSE) {
            $source->write($name, $data);
          }
          break;
        }
      }
    }
  }

}
