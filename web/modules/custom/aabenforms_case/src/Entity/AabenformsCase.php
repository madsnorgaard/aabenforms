<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Entity;

use Drupal\aabenforms_case\AabenformsCaseInterface;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\aabenforms_case\AabenformsCaseAccessControlHandler;
use Drupal\aabenforms_case\AabenformsCaseListBuilder;

/**
 * Defines the ÅbenForms Case (sag) entity.
 *
 * A case is the persistent record of a piece of municipal casework. It is
 * opened from a webform submission (via the aabenforms_case_open ECA action),
 * carries a lawful lifecycle status and a deadline (frist) clock, and is
 * revisionable so that every status transition leaves an auditable revision
 * with a log message (who/when/why) - the compliance backbone.
 *
 * Privacy by design: the case references its submission and never copies the
 * raw CPR. The CPR stays encrypted at rest on the submission
 * (aabenforms_core_webform_submission_presave + CprAccess).
 */
#[ContentEntityType(
  id: 'aabenforms_case',
  label: new TranslatableMarkup('Case'),
  label_collection: new TranslatableMarkup('Cases'),
  label_singular: new TranslatableMarkup('case'),
  label_plural: new TranslatableMarkup('cases'),
  label_count: [
    'singular' => '@count case',
    'plural' => '@count cases',
  ],
  handlers: [
    'list_builder' => AabenformsCaseListBuilder::class,
    'access' => AabenformsCaseAccessControlHandler::class,
    'form' => [
      'default' => ContentEntityForm::class,
      'edit' => ContentEntityForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  base_table: 'aabenforms_case',
  revision_table: 'aabenforms_case_revision',
  admin_permission: 'administer aabenforms_case',
  entity_keys: [
    'id' => 'id',
    'revision' => 'vid',
    'uuid' => 'uuid',
    'label' => 'title',
  ],
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
  links: [
    'collection' => '/admin/aabenforms/cases',
    'edit-form' => '/admin/aabenforms/cases/{aabenforms_case}/edit',
    'delete-form' => '/admin/aabenforms/cases/{aabenforms_case}/delete',
  ],
)]
class AabenformsCase extends RevisionableContentEntityBase implements AabenformsCaseInterface {

  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getCaseType(): string {
    return (string) $this->get('case_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status): static {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFristDue(): ?int {
    $value = $this->get('frist_due')->value;
    return $value === NULL ? NULL : (int) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getModtagelsesdato(): ?int {
    $value = $this->get('modtagelsesdato')->value;
    return $value === NULL ? NULL : (int) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionId(): ?string {
    $target = $this->get('submission_ref')->target_id;
    return $target === NULL ? NULL : (string) $target;
  }

  /**
   * {@inheritdoc}
   *
   * Enforces the lawful lifecycle at the storage layer so no save path - not
   * the admin edit form, JSON:API, Views Bulk Operations, nor a stray
   * programmatic write - can reach a state the ECA actions would refuse. A
   * closed case is immutable, an illegal transition throws, and every mutation
   * is forced to a new audited revision (the compliance backbone must never
   * silently overwrite history via a default entity-form save).
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Nothing to guard on the founding save.
    if ($this->isNew()) {
      return;
    }

    $original = $this->originalStatus();
    if ($original !== NULL) {
      // A closed case is terminal: no field on it may change.
      if ($original === 'lukket') {
        throw new EntityStorageException(sprintf('Sag %s er lukket og kan ikke ændres.', (string) $this->id()));
      }
      $new = $this->getStatus();
      if ($new !== $original && !in_array($new, self::allowedTransitions()[$original] ?? [], TRUE)) {
        throw new EntityStorageException(sprintf('Ulovlig statusovergang på sag %s: %s → %s.', (string) $this->id(), $original, $new));
      }
    }

    // Every change to a case is an audited revision. ECA actions set a
    // meaningful log message; a bare entity-form save gets a default one.
    $this->setNewRevision(TRUE);
    if ((string) ($this->getRevisionLogMessage() ?? '') === '') {
      $this->setRevisionLogMessage('Sag opdateret.');
    }
    $this->setRevisionCreationTime(\Drupal::time()->getRequestTime());
    $this->setRevisionUserId((int) \Drupal::currentUser()->id());
  }

  /**
   * Reads the persisted status from before this save, if available.
   *
   * @return string|null
   *   The original status, or NULL when there is no loaded original.
   */
  protected function originalStatus(): ?string {
    $original = NULL;
    if (method_exists($this, 'getOriginal')) {
      // Drupal >= 11.2.
      $original = $this->getOriginal();
    }
    elseif (isset($this->original)) {
      // @phpstan-ignore-line - deprecated property fallback for Drupal < 11.2.
      $original = $this->original;
    }
    return $original instanceof AabenformsCaseInterface ? $original->getStatus() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['case_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Case type'))
      ->setDescription(new TranslatableMarkup('The casework area, e.g. underretning, friplads.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('Lawful case lifecycle state.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue('modtaget')
      ->setSetting('allowed_values', self::statusOptions())
      ->setDisplayConfigurable('view', TRUE)
      // Status is only ever changed through the lawful, audited transition
      // actions (aabenforms_case_transition / _decide / _appeal / _sf2900),
      // never freely via the entity form - preSave() enforces this.
      ->setDisplayConfigurable('form', FALSE);

    $fields['submission_ref'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Webform submission'))
      ->setDescription(new TranslatableMarkup('The submission this case was opened from. The raw CPR stays encrypted on the submission and is never copied here.'))
      ->setSetting('target_type', 'webform_submission')
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['kle_emne'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('KLE-emne (UUID)'))
      ->setDescription(new TranslatableMarkup('Classification (SF1510 Klassifikation) UUID used for journalising and SF2900 routing.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['handlekommune'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Handling municipality'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE);

    $fields['betalingskommune'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Paying municipality'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE);

    $fields['modtagelsesdato'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Receipt date'))
      ->setDescription(new TranslatableMarkup('When the case was received - the start of the RSL §3 deadline clock.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['frist_due'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Deadline (frist) due'))
      ->setDescription(new TranslatableMarkup('Computed by the FristClock from the receipt date and the per-area deadline.'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['partshoering_state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Partshøring state'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('ikke_paakraevet')
      ->setSetting('allowed_values', [
        'ikke_paakraevet' => 'Ikke påkrævet',
        'afventer' => 'Afventer svar',
        'afsluttet' => 'Afsluttet',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['afgoerelse_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Decision type'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', [
        'medhold' => 'Medhold',
        'delvist' => 'Delvist medhold',
        'afslag' => 'Afslag',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['klagefrist'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Appeal deadline (klagefrist)'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['journal_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Journal reference'))
      ->setDescription(new TranslatableMarkup('Reference returned by the Sags- og Dokumentindeks (SF1470) registration.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Assigned caseworker'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    // Revision log fields (revision_user, revision_created, revision_log_message).
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    return $fields;
  }

  /**
   * The allowed case lifecycle states.
   *
   * @return array<string, string>
   *   Machine name => human label.
   */
  public static function statusOptions(): array {
    return [
      'modtaget' => 'Modtaget',
      'oplyst' => 'Oplyst',
      'partshoering' => 'Partshøring',
      'afgoerelse' => 'Afgørelse',
      'paaklaget' => 'Påklaget',
      'lukket' => 'Lukket',
    ];
  }

  /**
   * The lawful forward transitions allowed from each state.
   *
   * @return array<string, string[]>
   *   Source state => list of allowed target states.
   */
  public static function allowedTransitions(): array {
    return [
      'modtaget' => ['oplyst', 'lukket'],
      'oplyst' => ['partshoering', 'afgoerelse', 'lukket'],
      'partshoering' => ['afgoerelse', 'lukket'],
      'afgoerelse' => ['paaklaget', 'lukket'],
      'paaklaget' => ['afgoerelse', 'lukket'],
      'lukket' => [],
    ];
  }

}
