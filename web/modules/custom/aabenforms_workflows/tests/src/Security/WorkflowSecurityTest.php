<?php

namespace Drupal\Tests\aabenforms_workflows\Security;

use Drupal\KernelTests\KernelTestBase;

/**
 * Security tests for workflow system.
 *
 * @group aabenforms_workflows
 * @group security
 */
class WorkflowSecurityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_base',
    'aabenforms_core',
    'aabenforms_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Test CSRF protection on approval tokens.
   */
  public function testCsrfProtection(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    // Generate valid token.
    $valid_token = $token_service->generateToken(123, 1);

    // Attempt to use token for different submission.
    $is_valid = $token_service->validateToken(456, 1, $valid_token);

    $this->assertFalse($is_valid, 'Token cannot be reused for different submission');

    // Attempt to use token for different parent.
    $is_valid = $token_service->validateToken(123, 2, $valid_token);

    $this->assertFalse($is_valid, 'Token cannot be reused for different parent');
  }

  /**
   * Test timing-safe token comparison.
   */
  public function testTimingSafeComparison(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    $valid_token = $token_service->generateToken(123, 1);

    // Time validation of correct token.
    $start1 = microtime(true);
    $token_service->validateToken(123, 1, $valid_token);
    $time1 = microtime(true) - $start1;

    // Time validation of incorrect token (same length).
    $wrong_token = str_repeat('a', strlen($valid_token));
    $start2 = microtime(true);
    $token_service->validateToken(123, 1, $wrong_token);
    $time2 = microtime(true) - $start2;

    // Timing difference should be negligible (< 10ms for kernel test overhead).
    $diff = abs($time1 - $time2);
    $this->assertLessThan(0.01, $diff,
      'Token comparison must be timing-safe to prevent timing attacks');
  }

  /**
   * Test token expiration.
   */
  public function testTokenExpiration(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    // Generate token with expired timestamp (8 days ago).
    $expired_timestamp = time() - 691200; // 8 days
    $expired_token = $token_service->generateToken(123, 1, $expired_timestamp);

    // Verify token is detected as expired.
    $is_valid = $token_service->validateToken(123, 1, $expired_token);
    $this->assertFalse($is_valid, 'Expired token should be rejected');

    // Verify isTokenExpired method works.
    $this->assertTrue($token_service->isTokenExpired($expired_token),
      'isTokenExpired should return true for expired token');
  }

  /**
   * Test token format validation.
   */
  public function testTokenFormatValidation(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    // Test various invalid token formats.
    $invalid_tokens = [
      'not-base64',
      'bm90dmFsaWQ=', // Valid base64 but wrong format.
      '',
      'xyz123',
      base64_encode('onlyonepart'),
    ];

    foreach ($invalid_tokens as $invalid_token) {
      $is_valid = $token_service->validateToken(123, 1, $invalid_token);
      $this->assertFalse($is_valid,
        "Invalid token format '{$invalid_token}' should be rejected");
    }
  }

  /**
   * Test BPMN XML injection resistance.
   */
  public function testBpmnXmlInjectionResistance(): void {
    $template_manager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');

    // Create malicious XML with entity expansion attack.
    $malicious_xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE bpmn [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL">
  <bpmn:process id="test">
    <bpmn:documentation>&xxe;</bpmn:documentation>
  </bpmn:process>
</bpmn:definitions>
XML;

    // Create temporary file with malicious content.
    $temp_file = tempnam(sys_get_temp_dir(), 'bpmn_test_');
    file_put_contents($temp_file, $malicious_xml);

    // Attempt to import the malicious template.
    $result = $template_manager->importTemplate($temp_file, 'malicious_test');

    // Should either fail to import or parse safely with LIBXML_NOENT flag.
    $loaded = $template_manager->loadTemplate('malicious_test');

    if ($loaded) {
      // If it loaded, verify no external entity was expanded.
      $doc = $loaded->xpath('//bpmn:documentation');
      if (!empty($doc)) {
        $content = (string) $doc[0];
        $this->assertStringNotContainsString('root:', $content,
          'External entity should not be expanded');
      }

      // Clean up.
      $template_manager->deleteTemplate('malicious_test');
    }

    unlink($temp_file);
  }

  /**
   * Test HMAC secret key usage.
   */
  public function testHmacSecretKeyUsage(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    // Generate two tokens for same data.
    $token1 = $token_service->generateToken(123, 1, 1000000);
    $token2 = $token_service->generateToken(123, 1, 1000000);

    // Tokens should be identical (same input, same key, same timestamp).
    $this->assertEquals($token1, $token2,
      'Tokens with same input should match');

    // Decode tokens to verify they contain HMAC.
    $decoded = base64_decode($token1, TRUE);
    $parts = explode(':', $decoded);

    $this->assertCount(2, $parts, 'Token should have hash:timestamp format');
    $this->assertEquals(64, strlen($parts[0]),
      'HMAC-SHA256 hash should be 64 characters');
  }

  /**
   * Test service access control.
   */
  public function testServiceAccessControl(): void {
    // Verify workflow services are properly registered.
    $this->assertTrue(\Drupal::hasService('aabenforms_workflows.approval_token'),
      'Approval token service should be available');
    $this->assertTrue(\Drupal::hasService('aabenforms_workflows.bpmn_template_manager'),
      'BPMN template manager service should be available');

    // Verify services can be instantiated.
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');
    $this->assertInstanceOf(
      '\Drupal\aabenforms_workflows\Service\ApprovalTokenService',
      $token_service,
      'Token service should be correct instance'
    );

    $template_manager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');
    $this->assertInstanceOf(
      '\Drupal\aabenforms_workflows\Service\BpmnTemplateManager',
      $template_manager,
      'Template manager should be correct instance'
    );
  }

  /**
   * Test BPMN template validation against malformed XML.
   */
  public function testBpmnTemplateValidationAgainstMalformedXml(): void {
    $template_manager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');

    // Create malformed BPMN XML.
    $malformed_xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL">
  <bpmn:process id="test">
    <!-- Missing end event -->
    <bpmn:startEvent id="start"/>
  </bpmn:process>
</bpmn:definitions>
XML;

    $temp_file = tempnam(sys_get_temp_dir(), 'bpmn_test_');
    file_put_contents($temp_file, $malformed_xml);

    // Import and validate.
    $template_manager->importTemplate($temp_file, 'malformed_test');
    $validation = $template_manager->validateTemplate('malformed_test');

    // Should fail validation due to missing end event.
    $this->assertFalse($validation['valid'],
      'Malformed BPMN should fail validation');
    $this->assertNotEmpty($validation['errors'],
      'Validation should return error messages');

    // Clean up.
    $template_manager->deleteTemplate('malformed_test');
    unlink($temp_file);
  }

}
