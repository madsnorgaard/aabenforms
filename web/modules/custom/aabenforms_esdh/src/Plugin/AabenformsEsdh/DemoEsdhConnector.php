<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Plugin\AabenformsEsdh;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_esdh\Attribute\EsdhConnector;
use Drupal\aabenforms_esdh\EsdhConnectorBase;
use Drupal\aabenforms_esdh\Model\EsdhResult;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Demo ESDH connector - synthesises a deterministic reference, no transport.
 *
 * The only connector that runs without configuration. Lets the full casework
 * lifecycle be demonstrated end to end; a real deployment selects a live
 * connector in the settings form.
 */
#[EsdhConnector(
  id: 'demo',
  label: new TranslatableMarkup('Demo (no live ESDH)'),
  demo: TRUE,
)]
final class DemoEsdhConnector extends EsdhConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function journaliseCase(AabenformsCase $case, array $documents = []): EsdhResult {
    // Deterministic reference from the case UUID (same shape as the SF1470 and
    // SF2900 demo stubs), so a re-run resolves to the same sagsnummer.
    $ref = 'ESDH-DEMO-' . strtoupper(substr(str_replace('-', '', (string) $case->uuid()), 0, 8));
    return EsdhResult::journalised($this->id(), $ref);
  }

}
