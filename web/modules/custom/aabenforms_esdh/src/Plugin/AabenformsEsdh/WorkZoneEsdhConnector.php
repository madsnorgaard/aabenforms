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
 * KMD WorkZone connector - production stub.
 *
 * Transport: WorkZone OData REST (WCF Data Services), service root
 * http://<workzone>/odata/<EntitySet>. Auth: customer Azure AD / OAuth2, the
 * integration registered in WorkZone Configurator.
 *
 * Critical entity-naming gotcha: in WorkZone a CASE is a `File` and a DOCUMENT
 * is a `Record`. A live implementation would:
 *   1. POST /odata/File     (create the case; map case_type/KLE to File fields).
 *   2. POST /odata/Record   (create a Record on that File to journalise a doc),
 *      then upload the bytes.
 *   3. POST /odata/Contacts (relate the citizen party).
 * $filter/$expand support idempotent "does this File already exist" lookups.
 *
 * Not wired in this session: needs the OData service root + Azure AD OAuth2
 * client. Fails hard until configured.
 */
#[EsdhConnector(
  id: 'workzone',
  label: new TranslatableMarkup('KMD WorkZone (OData)'),
)]
final class WorkZoneEsdhConnector extends EsdhConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function journaliseCase(AabenformsCase $case, array $documents = []): EsdhResult {
    throw new EsdhException('WorkZone connector is not configured (needs the OData service root and an Azure AD OAuth2 client). Use the demo connector in non-production.');
  }

}
