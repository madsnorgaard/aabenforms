<?php

namespace Drupal\aabenforms_webform\Service;

/**
 * Service for validating Danish CPR numbers (personnummer).
 *
 * CPR Format: DDMMYYXXXX
 * - DD: Day (01-31)
 * - MM: Month (01-12)
 * - YY: Year (00-99)
 * - XXXX: Sequence number + check digit.
 *
 * Validation includes:
 * - Format check (10 digits)
 * - Date validation (valid day/month/year)
 * - Modulus-11 check digit algorithm (when applicable)
 *
 * @see https://www.cpr.dk/cpr-systemet/personnumre-uden-kontrolciffer-modulus-11-kontrol
 */
class CprValidator {

  /**
   * Validates a CPR number.
   *
   * @param string $cpr
   *   The CPR number to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function isValid(string $cpr): bool {
    // Strip whitespace and hyphens.
    $cpr = preg_replace('/[\s-]/', '', $cpr);

    // Must be exactly 10 digits.
    if (!preg_match('/^\d{10}$/', $cpr)) {
      return FALSE;
    }

    // Validate date portion.
    if (!$this->isValidDate($cpr)) {
      return FALSE;
    }

    // Modulus-11 validation (if applicable).
    // Note: Since 2007, Denmark stopped using modulus-11 for new CPR numbers,
    // so we make this optional and return TRUE if it can't be validated.
    if ($this->canValidateModulus11($cpr)) {
      return $this->isValidModulus11($cpr);
    }

    // If no modulus-11 check possible, assume valid if format + date are OK.
    return TRUE;
  }

  /**
   * Validates the date portion of a CPR number.
   *
   * @param string $cpr
   *   The CPR number (10 digits).
   *
   * @return bool
   *   TRUE if the date is valid, FALSE otherwise.
   */
  protected function isValidDate(string $cpr): bool {
    $day = (int) substr($cpr, 0, 2);
    $month = (int) substr($cpr, 2, 2);
    $year = (int) substr($cpr, 4, 2);

    // Basic range checks.
    if ($day < 1 || $day > 31) {
      return FALSE;
    }
    if ($month < 1 || $month > 12) {
      return FALSE;
    }

    // Determine century based on sequence number (7th digit).
    $century = $this->determineCentury($cpr);
    $fullYear = $century + $year;

    // Validate with checkdate().
    return checkdate($month, $day, $fullYear);
  }

  /**
   * Determines the century for a CPR number.
   *
   * @param string $cpr
   *   The CPR number.
   *
   * @return int
   *   The century (1800, 1900, or 2000).
   */
  protected function determineCentury(string $cpr): int {
    $year = (int) substr($cpr, 4, 2);
    $sequenceDigit = (int) substr($cpr, 6, 1);

    // Century determination logic based on 7th digit.
    // Simplified version - full logic is more complex.
    if ($sequenceDigit >= 0 && $sequenceDigit <= 3) {
      return 1900;
    }
    elseif ($sequenceDigit >= 4 && $sequenceDigit <= 9) {
      if ($year >= 0 && $year <= 36) {
        return 2000;
      }
      else {
        return 1900;
      }
    }

    // Default fallback.
    return 1900;
  }

  /**
   * Checks if modulus-11 validation can be performed.
   *
   * CPR numbers issued after 2007 may not use modulus-11.
   *
   * @param string $cpr
   *   The CPR number.
   *
   * @return bool
   *   TRUE if modulus-11 can be checked, FALSE otherwise.
   */
  protected function canValidateModulus11(string $cpr): bool {
    // Modulus-11 was used until October 2007.
    // For now, we attempt validation for all numbers.
    // A production system might check the issue date.
    return TRUE;
  }

  /**
   * Validates CPR number using modulus-11 algorithm.
   *
   * @param string $cpr
   *   The CPR number (10 digits).
   *
   * @return bool
   *   TRUE if modulus-11 validation passes, FALSE otherwise.
   */
  protected function isValidModulus11(string $cpr): bool {
    $weights = [4, 3, 2, 7, 6, 5, 4, 3, 2, 1];
    $sum = 0;

    for ($i = 0; $i < 10; $i++) {
      $sum += ((int) $cpr[$i]) * $weights[$i];
    }

    return ($sum % 11) === 0;
  }

  /**
   * Extracts the birthdate from a CPR number.
   *
   * @param string $cpr
   *   The CPR number.
   *
   * @return \DateTime|null
   *   The birthdate, or NULL if invalid.
   */
  public function getBirthdate(string $cpr): ?\DateTime {
    if (!$this->isValid($cpr)) {
      return NULL;
    }

    $day = substr($cpr, 0, 2);
    $month = substr($cpr, 2, 2);
    $year = substr($cpr, 4, 2);

    $century = $this->determineCentury($cpr);
    $fullYear = $century + (int) $year;

    try {
      return new \DateTime("{$fullYear}-{$month}-{$day}");
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Determines if the CPR number is for a male or female.
   *
   * The last digit determines gender:
   * - Even = Female
   * - Odd = Male.
   *
   * @param string $cpr
   *   The CPR number.
   *
   * @return string|null
   *   'male', 'female', or NULL if invalid.
   */
  public function getGender(string $cpr): ?string {
    if (!$this->isValid($cpr)) {
      return NULL;
    }

    $lastDigit = (int) substr($cpr, -1);
    return ($lastDigit % 2 === 0) ? 'female' : 'male';
  }

}
