<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\aabenforms_workflows\Plugin\Action\CvrLookupAction;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CvrLookupAction ECA plugin.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\CvrLookupAction
 * @group aabenforms_workflows
 */
class CvrLookupActionTest extends UnitTestCase {

  /**
   * The CvrLookupAction plugin.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\CvrLookupAction
   */
  protected CvrLookupAction $action;

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
      ->willReturnCallback(
              function ($name) {
                  return $this->tokenStorage[$name] ?? NULL;
              }
          );
    $this->tokenServices->method('addTokenData')
      ->willReturnCallback(
              function ($name, $value) {
                  $this->tokenStorage[$name] = $value;
                  return $this->tokenServices;
              }
          );

    // Mock Serviceplatformen client.
    $this->serviceplatformenClient = $this->createMock(ServiceplatformenClient::class);

    // Create action instance.
    $configuration = [
      'cvr_token' => 'cvr',
      'result_token' => 'company_data',
      'use_cache' => TRUE,
    ];

    $this->action = new CvrLookupAction(
          $configuration,
          'aabenforms_cvr_lookup',
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
   * Tests successful company lookup.
   *
   * @covers ::execute
   */
  public function testCompanyLookup(): void {
    $cvr = '12345678';
    $companyData = [
      'name' => 'Test A/S',
      'address' => 'Testvej 1, 1234 Testby',
      'city' => 'Testby',
      'zipcode' => '1234',
      'status' => 'ACTIVE',
    ];

    // Set CVR token.
    $this->tokenStorage['cvr'] = $cvr;

    // Mock Serviceplatformen response.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->with(
              'SF1530',
              'CompanyLookup',
              ['cvr' => $cvr],
              ['no_cache' => FALSE]
          )
      ->willReturn(['company' => $companyData]);

    // Execute action.
    $this->action->execute();

    // Verify result token contains company data.
    $this->assertEquals($companyData, $this->tokenStorage['company_data']);
  }

  /**
   * Tests invalid CVR rejection.
   *
   * @covers ::execute
   */
  public function testInvalidCvr(): void {
    // Test with empty CVR.
    $this->tokenStorage['cvr'] = '';

    // Expect warning log.
    $this->logger->expects($this->once())
      ->method('warning');

    // Serviceplatformen should NOT be called.
    $this->serviceplatformenClient->expects($this->never())
      ->method('request');

    // Execute action.
    $this->action->execute();

    // Verify result is NULL.
    $this->assertNull($this->tokenStorage['company_data']);
  }

  /**
   * Tests company not found handling.
   *
   * @covers ::execute
   */
  public function testCompanyNotFound(): void {
    $cvr = '99999999';

    // Set CVR token.
    $this->tokenStorage['cvr'] = $cvr;

    // Mock Serviceplatformen response with empty company.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->willReturn(['company' => NULL]);

    // Expect warning log.
    $this->logger->expects($this->once())
      ->method('warning');

    // Execute action.
    $this->action->execute();

    // Verify result token is NULL.
    $this->assertNull($this->tokenStorage['company_data']);
  }

  /**
   * Tests company data extraction and parsing.
   *
   * @covers ::execute
   */
  public function testDataExtraction(): void {
    $cvr = '12345678';
    $companyData = [
      'name' => 'Test ApS',
      'address' => 'Hovedgaden 123',
      'city' => 'København',
      'zipcode' => '2100',
      'p_number' => '1234567890',
      'industry_code' => '620100',
      'industry_text' => 'Computerprogrammering',
      'employees' => 25,
      'revenue' => 15000000,
    ];

    // Set CVR token.
    $this->tokenStorage['cvr'] = $cvr;

    // Mock response.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->willReturn(['company' => $companyData]);

    // Execute action.
    $this->action->execute();

    // Verify all company data is preserved.
    $result = $this->tokenStorage['company_data'];
    $this->assertIsArray($result);
    $this->assertEquals('Test ApS', $result['name']);
    $this->assertEquals('København', $result['city']);
    $this->assertEquals('620100', $result['industry_code']);
    $this->assertEquals(25, $result['employees']);
  }

  /**
   * Tests caching behavior.
   *
   * @covers ::execute
   */
  public function testCaching(): void {
    $cvr = '12345678';
    $companyData = ['name' => 'Test A/S'];

    // Set CVR token.
    $this->tokenStorage['cvr'] = $cvr;

    // Verify cache is used by default.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->with(
              $this->anything(),
              $this->anything(),
              $this->anything(),
              ['no_cache' => FALSE]
          )
      ->willReturn(['company' => $companyData]);

    $this->action->execute();
    $this->assertEquals($companyData, $this->tokenStorage['company_data']);
  }

  /**
   * Tests SOAP fault handling.
   *
   * @covers ::execute
   */
  public function testSoapFault(): void {
    $cvr = '12345678';

    // Set CVR token.
    $this->tokenStorage['cvr'] = $cvr;

    // Mock Serviceplatformen throwing exception.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \SoapFault('Server', 'SF1530 service unavailable'));

    // Expect error log.
    $this->logger->expects($this->once())
      ->method('error');

    // Execute action.
    $this->action->execute();

    // Verify result token is NULL on error.
    $this->assertNull($this->tokenStorage['company_data']);
  }

  /**
   * Tests CVR normalization (removing spaces/hyphens).
   *
   * @covers ::execute
   */
  public function testCvrNormalization(): void {
    $cvrWithSpaces = '1234 5678';
    $expectedCvr = '12345678';
    $companyData = ['name' => 'Test A/S'];

    // Set CVR token with spaces.
    $this->tokenStorage['cvr'] = $cvrWithSpaces;

    // Mock Serviceplatformen - should receive normalized CVR.
    $this->serviceplatformenClient->expects($this->once())
      ->method('request')
      ->with(
              $this->anything(),
              $this->anything(),
              ['cvr' => $expectedCvr],
              $this->anything()
          )
      ->willReturn(['company' => $companyData]);

    // Execute action.
    $this->action->execute();

    // Verify lookup was successful.
    $this->assertEquals($companyData, $this->tokenStorage['company_data']);
  }

  /**
   * Tests default configuration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $defaults = $this->action->defaultConfiguration();

    $this->assertArrayHasKey('cvr_token', $defaults);
    $this->assertEquals('cvr', $defaults['cvr_token']);

    $this->assertArrayHasKey('result_token', $defaults);
    $this->assertEquals('company_data', $defaults['result_token']);

    $this->assertArrayHasKey('use_cache', $defaults);
    $this->assertTrue($defaults['use_cache']);
  }

}
