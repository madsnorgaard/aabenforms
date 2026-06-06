<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\OrgChartServiceInterface;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Resolve a manager email from an employee identifier.
 *
 * Calls aabenforms_workflows.org_chart (StubOrgChartService by default)
 * with an employee id read from a configured ECA token. Writes the
 * resolved email into a result token so SendApprovalEmailAction can
 * pick it up. When the org-chart can't resolve the employee, the
 * fallback token (typically the form-supplied manager_email field) is
 * used so existing approval flows degrade gracefully.
 */
#[Action(
  id: 'aabenforms_manager_lookup',
  label: new TranslatableMarkup('Resolve manager email'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Looks up a manager email from an employee identifier via the OrgChartService, with form-field fallback.'),
  version_introduced: '2.1.0',
)]
class ManagerLookupAction extends AabenFormsActionBase {

  /**
   * The org-chart service.
   *
   * @var \Drupal\aabenforms_workflows\Service\OrgChartServiceInterface
   */
  protected OrgChartServiceInterface $orgChart;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->orgChart = $container->get('aabenforms_workflows.org_chart');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'employee_id_token' => '[webform_submission:values:employee_id:raw]',
      'fallback_email_token' => '[webform_submission:values:manager_email:raw]',
      'result_token' => 'manager_email',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['employee_id_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Employee ID token'),
      '#description' => $this->t('ECA token resolving to the employee identifier. Examples: <code>[webform_submission:values:employee_id:raw]</code>, <code>[citizen_session:cpr]</code>.'),
      '#default_value' => $this->configuration['employee_id_token'],
      '#required' => TRUE,
    ];
    $form['fallback_email_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback manager-email token'),
      '#description' => $this->t('Token consulted when the org-chart cannot resolve the manager. Typically the form-field email.'),
      '#default_value' => $this->configuration['fallback_email_token'],
    ];
    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token that receives the resolved manager email; downstream actions read this name.'),
      '#default_value' => $this->configuration['result_token'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach (['employee_id_token', 'fallback_email_token', 'result_token'] as $key) {
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
      $fallback = $this->getTokenValue((string) $this->configuration['fallback_email_token'], '');

      $resolved = $this->orgChart->findManagerEmail($employee_id, $fallback);
      $this->setTokenValue((string) $this->configuration['result_token'], $resolved);
      // Emit a companion status token so a following audit node logs the real
      // outcome instead of a hardcoded success.
      $resultToken = (string) $this->configuration['result_token'];
      if ($resultToken !== '') {
        $this->setTokenValue($resultToken . '_status', $resolved !== '' ? 'found' : 'not_found');
      }

      $this->recordStep(
        label: 'Manager email resolved',
        description: $resolved !== '' ? sprintf('Resolved manager email for employee_id=%s', $employee_id !== '' ? substr($employee_id, 0, 4) . '****' : 'unknown') : 'No manager email available; downstream approval may fail.',
        status: $resolved !== '' ? 'completed' : 'skipped',
      );
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'ManagerLookupAction');
    }
  }

}
