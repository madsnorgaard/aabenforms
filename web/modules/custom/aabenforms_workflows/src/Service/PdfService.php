<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;

/**
 * Mock PDF generation service for development and demos.
 *
 * This service simulates PDF generation for certificates, permits, and
 * documents. In production, this would integrate with TCPDF, DOMPDF,
 * or a dedicated PDF service.
 */
class PdfService {

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
   * Constructs a PdfService.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Generates a PDF document.
   *
   * @param string $template
   *   Template type (e.g., 'parking_permit', 'marriage_certificate', 'building_permit').
   * @param array $data
   *   Data to populate the PDF template.
   * @param array $options
   *   Optional settings:
   *   - filename: string - Custom filename
   *   - orientation: string - 'portrait' or 'landscape'
   *   - size: string - 'A4', 'letter', etc.
   *
   * @return array
   *   Result containing:
   *   - status: string - 'success' or 'failed'
   *   - file_id: int - Drupal file entity ID
   *   - file_uri: string - File URI
   *   - file_url: string - Public file URL
   *   - file_size: int - File size in bytes
   *   - error: string - Error message (if failed)
   */
  public function generatePdf(string $template, array $data, array $options = []): array {
    // Simulate PDF generation delay.
    usleep(800000);

    try {
      // Generate mock PDF content.
      $pdf_content = $this->generateMockPdfContent($template, $data);

      // Prepare filename.
      $filename = $options['filename'] ?? ($template . '_' . time() . '.pdf');
      if (!str_ends_with($filename, '.pdf')) {
        $filename .= '.pdf';
      }

      // Prepare directory.
      $directory = 'public://workflow-documents/' . $template;
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Save file.
      $file_uri = $directory . '/' . $filename;
      $file = file_save_data($pdf_content, $file_uri, FileSystemInterface::EXISTS_REPLACE);

      if (!$file) {
        throw new \Exception('Failed to save PDF file');
      }

      $result = [
        'status' => 'success',
        'file_id' => $file->id(),
        'file_uri' => $file->getFileUri(),
        'file_url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
        'file_size' => $file->getSize(),
        'filename' => $filename,
        'template' => $template,
        'timestamp' => time(),
      ];

      $this->logger->info('Mock PDF generated: @filename (template: @template, size: @size bytes)', [
        '@filename' => $filename,
        '@template' => $template,
        '@size' => $file->getSize(),
      ]);

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('PDF generation failed: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'status' => 'failed',
        'error' => 'PDF generation failed: ' . $e->getMessage(),
        'template' => $template,
      ];
    }
  }

  /**
   * Generates mock PDF content.
   *
   * In a real implementation, this would use TCPDF or similar to generate
   * actual PDF files with proper formatting and templates.
   *
   * @param string $template
   *   Template type.
   * @param array $data
   *   Document data.
   *
   * @return string
   *   Mock PDF content.
   */
  protected function generateMockPdfContent(string $template, array $data): string {
    // This is a simplified mock. In production, use TCPDF, DOMPDF, or similar.
    $content = "%PDF-1.4\n";
    $content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
    $content .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";

    // Add content stream.
    $text = $this->generateDocumentText($template, $data);
    $stream = "BT\n/F1 12 Tf\n50 700 Td\n($text) Tj\nET";
    $length = strlen($stream);

    $content .= "5 0 obj\n<< /Length $length >>\nstream\n$stream\nendstream\nendobj\n";
    $content .= "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000056 00000 n\n0000000115 00000 n\n0000000214 00000 n\n0000000304 00000 n\n";
    $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . strlen($content) . "\n%%EOF";

    return $content;
  }

  /**
   * Generates document text based on template and data.
   *
   * @param string $template
   *   Template type.
   * @param array $data
   *   Document data.
   *
   * @return string
   *   Document text.
   */
  protected function generateDocumentText(string $template, array $data): string {
    $texts = [
      'parking_permit' => 'PARKING PERMIT - Vehicle: ' . ($data['vehicle_registration'] ?? 'UNKNOWN') . ' - Valid until: ' . ($data['valid_until'] ?? 'N/A'),
      'marriage_certificate' => 'MARRIAGE CERTIFICATE - Couple: ' . ($data['partner1_name'] ?? 'N/A') . ' & ' . ($data['partner2_name'] ?? 'N/A') . ' - Date: ' . ($data['ceremony_date'] ?? 'N/A'),
      'building_permit' => 'BUILDING PERMIT - Address: ' . ($data['property_address'] ?? 'N/A') . ' - Type: ' . ($data['construction_type'] ?? 'N/A'),
    ];

    return $texts[$template] ?? 'DOCUMENT - Template: ' . $template;
  }

  /**
   * Generates a PDF from HTML content.
   *
   * @param string $html
   *   HTML content to convert to PDF.
   * @param array $options
   *   Optional settings.
   *
   * @return array
   *   Result array.
   */
  public function generateFromHtml(string $html, array $options = []): array {
    // Extract data from HTML for mock purposes.
    $data = ['html_length' => strlen($html)];
    return $this->generatePdf('html_document', $data, $options);
  }

  /**
   * Merges multiple PDF files.
   *
   * @param array $file_ids
   *   Array of Drupal file entity IDs to merge.
   * @param array $options
   *   Optional settings.
   *
   * @return array
   *   Result array.
   */
  public function mergePdfs(array $file_ids, array $options = []): array {
    // Mock PDF merge - in production, use PDFtk or similar.
    $this->logger->info('Mock PDF merge: @count files', [
      '@count' => count($file_ids),
    ]);

    return $this->generatePdf('merged_document', ['file_count' => count($file_ids)], $options);
  }

}
