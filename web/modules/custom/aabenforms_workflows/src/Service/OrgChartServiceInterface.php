<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

/**
 * Resolves an employee's manager from their employee identifier.
 *
 * Two implementations ship: StubOrgChartService (default) returns the
 * fallback email it's given so existing webform-driven approval flows
 * keep working. A site that wires LDAP / Active Directory / a real HR
 * system can decorate aabenforms_workflows.org_chart and replace
 * findManagerEmail() with a real lookup.
 */
interface OrgChartServiceInterface {

  /**
   * Returns the manager's email for the given employee identifier.
   *
   * @param string $employee_id
   *   Implementation-specific identifier (CPR, employee number, email).
   * @param string $fallback
   *   Email to return if no lookup is wired or the employee is unknown.
   *   Lets the caller pass through a webform-supplied "manager_email"
   *   field as a graceful degrade.
   *
   * @return string
   *   The resolved manager email, or the fallback if not found.
   */
  public function findManagerEmail(string $employee_id, string $fallback = ''): string;

}
