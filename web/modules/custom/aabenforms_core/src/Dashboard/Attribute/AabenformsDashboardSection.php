<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Dashboard\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Attribute for an AabenForms admin dashboard section plugin.
 *
 * Each card on /admin/aabenforms is a plugin annotated with this attribute.
 * Feature modules ship one plugin file under
 * src/Plugin/AabenformsDashboard/ and the dashboard manager picks it up.
 *
 * @see \Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AabenformsDashboardSection extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly int $weight = 0,
  ) {}

}
