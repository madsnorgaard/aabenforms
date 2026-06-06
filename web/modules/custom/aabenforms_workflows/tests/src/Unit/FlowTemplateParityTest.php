<?php

namespace Drupal\Tests\aabenforms_workflows\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Guards parity between BPMN templates and active ECA flows.
 *
 * Each *.bpmn template in workflows/ is surfaced in the template wizard, so
 * it must be backed by an active eca.eca.<name>_flow config, and it must not
 * advertise a MitID identity step that the active flow does not actually run
 * (the divergence that left address_change/building_permit looking gated when
 * they were not).
 *
 * @group aabenforms_workflows
 */
class FlowTemplateParityTest extends UnitTestCase {

  /**
   * The MitID validate plugin id, referenced by both .bpmn and flow YAML.
   */
  protected const MITID = 'aabenforms_mitid_validate';

  /**
   * Every template has a matching flow, and MitID claims match.
   */
  public function testTemplatesMatchActiveFlows(): void {
    $moduleDir = dirname(__DIR__, 3);
    $workflowsDir = $moduleDir . '/workflows';
    $syncDir = $this->locateConfigSync();
    $this->assertNotNull($syncDir, 'Could not locate config/sync.');
    $this->assertDirectoryExists($workflowsDir);

    $missingFlow = [];
    $mitidDivergence = [];

    foreach (glob($workflowsDir . '/*.bpmn') as $template) {
      $name = basename($template, '.bpmn');
      $flowFile = $syncDir . '/eca.eca.' . $name . '_flow.yml';

      if (!is_file($flowFile)) {
        $missingFlow[] = $name;
        continue;
      }

      $templateHasMitid = str_contains((string) file_get_contents($template), self::MITID);
      $flowHasMitid = str_contains((string) file_get_contents($flowFile), self::MITID);
      if ($templateHasMitid && !$flowHasMitid) {
        $mitidDivergence[] = $name;
      }
    }

    $this->assertSame([], $missingFlow, "BPMN templates with no active flow:\n" . implode("\n", $missingFlow));
    $this->assertSame([], $mitidDivergence, "BPMN templates advertise a MitID step their flow does not run:\n" . implode("\n", $mitidDivergence));
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
