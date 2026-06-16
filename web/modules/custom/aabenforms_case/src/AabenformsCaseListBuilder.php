<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_case\Service\FristClock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin list builder for cases at /admin/aabenforms/cases.
 */
class AabenformsCaseListBuilder extends EntityListBuilder {

  /**
   * Constructs the list builder.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    protected DateFormatterInterface $dateFormatter,
    protected TimeInterface $time,
    protected FristClock $fristClock,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('aabenforms_case.frist_clock'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = new TranslatableMarkup('ID');
    $header['title'] = new TranslatableMarkup('Title');
    $header['case_type'] = new TranslatableMarkup('Type');
    $header['status'] = new TranslatableMarkup('Status');
    $header['frist'] = new TranslatableMarkup('Deadline');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof AabenformsCase);
    $statuses = AabenformsCase::statusOptions();
    $row['id'] = $entity->id();
    $row['title'] = $entity->label();
    $row['case_type'] = $entity->getCaseType();
    $row['status'] = $statuses[$entity->getStatus()] ?? $entity->getStatus();

    $due = $entity->getFristDue();
    if ($due === NULL) {
      $row['frist'] = new TranslatableMarkup('—');
    }
    else {
      $state = $this->fristClock->computeState($due, $this->time->getRequestTime());
      $row['frist'] = new TranslatableMarkup('@date (@state)', [
        '@date' => $this->dateFormatter->format($due, 'short'),
        '@state' => $state,
      ]);
    }

    return $row + parent::buildRow($entity);
  }

}
