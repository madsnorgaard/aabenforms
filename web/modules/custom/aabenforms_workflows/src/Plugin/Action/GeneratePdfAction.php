<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\PdfService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates PDF document from template.
 */
#[Action(
  id: 'aabenforms_generate_pdf',
  label: new TranslatableMarkup('Generate PDF Document'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Generates PDF certificate or document from template'),
  version_introduced: '2.0.0',
)]
class GeneratePdfAction extends AabenFormsActionBase {

  use PluginFormTrait;

  /**
   * The PDF service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PdfService
   */
  protected PdfService $pdfService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->pdfService = $container->get('aabenforms_workflows.pdf_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'template' => 'parking_permit',
      'filename_pattern' => '{template}_{submission_id}_{timestamp}.pdf',
      'orientation' => 'portrait',
      'size' => 'A4',
      'store_file_id_in' => 'pdf_file_id',
      'data_fields' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('PDF Template'),
      '#description' => $this->t('Select the PDF template type.'),
      '#options' => [
        'parking_permit' => $this->t('Parking Permit'),
        'marriage_certificate' => $this->t('Marriage Certificate'),
        'building_permit' => $this->t('Building Permit'),
        'generic_certificate' => $this->t('Generic Certificate'),
      ],
      '#default_value' => $this->configuration['template'],
      '#required' => TRUE,
    ];

    $form['filename_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename Pattern'),
      '#description' => $this->t('PDF filename pattern. Use {template}, {submission_id}, {timestamp} as placeholders.'),
      '#default_value' => $this->configuration['filename_pattern'],
      '#required' => TRUE,
    ];

    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Page Orientation'),
      '#options' => [
        'portrait' => $this->t('Portrait'),
        'landscape' => $this->t('Landscape'),
      ],
      '#default_value' => $this->configuration['orientation'],
    ];

    $form['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Page Size'),
      '#options' => [
        'A4' => 'A4',
        'A3' => 'A3',
        'Letter' => 'Letter',
      ],
      '#default_value' => $this->configuration['size'],
    ];

    $form['data_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data Field Mapping'),
      '#description' => $this->t('Map submission fields to PDF template variables. One per line: pdf_var:field_name'),
      '#default_value' => $this->configuration['data_fields'],
      '#rows' => 5,
    ];

    $form['store_file_id_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store File ID In'),
      '#description' => $this->t('Field name to store the generated PDF file ID.'),
      '#default_value' => $this->configuration['store_file_id_in'],
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
      $this->logger->error('GeneratePdfAction: No webform submission found');
      return;
    }

    // Prepare PDF data from submission.
    $pdf_data = $this->preparePdfData($submission);

    // Generate filename.
    $filename = $this->generateFilename($submission);

    // Prepare PDF options.
    $options = [
      'filename' => $filename,
      'orientation' => $this->configuration['orientation'],
      'size' => $this->configuration['size'],
    ];

    // Generate PDF.
    $result = $this->pdfService->generatePdf(
      $this->configuration['template'],
      $pdf_data,
      $options
    );

    // Store result in submission.
    $file_id_field = $this->configuration['store_file_id_in'];

    if ($result['status'] === 'success') {
      $submission->setElementData($file_id_field, $result['file_id']);
      $submission->setElementData('pdf_file_uri', $result['file_uri']);
      $submission->setElementData('pdf_file_url', $result['file_url']);
      $submission->setElementData('pdf_filename', $result['filename']);

      $this->logger->info('PDF generated successfully for submission @id: @filename (file_id: @file_id)', [
        '@id' => $submission->id(),
        '@filename' => $result['filename'],
        '@file_id' => $result['file_id'],
      ]);
    }
    else {
      $submission->setElementData('pdf_status', 'failed');
      $submission->setElementData('pdf_error', $result['error']);

      $this->logger->error('PDF generation failed for submission @id: @error', [
        '@id' => $submission->id(),
        '@error' => $result['error'],
      ]);
    }

    $submission->save();
  }

  /**
   * Prepares PDF data from submission.
   *
   * @param mixed $submission
   *   Webform submission.
   *
   * @return array
   *   PDF template data.
   */
  protected function preparePdfData($submission): array {
    $data = $submission->getData();
    $pdf_data = [];

    // Parse field mapping configuration.
    $field_mapping = $this->configuration['data_fields'];
    if ($field_mapping) {
      $lines = explode("\n", $field_mapping);
      foreach ($lines as $line) {
        $line = trim($line);
        if (str_contains($line, ':')) {
          [$pdf_var, $field_name] = explode(':', $line, 2);
          $pdf_var = trim($pdf_var);
          $field_name = trim($field_name);

          if (isset($data[$field_name])) {
            $pdf_data[$pdf_var] = $data[$field_name];
          }
        }
      }
    }

    // Add common fields if not already mapped.
    if (!isset($pdf_data['submission_id'])) {
      $pdf_data['submission_id'] = $submission->id();
    }
    if (!isset($pdf_data['submission_date'])) {
      $pdf_data['submission_date'] = date('Y-m-d', $submission->getCreatedTime());
    }

    return $pdf_data;
  }

  /**
   * Generates PDF filename from pattern.
   *
   * @param mixed $submission
   *   Webform submission.
   *
   * @return string
   *   Generated filename.
   */
  protected function generateFilename($submission): string {
    $pattern = $this->configuration['filename_pattern'];

    // Replace placeholders.
    $filename = str_replace('{template}', $this->configuration['template'], $pattern);
    $filename = str_replace('{submission_id}', $submission->id(), $filename);
    $filename = str_replace('{timestamp}', time(), $filename);

    // Ensure .pdf extension.
    if (!str_ends_with($filename, '.pdf')) {
      $filename .= '.pdf';
    }

    return $filename;
  }

}
