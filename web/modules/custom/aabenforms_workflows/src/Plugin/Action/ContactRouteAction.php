<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Route a contact-form submission.
 *
 * Sends the message to a department inbox, acknowledges the sender, and
 * records an audit entry. Backs the contact_form template, which was
 * previously a wizard blueprint with no active flow.
 */
#[Action(
  id: 'aabenforms_contact_route',
  label: new TranslatableMarkup('Route contact submission'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Routes a contact-form message to a department inbox and acknowledges the sender.'),
  version_introduced: '2.1.0',
)]
class ContactRouteAction extends AabenFormsActionBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected AuditLogger $auditLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->auditLogger = $container->get('aabenforms_core.audit_logger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'inbox_email' => 'kontakt@example.dk',
      'name_token' => '[webform_submission:values:name:raw]',
      'email_token' => '[webform_submission:values:email:raw]',
      'message_token' => '[webform_submission:values:message:raw]',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['inbox_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department inbox'),
      '#default_value' => $this->configuration['inbox_email'],
      '#required' => TRUE,
    ];
    $form['name_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender name token'),
      '#default_value' => $this->configuration['name_token'],
    ];
    $form['email_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender email token'),
      '#default_value' => $this->configuration['email_token'],
      '#required' => TRUE,
    ];
    $form['message_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message token'),
      '#default_value' => $this->configuration['message_token'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach (['inbox_email', 'name_token', 'email_token', 'message_token'] as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $name = $this->getTokenValue((string) $this->configuration['name_token'], '');
    $email = $this->getTokenValue((string) $this->configuration['email_token'], '');
    $message = $this->getTokenValue((string) $this->configuration['message_token'], '');
    $inbox = (string) $this->configuration['inbox_email'];
    $langcode = LanguageInterface::LANGCODE_DEFAULT;

    $params = [
      'sender_name' => $name !== '' ? $name : $this->t('borger'),
      'sender_email' => $email,
      'message' => $message,
    ];

    $this->mailManager->mail('aabenforms_workflows', 'contact_inbox', $inbox, $langcode, $params);
    $this->recordStep('Contact routed', 'Henvendelsen blev sendt til afdelingens postkasse.', 'completed');

    if ($email !== '') {
      $this->mailManager->mail('aabenforms_workflows', 'contact_acknowledgement', $email, $langcode, $params);
      $this->recordStep('Acknowledgement sent', 'Kvittering sendt til afsenderen.', 'completed');
    }
    else {
      $this->recordStep('Acknowledgement skipped', 'Ingen e-mailadresse angivet.', 'skipped');
    }

    try {
      $this->auditLogger->log('contact_submitted', 'system', 'Contact form routed', 'success', []);
    }
    catch (\Exception $e) {
      $this->handleError($e, 'Recording contact audit entry');
    }
  }

}
