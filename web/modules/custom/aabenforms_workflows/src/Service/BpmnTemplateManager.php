<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for loading and managing BPMN workflow templates.
 *
 * This service provides functionality to discover, load, validate, and manage
 * BPMN 2.0 workflow templates for Danish municipal use cases.
 */
class BpmnTemplateManager {

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleList;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a BpmnTemplateManager service.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ModuleExtensionList $module_list,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->moduleList = $module_list;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Gets all available BPMN templates.
   *
   * Scans the workflows directory and returns metadata for all discovered
   * BPMN template files.
   *
   * @return array
   *   Array of template metadata, keyed by template ID.
   *   Each entry contains:
   *   - id: Template identifier.
   *   - name: Human-readable template name.
   *   - file: Absolute path to BPMN file.
   *   - description: Template description.
   *   - category: Template category (e.g., 'municipal', 'citizen_service').
   */
  public function getAvailableTemplates(): array {
    $templates = [];
    $template_dir = $this->getTemplateDirectory();

    if (!is_dir($template_dir)) {
      $this->logger->warning('BPMN template directory not found: @dir', [
        '@dir' => $template_dir,
      ]);
      return $templates;
    }

    foreach (glob($template_dir . '/*.bpmn') as $file) {
      $template_id = basename($file, '.bpmn');
      $metadata = $this->extractTemplateMetadata($file);

      if ($metadata) {
        $templates[$template_id] = [
          'id' => $template_id,
          'name' => $metadata['name'] ?? $template_id,
          'file' => $file,
          'description' => $metadata['description'] ?? '',
          'category' => $metadata['category'] ?? 'other',
        ];
      }
    }

    return $templates;
  }

