<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Drush;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for keeping ECA flows owned by the workflow modeler.
 *
 * An ECA flow renders in the React workflow modeler only while its config
 * carries the `modeler_api.modeler_id` third-party setting. ModelOwnerBase
 * defaults a missing setting to "fallback", which drops the flow to the
 * BPMN.iO editor. Some ECA save paths (and editing a flow in BPMN.iO) strip
 * that setting, so flows silently regress to fallback even though config/sync
 * declares the modeler. `af:modeler-adopt` re-asserts it across every flow;
 * it is idempotent and safe to run as a post-deploy step.
 */
final class ModelerCommands extends DrushCommands {

  /**
   * The modeler id every ÅbenForms flow should be owned by.
   */
  private const MODELER_ID = 'workflow_modeler';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Ensure every ECA flow is owned by the React workflow modeler.
   *
   * Sets the `modeler_api.modeler_id` third-party setting to workflow_modeler
   * on any flow that is missing it (i.e. showing as "Fallback" / editable only
   * via BPMN.iO). Idempotent: flows already owned by the modeler are left
   * untouched.
   *
   * @command aabenforms:modeler:adopt
   * @aliases af:modeler-adopt
   * @usage drush aabenforms:modeler:adopt
   *   Repair every flow that has fallen back to the BPMN.iO editor.
   */
  public function adopt(): int {
    $storage = $this->entityTypeManager->getStorage('eca');
    /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface[] $flows */
    $flows = $storage->loadMultiple();

    $fixed = [];
    foreach ($flows as $id => $flow) {
      if ($flow->getThirdPartySetting('modeler_api', 'modeler_id', 'fallback') === self::MODELER_ID) {
        continue;
      }
      $flow->setThirdPartySetting('modeler_api', 'modeler_id', self::MODELER_ID);
      // ModelerApi shows this label in the list; default it to the flow id when
      // none is present so the row stays readable.
      if ($flow->getThirdPartySetting('modeler_api', 'label', '') === '') {
        $flow->setThirdPartySetting('modeler_api', 'label', $id);
      }
      $flow->save();
      $fixed[] = $id;
    }

    if ($fixed === []) {
      $this->io()->success(dt('All @count flows already use the workflow modeler.', [
        '@count' => count($flows),
      ]));
      return self::EXIT_SUCCESS;
    }

    $this->io()->success(dt('Adopted the workflow modeler on @n flow(s):', ['@n' => count($fixed)]));
    $this->io()->listing($fixed);
    return self::EXIT_SUCCESS;
  }

}
