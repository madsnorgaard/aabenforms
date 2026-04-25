<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

/**
 * Default no-op org-chart implementation.
 *
 * Returns whatever fallback the caller hands us. Sites that want a real
 * org-chart lookup install/decorate a service implementing
 * OrgChartServiceInterface (e.g. an LDAP/AD bridge module).
 */
final class StubOrgChartService implements OrgChartServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function findManagerEmail(string $employee_id, string $fallback = ''): string {
    return $fallback;
  }

}
