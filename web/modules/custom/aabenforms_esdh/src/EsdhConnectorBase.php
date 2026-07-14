<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class factoring the id/label/demo accessors from the plugin definition.
 */
abstract class EsdhConnectorBase extends PluginBase implements EsdhConnectorInterface {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return (string) $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) ($this->getPluginDefinition()['label'] ?? $this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function isDemo(): bool {
    return (bool) ($this->getPluginDefinition()['demo'] ?? FALSE);
  }

  /**
   * A KLE-scoped, PII-free case summary the connectors map into their payload.
   *
   * Deliberately never reads raw CPR: identity lives encrypted on the
   * submission, and the ESDH party is resolved server-side from the verified
   * session in a real transport, not copied from the case.
   *
   * @return array<string, string>
   *   Case fields safe to send to an ESDH.
   */
  protected function caseSummary(AabenformsCase $case): array {
    return [
      'title' => (string) ($case->get('title')->value ?? ''),
      'case_type' => $case->getCaseType(),
      'kle_emne' => (string) ($case->get('kle_emne')->value ?? ''),
      'handlekommune' => (string) ($case->get('handlekommune')->value ?? ''),
      'journal_ref' => (string) ($case->get('journal_ref')->value ?? ''),
    ];
  }

}