  /**
   * Loads a specific BPMN template.
   *
   * @param string $template_id
   *   The template identifier.
   * @param bool $as_string
   *   If TRUE, returns XML string. If FALSE, returns SimpleXMLElement.
   *
   * @return \SimpleXMLElement|string|null
   *   The parsed BPMN XML or NULL if not found/invalid.
   */
  public function loadTemplate(string $template_id, bool $as_string = FALSE): \SimpleXMLElement|string|null {
    $file = $this->getTemplatePath($template_id);

    if (!file_exists($file)) {
      $this->logger->error('BPMN template not found: @id', [
        '@id' => $template_id,
      ]);
      return NULL;
    }

    try {
      if ($as_string) {
        return file_get_contents($file);
      }

      $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOENT);
      return $xml !== FALSE ? $xml : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load BPMN template @id: @message', [
        '@id' => $template_id,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Validation errors storage.
   *
   * @var array
   */
  protected array $validationErrors = [];

  /**
   * Validates a BPMN template against schema.
   *
   * @param string $template_id_or_xml
   *   The template identifier or raw XML string.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateTemplate(string $template_id_or_xml): bool {
    // Determine if input is XML or template ID.
    $is_xml = str_starts_with(trim($template_id_or_xml), '<?xml');

    if ($is_xml) {
      $xml = $this->parseXmlString($template_id_or_xml);
    }
    else {
      $xml = $this->loadTemplate($template_id_or_xml);
    }

    if (!$xml) {
      $this->validationErrors = ['Failed to load or parse template'];
      return FALSE;
    }

    $this->validationErrors = [];

    // Check for required BPMN 2.0 namespace.
    $namespaces = $xml->getNamespaces(TRUE);
    if (!isset($namespaces['bpmn']) && !isset($namespaces['bpmn2'])) {
      $this->validationErrors[] = 'Missing BPMN 2.0 namespace';
    }

    // Register namespace for XPath queries.
    $ns = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;
    if ($ns) {
      $xml->registerXPathNamespace('bpmn', $ns);
    }

    // Check for at least one process.
    $processes = $xml->xpath('//bpmn:process');
    if (empty($processes)) {
      $this->validationErrors[] = 'No BPMN process found';
    }

    // Check for start event.
    $start_events = $xml->xpath('//bpmn:startEvent');
    if (empty($start_events)) {
      $this->validationErrors[] = 'No start event found - every workflow must have a start point';
    }

    // Check for end event.
    $end_events = $xml->xpath('//bpmn:endEvent');
    if (empty($end_events)) {
      $this->validationErrors[] = 'No end event found - every workflow must have an end point';
    }

    // Validate sequence flows reference valid elements.
    $sequence_flows = $xml->xpath('//bpmn:sequenceFlow');
    if ($sequence_flows) {
      $all_elements = $xml->xpath('//*[@id]');
      $element_ids = [];
      foreach ($all_elements as $element) {
        $element_ids[] = (string) $element['id'];
      }

      foreach ($sequence_flows as $flow) {
        $source_ref = (string) $flow['sourceRef'];
        $target_ref = (string) $flow['targetRef'];

        if (!in_array($source_ref, $element_ids)) {
          $this->validationErrors[] = "Sequence flow references invalid source: $source_ref";
        }

        if (!in_array($target_ref, $element_ids)) {
          $this->validationErrors[] = "Sequence flow references invalid target: $target_ref";
        }
      }
    }

    return empty($this->validationErrors);
  }

  /**
   * Gets validation errors from the last validation.
   *
   * @return array
   *   Array of validation error messages.
   */
  public function getValidationErrors(): array {
    return $this->validationErrors;
  }

  /**
   * Parses an XML string into a SimpleXMLElement.
   *
   * @param string $xml_string
   *   The XML string.
   *
   * @return \SimpleXMLElement|null
   *   The parsed XML or NULL on failure.
   */
  protected function parseXmlString(string $xml_string): ?\SimpleXMLElement {
    try {
      $xml = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOENT);
      return $xml !== FALSE ? $xml : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to parse XML string: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Imports a BPMN template from uploaded file.
   *
   * @param string $file_path
   *   Path to the uploaded BPMN file.
   * @param string $template_id
   *   Desired template identifier.
   *
   * @return bool
   *   TRUE if import succeeded, FALSE otherwise.
   */
  public function importTemplate(string $file_path, string $template_id): bool {
    // Validate the uploaded file is valid XML.
    try {
      $xml = simplexml_load_file($file_path, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOENT);
      if ($xml === FALSE) {
        $this->logger->error('Invalid XML in uploaded BPMN file');
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to parse uploaded BPMN file: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }

    // Copy to templates directory.
    $destination = $this->getTemplatePath($template_id);
    try {
      $this->fileSystem->copy($file_path, $destination, FileSystemInterface::EXISTS_REPLACE);
      $this->logger->info('Imported BPMN template: @id', ['@id' => $template_id]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to import BPMN template: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Exports a BPMN template.
   *
   * @param string $template_id
   *   The template identifier.
   *
   * @return string|null
   *   The template file path or NULL if not found.
   */
  public function exportTemplate(string $template_id): ?string {
    $file = $this->getTemplatePath($template_id);
    return file_exists($file) ? $file : NULL;
  }

  /**
   * Deletes a BPMN template.
   *
   * @param string $template_id
   *   The template identifier.
   *
   * @return bool
   *   TRUE if deletion succeeded, FALSE otherwise.
   */
  public function deleteTemplate(string $template_id): bool {
    $file = $this->getTemplatePath($template_id);

    if (!file_exists($file)) {
      return FALSE;
    }

    try {
      $this->fileSystem->delete($file);
      $this->logger->info('Deleted BPMN template: @id', ['@id' => $template_id]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to delete BPMN template: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets the template directory path.
   *
   * @return string
   *   Absolute path to the workflows directory.
   */
  protected function getTemplateDirectory(): string {
    $module_path = $this->moduleList->getPath('aabenforms_workflows');
    return DRUPAL_ROOT . '/' . $module_path . '/workflows';
  }

  /**
   * Gets the full path to a template file.
   *
   * @param string $template_id
   *   The template identifier.
   *
   * @return string
   *   Absolute path to the BPMN file.
   */
  protected function getTemplatePath(string $template_id): string {
    return $this->getTemplateDirectory() . '/' . $template_id . '.bpmn';
  }

  /**
   * Extracts metadata from a BPMN template file.
   *
   * @param string $file
   *   Path to the BPMN file.
   *
   * @return array|null
   *   Metadata array or NULL if parsing failed.
   */
  protected function extractTemplateMetadata(string $file): ?array {
    try {
      $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOENT);
      if ($xml === FALSE) {
        return NULL;
      }

      $namespaces = $xml->getNamespaces(TRUE);
      $ns = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;

      if (!$ns) {
        return NULL;
      }

      // Extract process name and documentation.
      $xml->registerXPathNamespace('bpmn', $ns);
      $processes = $xml->xpath('//bpmn:process');

      if (empty($processes)) {
        return NULL;
      }

      $process = $processes[0];
      $name = (string) ($process['name'] ?? basename($file, '.bpmn'));
      $description = '';

      // Try to extract documentation element.
      $docs = $process->xpath('bpmn:documentation');
      if (!empty($docs)) {
        $description = (string) $docs[0];
      }

      // Extract category from documentation metadata.
      $category = 'other';
      if (preg_match('/\[category:\s*([^\]]+)\]/', $description, $matches)) {
        $category = trim($matches[1]);
      }

      return [
        'name' => $name,
        'description' => $description,
        'category' => $category,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to extract metadata from BPMN file @file: @message', [
        '@file' => $file,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
