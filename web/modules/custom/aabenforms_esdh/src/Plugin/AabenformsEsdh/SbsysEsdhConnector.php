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
 * SBSYS connector (community-owned ESDH, ~45 kommuner) - production stub.
 *
 * Transport: SBSIP (SBSYS IntegrationsPlatform) REST, OAuth2 (client_credentials
 * or password grant). Base URL is customer-specific (e.g.
 * https://sbsysapi.<kommune>.dk). A live implementation would:
 *   1. POST api/token (OAuth2) -> bearer token.
 *   2. POST api/v10/sag/template  (create case FROM A CASE TEMPLATE - SBSIP
 *      cannot create a case without a template id; store per-case-type
 *      template ids in config).
 *   3. api/sag/{id}/part          (add the citizen as a party).
 *   4. api/sag/{id}/dokumenter    (attach documents) + api/journalarknote/create
 *      (journal note).
 * Idempotency: search api/sag/search by a stable external key before create.
 * Errors: 5xx/timeout -> transient (retry); 4xx validation -> permanent.
 *
 * Not wired in this session: needs the SBSIP base URL, OAuth2 client creds
 * (from env), and per-case-type template ids. Fails hard until configured.
 */
#[EsdhConnector(
  id: 'sbsys',
  label: new TranslatableMarkup('SBSYS (via SBSIP REST)'),
)]
final class SbsysEsdhConnector extends EsdhConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function journaliseCase(AabenformsCase $case, array $documents = []): EsdhResult {
    // Production stub: never silently pretend to journalise. A live SBSYS
    // connector requires SBSIP base URL + OAuth2 creds + case-template ids.
    throw new EsdhException('SBSYS connector is not configured (needs SBSIP base URL, OAuth2 credentials, and per-case-type template ids). Use the demo connector in non-production.');
  }

}
