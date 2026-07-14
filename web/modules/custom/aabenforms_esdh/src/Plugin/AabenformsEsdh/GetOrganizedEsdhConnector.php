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
 * GetOrganized connector (SharePoint-based ESDH; KBH, Aarhus, ATP) - stub.
 *
 * Transport: GetOrganized REST under /_goapi/ (the same surface the
 * itk-dev/getorganized-api-client-php library and os2forms_get_organized
 * target). Auth: username/password to the web-app URL. A live implementation
 * would:
 *   1. POST /_goapi/Cases/FindByCaseProperties  (find the citizen's case by
 *      CPR + case type) or POST /_goapi/Cases/ (create a case / subcase).
 *   2. POST /_goapi/Documents/AddToDocumentLibrary  (upload/journalise a doc).
 *   3. POST /_goapi/Documents/Finalize/ByDocumentId (lock/finalise) and
 *      /_goapi/Documents/RelateDocuments (relate).
 * This is the best-documented Danish ESDH surface and the recommended first
 * live target.
 *
 * Not wired in this session: needs the GetOrganized base URL + service
 * credentials. Fails hard until configured.
 */
#[EsdhConnector(
  id: 'getorganized',
  label: new TranslatableMarkup('GetOrganized (/_goapi REST)'),
)]
final class GetOrganizedEsdhConnector extends EsdhConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function journaliseCase(AabenformsCase $case, array $documents = []): EsdhResult {
    throw new EsdhException('GetOrganized connector is not configured (needs the /_goapi base URL and service credentials). Use the demo connector in non-production.');
  }

}
