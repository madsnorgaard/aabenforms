<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Dashboard;

use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Discovers AabenForms dashboard section plugins.
 *
 * Plugins live at src/Plugin/AabenformsDashboard/*.php in any enabled
 * module, are annotated with #[AabenformsDashboardSection(...)] and
 * implement AabenformsDashboardSectionInterface.
 */
class AabenformsDashboardSectionManager extends DefaultPluginManager {

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/AabenformsDashboard',
      $namespaces,
      $module_handler,
      AabenformsDashboardSectionInterface::class,
      AabenformsDashboardSection::class,
    );

    $this->alterInfo('aabenforms_dashboard_section_info');
    $this->setCacheBackend($cache_backend, 'aabenforms_dashboard_section_plugins');
  }

  /**
   * Returns instantiated, applicable sections sorted by weight ascending.
   *
   * @return \Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionInterface[]
   */
  public function getApplicableSections(): array {
    $sections = [];
    foreach (array_keys($this->getDefinitions()) as $id) {
      /** @var \Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionInterface $section */
      $section = $this->createInstance($id);
      if ($section->isApplicable()) {
        $sections[$id] = $section;
      }
    }
    uasort($sections, static fn ($a, $b) => $a->getWeight() <=> $b->getWeight());
    return $sections;
  }

}
