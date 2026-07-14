<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Plugin\AabenformsEsdh;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_esdh\Attribute\EsdhConnector;
use Drupal\aabenforms_esdh\EsdhConnectorBase;
use Drupal\aabenforms_esdh\Exception\EsdhException;
use Drupal\aabenforms_esdh\Model\EsdhResult;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formpipe Acadre connector - production stub.
 *
 * Transport: Acadre PWS (Public Web Service, SOAP) for case operations and PWI
 * (Public Web Interface, REST) for autoprofiling. Auth: client certificate
 * (.pfx) + dedicated Acadre service user; requires Acadre >= v23 SP1 CU9.
 * A live implementation would create/update the sag, add documents, and add
 * the party. Public API documentation is thin, so operation names are not
 * hard-coded here.
 *
 * Not wired in this session: needs the PWS endpoint, client certificate, and a
 * service user. Fails hard until configured.
 */
#[EsdhConnector(
  id: 'acadre',
  label: new TranslatableMarkup('Formpipe Acadre (PWS/SOAP)'),
)]
final class AcadreEsdhConnector extends EsdhConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function journaliseCase(AabenformsCase $case, array $documents = []): EsdhResult {
    throw new EsdhException('Acadre connector is not configured (needs the PWS endpoint, a client certificate, and a service user). Use the demo connector in non-production.');
  }

}
