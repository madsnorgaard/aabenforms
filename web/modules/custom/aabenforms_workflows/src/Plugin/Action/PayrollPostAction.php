<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\OrgChartServiceInterface;
use Drupal\aabenforms_workflows\Service\PayrollService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Forward an approved claim to payroll.
 *
 * Reads employee identifier, claim type, and amount from configured
 * tokens. Forwards via aabenforms_workflows.payroll which writes to
 * {aabenforms_payroll_log} (fake_db default). The action is transport-
 * agnostic - swapping in a real payroll API is a service decoration
 * concern, not a plugin change.
 */
#[Action(
  id: 'aabenforms_payroll_post',
  label: new TranslatableMarkup('Forward to payroll'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('POSTs an approved claim to the payroll service. Default fake_db transport records the payload to {aabenforms_payroll_log}.'),
  version_introduced: '2.1.0',
)]
class PayrollPostAction extends AabenFormsActionBase {

  /**
   * The payroll service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PayrollService
   */
  protected PayrollService $payroll;

  /**
   * The org-chart directory, used for the per-employee policy limit.
   *
   * @var \Drupal\aabenforms_workflows\Service\OrgChartServiceInterface
   */
  protected OrgChartServiceInterface $orgChart;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->payroll = $container->get('aabenforms_workflows.payroll');
    $instance->orgChart = $container->get('aabenforms_workflows.org_chart');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'employee_id_token' => '[webform_submission:values:employee_id:raw]',
      'amount_token' => '[webform_submission:values:amount:raw]',
      'claim_type' => 'mileage',
      'result_token' => 'payroll_result',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['employee_id_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Employee ID token'),
      '#description' => $this->t('ECA token resolving to the employee identifier.'),
      '#default_value' => $this->configuration['employee_id_token'],
      '#required' => TRUE,
    ];
    $form['amount_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount token'),
      '#description' => $this->t('Token resolving to the claim amount in DKK. Decimals allowed; converted to øre internally.'),
      '#default_value' => $this->configuration['amount_token'],
      '#required' => TRUE,
    ];
    $form['claim_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Claim type'),
      '#description' => $this->t('Free-form label persisted in the payroll log: mileage, expense, phone_subsidy, ...'),
      '#default_value' => $this->configuration['claim_type'],
    ];
    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token receiving the typed result (status, reason_code, transaction_id).'),
      '#default_value' => $this->configuration['result_token'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach (['employee_id_token', 'amount_token', 'claim_type', 'result_token'] as $key) {
      $this->configuration[$key] = (string) $form_state->getValue($key);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $entity = NULL): void {
    try {
      $employee_id = $this->getTokenValue((string) $this->configuration['employee_id_token'], '');
      $amount_raw = $this->getTokenValue((string) $this->configuration['amount_token'], '0');
      $amount_cents = (int) round(((float) str_replace(',', '.', $amount_raw)) * 100);
      $claim_type = (string) ($this->configuration['claim_type'] ?? 'unspecified');

      // Enforce the per-employee policy limit before forwarding money.
      $limit_cents = $this->orgChart->tierLimitCents($employee_id);
      if ($limit_cents > 0 && $amount_cents > $limit_cents) {
        $name = (string) $this->configuration['result_token'];
        if ($name !== '') {
          $this->setTokenValue($name, [
            'status' => PayrollService::STATUS_FAILURE,
            'transaction_id' => '',
            'reason_code' => 'AMOUNT_EXCEEDS_POLICY',
            'message' => 'Beloeb overstiger den tilladte graense for medarbejderen.',
          ]);
        }
        $this->recordStep(
          label: 'Payroll forwarding blocked',
          description: sprintf('%s: beloebet (%.2f kr.) overstiger graensen (%.2f kr.) og blev ikke sendt til loen.', $claim_type, $amount_cents / 100, $limit_cents / 100),
          status: 'failed',
        );
        return;
      }

      $result = $this->payroll->forward($employee_id, $claim_type, $amount_cents, [
        'submission_id' => $this->getSubmission($entity)?->id(),
      ]);

      $name = (string) $this->configuration['result_token'];
      if ($name !== '') {
        $this->setTokenValue($name, [
          'status' => $result['status'],
          'transaction_id' => $result['transaction_id'],
          'reason_code' => $result['reason_code'],
          'message' => $result['message'],
        ]);
      }

      $is_success = $result['status'] === PayrollService::STATUS_SUCCESS;
      $this->recordStep(
        label: $is_success ? 'Payroll forwarded' : 'Payroll forwarding failed',
        description: sprintf('%s: %s', $claim_type, $result['message']),
        status: $is_success ? 'completed' : 'failed',
      );
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'PayrollPostAction');
    }
  }

}
