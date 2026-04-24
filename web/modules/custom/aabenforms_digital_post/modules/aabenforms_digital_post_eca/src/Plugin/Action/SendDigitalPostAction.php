<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post_eca\Plugin\Action;

use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use Drupal\aabenforms_digital_post\Service\DigitalPostSender;
use Drupal\aabenforms_workflows\Plugin\Action\AabenFormsActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

/**
 * ECA Action: Send Digital Post.
 *
 * Reads a recipient identifier from an ECA token, builds a DigitalPost
 * DTO using the configured sender, and dispatches it via the
 * aabenforms_digital_post.sender service. The send Result's
 * transactionId is written back to a token the flow can inspect.
 *
 * The plugin is intentionally thin - it does not know about SOAP, MeMo
 * XML, certificates, or test modes. All of that lives behind the
 * DigitalPostSender service boundary.
 */
#[Action(
  id: 'aabenforms_digital_post_send',
  label: new TranslatableMarkup('Send Digital Post'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Send a SF1601 Digital Post to a citizen (CPR) or company (CVR) via aabenforms_digital_post.'),
  version_introduced: '1.0.0',
)]
class SendDigitalPostAction extends AabenFormsActionBase {

  protected DigitalPostSender $sender;
  protected ConfigFactoryInterface $configFactory;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sender = $container->get('aabenforms_digital_post.sender');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  public function defaultConfiguration(): array {
    return [
      'recipient_token' => '',
      'recipient_type' => 'cpr',
      'sender_cvr_token' => '',
      'subject_template' => 'Afgørelse',
      'body_template' => '<p>Se vedlagte bilag.</p>',
      'type' => DigitalPost::TYPE_DIGITAL_POST,
      'result_token' => 'digital_post_result',
    ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['recipient_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient token'),
      '#description' => $this->t('ECA token resolving to the recipient identifier. Examples: [citizen_session:cpr], [webform_submission:values:cpr:raw]'),
      '#default_value' => $this->configuration['recipient_token'],
      '#required' => TRUE,
    ];
    $form['recipient_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Recipient type'),
      '#options' => [
        Recipient::TYPE_CPR => $this->t('CPR (citizen)'),
        Recipient::TYPE_CVR => $this->t('CVR (company)'),
      ],
      '#default_value' => $this->configuration['recipient_type'],
      '#required' => TRUE,
    ];
    $form['sender_cvr_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender CVR (override)'),
      '#description' => $this->t('Leave empty to use the module default from aabenforms_digital_post.settings.'),
      '#default_value' => $this->configuration['sender_cvr_token'],
    ];
    $form['subject_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Literal subject or [token]-containing template.'),
      '#default_value' => $this->configuration['subject_template'],
      '#required' => TRUE,
    ];
    $form['body_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body (HTML allowed for Digital Post)'),
      '#default_value' => $this->configuration['body_template'],
      '#required' => TRUE,
      '#rows' => 4,
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Delivery type'),
      '#options' => [
        DigitalPost::TYPE_DIGITAL_POST => $this->t('Digital Post only'),
        DigitalPost::TYPE_AUTOMATISK_VALG => $this->t('Automatisk Valg (digital with fjernprint fallback)'),
        DigitalPost::TYPE_FYSISK_POST => $this->t('Fysisk Post (physical mail only)'),
        DigitalPost::TYPE_NEM_SMS => $this->t('NemSMS'),
      ],
      '#default_value' => $this->configuration['type'],
    ];
    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token that receives the typed Result. Keys: success (bool), transaction_id, reason_code, message.'),
      '#default_value' => $this->configuration['result_token'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach (['recipient_token', 'recipient_type', 'sender_cvr_token', 'subject_template', 'body_template', 'type', 'result_token'] as $key) {
      $this->configuration[$key] = (string) $form_state->getValue($key);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  public function execute(): void {
    $recipientRaw = $this->resolveRecipient();
    $recipientType = (string) $this->configuration['recipient_type'];

    if ($recipientRaw === '') {
      $this->recordStep(
        label: 'Digital Post skipped',
        description: 'Recipient not resolved. Common in demo mode without a real MitID session or when the webform field is empty.',
        status: 'skipped',
      );
      $this->log('Digital Post skipped: recipient empty');
      $this->setResultToken(success: FALSE, transactionId: '', reasonCode: 'RECIPIENT_EMPTY', message: 'Recipient not resolved.');
      return;
    }

    try {
      $recipient = $recipientType === Recipient::TYPE_CVR
        ? Recipient::cvr($recipientRaw)
        : Recipient::cpr($recipientRaw);

      $senderCvrOverride = $this->getTokenValue((string) $this->configuration['sender_cvr_token'], '');
      $sender = $senderCvrOverride !== ''
        ? new Sender(cvr: $senderCvrOverride)
        : Sender::fromConfig($this->configFactory);

      $post = new DigitalPost(
        recipient: $recipient,
        sender: $sender,
        subject: $this->renderTemplate((string) $this->configuration['subject_template']),
        body: $this->renderTemplate((string) $this->configuration['body_template']),
        type: (string) $this->configuration['type'],
      );

      $result = $this->sender->send($post);

      $this->recordStep(
        label: $result->isSuccess() ? 'Digital Post sent' : 'Digital Post failed',
        description: sprintf('%s: %s', $this->sender->testMode(), $result->message),
        status: $result->isSuccess() ? 'completed' : 'failed',
      );
      $this->setResultToken(
        success: $result->isSuccess(),
        transactionId: $result->transactionId,
        reasonCode: $result->reasonCode,
        message: $result->message,
      );
    }
    catch (Throwable $e) {
      $this->handleError($e, 'SendDigitalPostAction');
      $this->setResultToken(
        success: FALSE,
        transactionId: '',
        reasonCode: 'VALIDATION',
        message: $e->getMessage(),
      );
      $this->recordStep(
        label: 'Digital Post failed',
        description: 'Validation error: ' . $e->getMessage(),
        status: 'failed',
      );
    }
  }

  /**
   * Resolve the recipient identifier.
   *
   * Strategy, in order:
   * 1. Parse the recipient_token config as `[webform_submission:values:FIELD:raw]`
   *    and read the field directly off the entity exposed by the triggering
   *    ECA event. This handles the common webform-submit case without
   *    depending on ECA's key-based token data registry.
   * 2. Fall back to tokenService->getTokenData() with the raw token string
   *    as a key. Handles session-backed tokens populated by earlier actions
   *    (e.g. MitIdValidateAction writing '[citizen_session:cpr]' semantics).
   * 3. Return '' if neither resolves.
   */
  private function resolveRecipient(): string {
    $token = (string) $this->configuration['recipient_token'];
    if ($token === '') {
      return '';
    }

    // Strategy 1: parse [webform_submission:values:FIELD:raw] and read
    // directly from the event's entity.
    if (preg_match('/^\[webform_submission:values:([a-zA-Z0-9_]+)(?::raw)?\]$/', $token, $m)) {
      $entity = $this->eventEntity();
      if ($entity !== NULL && method_exists($entity, 'getElementData')) {
        $value = $entity->getElementData($m[1]);
        if (is_string($value) && $value !== '') {
          return $value;
        }
      }
    }

    // Strategy 2: ECA token data registry lookup.
    $key = trim($token, '[]');
    $value = $this->tokenService->getTokenData($key);
    if (is_string($value)) {
      return $value;
    }
    if (is_array($value) && isset($value['cpr']) && is_string($value['cpr'])) {
      return $value['cpr'];
    }

    return '';
  }

  /**
   * Extract the content entity from the triggering ECA event, if any.
   */
  private function eventEntity(): ?object {
    if (!isset($this->event)) {
      return NULL;
    }
    foreach (['getEntity', 'getContext'] as $method) {
      if (method_exists($this->event, $method)) {
        $result = $this->event->{$method}();
        if (is_object($result) && method_exists($result, 'getElementData')) {
          return $result;
        }
      }
    }
    return NULL;
  }

  /**
   * Replace [token] placeholders in a template. Only subject + body templates
   * go through this; literal strings pass through unchanged.
   */
  private function renderTemplate(string $template): string {
    if ($template === '' || !str_contains($template, '[')) {
      return $template;
    }
    return preg_replace_callback(
      '/\[([^\]]+)\]/',
      function ($m) {
        $value = $this->tokenService->getTokenData($m[1]);
        if (is_string($value) || is_numeric($value)) {
          return (string) $value;
        }
        return $m[0];
      },
      $template,
    );
  }

  private function setResultToken(bool $success, string $transactionId, ?string $reasonCode, string $message): void {
    $name = (string) $this->configuration['result_token'];
    if ($name === '') {
      return;
    }
    $this->setTokenValue($name, [
      'success' => $success,
      'transaction_id' => $transactionId,
      'reason_code' => $reasonCode,
      'message' => $message,
    ]);
  }

}
