<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
#[AabenformsDashboardSection(id: 'workflows', weight: -50)]
class WorkflowsSection extends AabenformsDashboardSectionBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly BpmnTemplateManager $templateManager,
    protected readonly WorkflowTemplateInstantiator $instantiator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('aabenforms_workflows.bpmn_template_manager'),
      $container->get('aabenforms_workflows.template_instantiator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Workflow Templates');
  }

  /**
   * {@inheritdoc}
   */
  public function getHeroMetric(): ?array {
    $instances = $this->instantiator->getInstances();
    return [
      'value' => count($instances),
      'label' => $this->t('active workflows'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryMetrics(): array {
    return [
      [
        'label' => $this->t('Templates available'),
        'value' => count($this->templateManager->getAvailableTemplates()),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMainLink(): array {
    return [
      'label' => $this->t('Browse templates'),
      'url' => Url::fromRoute('aabenforms_workflows.template_browser'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return ['config:eca_list', 'aabenforms_workflows:templates'];
  }

}
