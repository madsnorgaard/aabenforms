<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\aabenforms_workflows\Service\PaymentService;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes payment via Nets Easy payment gateway.
 */
#[Action(
  id: 'aabenforms_process_payment',
  label: new TranslatableMarkup('Process Payment'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Processes payment transaction via Nets Easy gateway'),
  version_introduced: '2.0.0',
)]
class ProcessPaymentAction extends AabenFormsActionBase {

  use PluginFormTrait;

  /**
   * The payment service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PaymentService
   */
  protected PaymentService $paymentService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->paymentService = $container->get('aabenforms_workflows.payment_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'amount_field' => 'amount',
      'currency' => 'DKK',
      'payment_method' => 'nets_easy',
      'description_field' => 'payment_description',
      'store_payment_id_in' => 'payment_id',
      'store_status_in' => 'payment_status',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['amount_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount Field Name'),
      '#description' => $this->t('The webform field containing payment amount in øre (Danish cents). Example: 10000 = 100 DKK.'),
      '#default_value' => $this->configuration['amount_field'],
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#description' => $this->t('Payment currency code.'),
      '#options' => [
        'DKK' => $this->t('DKK (Danish Kroner)'),
        'EUR' => $this->t('EUR (Euro)'),
        'USD' => $this->t('USD (US Dollar)'),
      ],
      '#default_value' => $this->configuration['currency'],
      '#required' => TRUE,
    ];

    $form['payment_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment Method'),
      '#description' => $this->t('Preferred payment method.'),
      '#options' => [
        'nets_easy' => $this->t('Nets Easy (Card)'),
        'mobilepay' => $this->t('MobilePay'),
        'bank_transfer' => $this->t('Bank Transfer'),
      ],
      '#default_value' => $this->configuration['payment_method'],
      '#required' => TRUE,
    ];

    $form['description_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description Field Name'),
      '#description' => $this->t('Optional field containing payment description.'),
      '#default_value' => $this->configuration['description_field'],
    ];

    $form['store_payment_id_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store Payment ID In'),
      '#description' => $this->t('Field name to store the payment ID. Will be created if not exists.'),
      '#default_value' => $this->configuration['store_payment_id_in'],
      '#required' => TRUE,
    ];

    $form['store_status_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store Status In'),
      '#description' => $this->t('Field name to store the payment status.'),
      '#default_value' => $this->configuration['store_status_in'],
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $submission = $this->getSubmission($entity);
    if (!$submission) {
      $this->logger->error('ProcessPaymentAction: No webform submission found');
      return;
    }

    $data = $submission->getData();

    // Extract payment amount.
    $amount_field = $this->configuration['amount_field'];
    $amount = $data[$amount_field] ?? NULL;

    if ($amount === NULL) {
      $this->logger->error('ProcessPaymentAction: Amount field "@field" not found in submission @id', [
        '@field' => $amount_field,
        '@id' => $submission->id(),
      ]);
      return;
    }

    // Validate amount is numeric and positive.
    if (!is_numeric($amount) || $amount <= 0) {
      $this->logger->error('ProcessPaymentAction: Invalid amount @amount in submission @id', [
        '@amount' => $amount,
        '@id' => $submission->id(),
      ]);
      return;
    }

    // Extract optional description.
    $description_field = $this->configuration['description_field'];
    $description = $description_field ? ($data[$description_field] ?? 'Payment') : 'Payment';

    // Prepare payment data.
    $payment_data = [
      'amount' => (int) $amount,
      'currency' => $this->configuration['currency'],
      'order_id' => 'WF-' . $submission->id() . '-' . time(),
      'payment_method' => $this->configuration['payment_method'],
      'description' => $description,
    ];

    // Process payment.
    $result = $this->paymentService->processPayment($payment_data);

    // Store payment result in submission.
    $payment_id_field = $this->configuration['store_payment_id_in'];
    $status_field = $this->configuration['store_status_in'];

    if ($result['status'] === 'success') {
      $submission->setElementData($payment_id_field, $result['payment_id']);
      $submission->setElementData($status_field, 'completed');
      $submission->setElementData('payment_transaction_id', $result['transaction_id']);
      $submission->setElementData('payment_timestamp', $result['timestamp']);

      $this->logger->info('Payment processed successfully for submission @id: @payment_id (amount: @amount @currency)', [
        '@id' => $submission->id(),
        '@payment_id' => $result['payment_id'],
        '@amount' => $amount / 100,
        '@currency' => $result['currency'],
      ]);
    }
    else {
      $submission->setElementData($status_field, 'failed');
      $submission->setElementData('payment_error', $result['error']);

      $this->logger->warning('Payment failed for submission @id: @error', [
        '@id' => $submission->id(),
        '@error' => $result['error'],
      ]);
    }

    $submission->save();
  }

}
