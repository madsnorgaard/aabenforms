<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\aabenforms_workflows\Plugin\Action\CprLookupAction;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CprLookupAction ECA plugin.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\CprLookupAction
 * @group aabenforms_workflows
 */
class CprLookupActionTest extends UnitTestCase {

  /**
   * The CprLookupAction plugin.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\CprLookupAction
   */
  protected CprLookupAction $action;

  /**
   * Mock Serviceplatformen client.
   *
   * @var \Drupal\aabenforms_core\Service\ServiceplatformenClient|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $serviceplatformenClient;

  /**
   * Mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock ECA token services.
   *
   * @var \Drupal\eca\Token\TokenInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tokenServices;

  /**
   * Token storage for testing.
   *
   * @var array
   */
  protected array $tokenStorage = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock all ECA ActionBase dependencies.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $ecaState = $this->createMock(EcaState::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Mock ECA token services.
    $this->tokenServices = $this->createMock(EcaTokenInterface::class);
    $this->tokenServices->method('getTokenData')
      ->willReturnCallback(function($name) {
        return $this->tokenStorage[$name] ?? NULL;
      });
    $this->tokenServices->method('addTokenData')
      ->willReturnCallback(function($name, $value) {
        $this->tokenStorage[$name] = $value;
        return $this->tokenServices;
      });

    // Mock Serviceplatformen client.
    $this->serviceplatformenClient = $this->createMock(ServiceplatformenClient::class);

    // Create action instance.
    $configuration = [
      'cpr_token' => 'cpr',
      'result_token' => 'person_data',
      'use_cache' => TRUE,
    ];

    $this->action = new CprLookupAction(
      $configuration,
      'aabenforms_cpr_lookup',
      [],
      $entityTypeManager,
      $this->tokenServices,
      $currentUser,
      $time,
      $ecaState,
      $this->logger
    );

    // Inject Serviceplatformen client using reflection.
    $reflectionClass = new \ReflectionClass($this->action);
    $clientProperty = $reflectionClass->getProperty('serviceplatformenClient');
    $clientProperty->setAccessible(TRUE);
    $clientProperty->setValue($this->action, $this->serviceplatformenClient);
  }

  /**
   * Tests successful CPR lookup.
   *
   * @covers ::execute
   */
  public function testSuccessfulLookup(): void {
    $cpr = '0101001234';
    $personData = [
      'full_name' => 'Test Testesen',
      'given_name' => 'Test',
      'family_name' => 'Testesen',
      'address' => 'Testvej 1, 1234 Testby',
      'birth_date' => '2000-01-01',
    ];

    // Set CPR token.
    $this->tokenStorage['cpr'] = $cpr;

    // Mock Serviceplatformen response.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->with(
        'SF1520',
        'PersonLookup',
        ['cpr' => $cpr],
        ['no_cache' => FALSE]
      )
      ->willReturn(['person' => $personData]);

    // Expect success log.
    $this->logger->expects($this->exactly(2))
      ->method('info');

    // Execute action.
    $this->action->execute();

    // Verify result token contains person data.
    $this->assertEquals($personData, $this->tokenStorage['person_data']);
  }

  /**
   * Tests person not found (404 response).
   *
   * @covers ::execute
   */
  public function testPersonNotFound(): void {
    $cpr = '9999999999';

    // Set CPR token.
    $this->tokenStorage['cpr'] = $cpr;

    // Mock Serviceplatformen response with empty person.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->willReturn(['person' => NULL]);

    // Expect warning log.
    $this->logger->expects($this->once())
      ->method('warning');

    // Execute action.
    $this->action->execute();

    // Verify result token is NULL.
    $this->assertNull($this->tokenStorage['person_data']);
  }

  /**
   * Tests SOAP fault handling.
   *
   * @covers ::execute
   */
  public function testSoapFaultHandling(): void {
    $cpr = '0101001234';

    // Set CPR token.
    $this->tokenStorage['cpr'] = $cpr;

    // Mock Serviceplatformen throwing exception.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \SoapFault('Server', 'SF1520 service unavailable'));

    // Expect error log.
    $this->logger->expects($this->once())
      ->method('error');

    // Execute action.
    $this->action->execute();

    // Verify result token is NULL on error.
    $this->assertNull($this->tokenStorage['person_data']);
  }

  /**
   * Tests CPR validation before lookup.
   *
   * @covers ::execute
   */
  public function testCprValidation(): void {
    // Test with hyphenated CPR (should be normalized).
    $cprWithHyphen = '010100-1234';
    $expectedCpr = '0101001234';
    $personData = ['full_name' => 'Test Testesen'];

    // Set CPR token with hyphen.
    $this->tokenStorage['cpr'] = $cprWithHyphen;

    // Mock Serviceplatformen - should receive normalized CPR.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->with(
        $this->anything(),
        $this->anything(),
        ['cpr' => $expectedCpr],
        $this->anything()
      )
      ->willReturn(['person' => $personData]);

    // Execute action.
    $this->action->execute();

    // Verify lookup was successful.
    $this->assertEquals($personData, $this->tokenStorage['person_data']);
  }

  /**
   * Tests audit logging for GDPR compliance.
   *
   * @covers ::execute
   */
  public function testAuditLogging(): void {
    $cpr = '0101001234';
    $personData = ['full_name' => 'Test Testesen'];

    // Set CPR token.
    $this->tokenStorage['cpr'] = $cpr;

    // Mock successful response.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->willReturn(['person' => $personData]);

    // Expect logs with masked CPR for GDPR compliance.
    $this->logger->expects($this->exactly(2))
      ->method('info')
      ->with(
        $this->anything(),
        $this->callback(function($context) {
          // CPR should be masked in logs (only first 6 digits + XXXX).
          if (isset($context['cpr'])) {
            return str_contains($context['cpr'], 'XXXX');
          }
          return TRUE;
        })
      );

    // Execute action.
    $this->action->execute();

    // Verify result.
    $this->assertEquals($personData, $this->tokenStorage['person_data']);
  }

  /**
   * Tests missing CPR handling.
   *
   * @covers ::execute
   */
  public function testMissingCpr(): void {
    // Don't set CPR token.

    // Expect warning log.
    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        'CPR lookup failed: No CPR number provided',
        ['action' => 'aabenforms_cpr_lookup']
      );

    // Serviceplatformen should NOT be called.
    $this->serviceplatformenClient->expects($this->never())
      ->method('request');

    // Execute action.
    $this->action->execute();

    // Verify result is NULL.
    $this->assertNull($this->tokenStorage['person_data']);
  }

  /**
   * Tests cache configuration.
   *
   * @covers ::execute
   */
  public function testCachingBehavior(): void {
    $cpr = '0101001234';
    $personData = ['full_name' => 'Test Testesen'];

    // Set CPR token.
    $this->tokenStorage['cpr'] = $cpr;

    // Test with caching enabled (default).
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->anything(),
        ['no_cache' => FALSE]
      )
      ->willReturn(['person' => $personData]);

    $this->action->execute();
    $this->assertEquals($personData, $this->tokenStorage['person_data']);
  }

  /**
   * Tests default configuration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $defaults = $this->action->defaultConfiguration();

    $this->assertArrayHasKey('cpr_token', $defaults);
    $this->assertEquals('cpr', $defaults['cpr_token']);

    $this->assertArrayHasKey('result_token', $defaults);
    $this->assertEquals('person_data', $defaults['result_token']);

    $this->assertArrayHasKey('use_cache', $defaults);
    $this->assertTrue($defaults['use_cache']);
  }

}
