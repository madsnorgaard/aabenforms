<?php

namespace Drupal\aabenforms_webform\Service;

/**
 * Service for validating Danish CVR numbers (company registration number).
 *
 * CVR Format: XXXXXXXX (8 digits)
 * - 8 digits total
 * - Uses modulus-11 checksum with weights [2,7,6,5,4,3,2,1]
 *
 * Validation includes:
 * - Format check (8 digits)
 * - Modulus-11 checksum validation
 *
 * @see https://www.virk.dk/myndigheder/stat/cvr
 */
class CvrValidator {

  /**
   * Validates a CVR number.
   *
   * @param string $cvr
   *   The CVR number to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function isValid(string $cvr): bool {
    // Strip whitespace and hyphens.
    $cvr = preg_replace('/[\s-]/', '', $cvr);

    // Must be exactly 8 digits.
    if (!preg_match('/^\d{8}$/', $cvr)) {
      return FALSE;
    }

    // Modulus-11 checksum validation.
    return $this->isValidModulus11($cvr);
  }

  /**
   * Validates CVR number using modulus-11 algorithm.
   *
   * CVR uses different weights than CPR: [2,7,6,5,4,3,2,1]
   *
   * @param string $cvr
   *   The CVR number (8 digits).
   *
   * @return bool
   *   TRUE if modulus-11 validation passes, FALSE otherwise.
   */
  protected function isValidModulus11(string $cvr): bool {
    $weights = [2, 7, 6, 5, 4, 3, 2, 1];
    $sum = 0;

    for ($i = 0; $i < 8; $i++) {
      $sum += ((int) $cvr[$i]) * $weights[$i];
    }

    return ($sum % 11) === 0;
  }

  /**
   * Formats a CVR number for display.
   *
   * Formats as: "12 34 56 78"
   *
   * @param string $cvr
   *   The CVR number to format.
   *
   * @return string
   *   The formatted CVR number.
   */
  public function format(string $cvr): string {
    // Strip whitespace and hyphens.
    $cvr = preg_replace('/[\s-]/', '', $cvr);

    // If not 8 digits, return as-is.
    if (!preg_match('/^\d{8}$/', $cvr)) {
      return $cvr;
    }

    // Format as "12 34 56 78".
    return substr($cvr, 0, 2) . ' ' .
           substr($cvr, 2, 2) . ' ' .
           substr($cvr, 4, 2) . ' ' .
           substr($cvr, 6, 2);
  }

}
