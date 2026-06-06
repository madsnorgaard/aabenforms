<?php

namespace Drupal\Tests\aabenforms_workflows\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards that every ECA content-entity event references an existing webform.
 *
 * The MED voting flow listened on a med_election_ballot webform that did not
 * exist, so the flow was dead. This test asserts the invariant across all
 * exported ECA flows so a dangling reference fails CI instead of shipping.
 *
 * @group aabenforms_workflows
 */
class FlowWebformReferencesTest extends UnitTestCase {

  /**
   * Every webform_submission event type must have a matching webform config.
   */
  public function testEveryFlowEventReferencesAnExistingWebform(): void {
    $syncDir = $this->locateConfigSync();
    $this->assertNotNull($syncDir, 'Could not locate the config/sync directory.');

    $missing = [];
    foreach (glob($syncDir . '/eca.eca.*.yml') as $flowFile) {
      $flow = Yaml::parseFile($flowFile);
      foreach (($flow['events'] ?? []) as $event) {
        $plugin = $event['plugin'] ?? '';
        if (!str_starts_with($plugin, 'content_entity:')) {
          continue;
        }
        $type = $event['configuration']['type'] ?? '';
        // The type is "webform_submission <webform_id>".
        if (!preg_match('/^webform_submission\s+([a-z0-9_]+)$/', $type, $m)) {
          continue;
        }
        $webformId = $m[1];
        if (!is_file($syncDir . '/webform.webform.' . $webformId . '.yml')) {
          $missing[] = sprintf('%s -> webform "%s"', basename($flowFile), $webformId);
        }
      }
    }

    $this->assertSame([], $missing, "ECA flows reference webforms that do not exist:\n" . implode("\n", $missing));
  }

  /**
   * Walks up from this file to find the config/sync directory.
   *
   * @return string|null
   *   The absolute path to config/sync, or NULL if not found.
   */
  protected function locateConfigSync(): ?string {
    $dir = __DIR__;
    for ($i = 0; $i < 10; $i++) {
      $candidate = $dir . '/config/sync';
      if (is_dir($candidate)) {
        return $candidate;
      }
      $parent = dirname($dir);
      if ($parent === $dir) {
        break;
      }
      $dir = $parent;
    }
    return NULL;
  }

}
