<?php

namespace Drupal\Tests\aabenforms_core\Unit\Service;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\aabenforms_core\Exception\ServiceplatformenException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the ServiceplatformenClient.
 *
 * @group aabenforms_core
 * @coversDefaultClass \Drupal\aabenforms_core\Service\ServiceplatformenClient
 */
class ServiceplatformenClientTest extends UnitTestCase {

  /**
   * The service client under test.
   *
   * @var \Drupal\aabenforms_core\Service\ServiceplatformenClient
   */
  protected ServiceplatformenClient $client;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);

    // Mock logger factory.
    $this->logger = $this->createMock(LoggerInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    // Mock config.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['serviceplatformen.urls', NULL, [
        'SF1520' => 'https://test.serviceplatformen.dk/sf1520',
        'SF1530' => 'https://test.serviceplatformen.dk/sf1530',
        'SF1601' => 'https://test.serviceplatformen.dk/sf1601',
      ],
      ],
      ['serviceplatformen.username', NULL, 'testuser'],
      ['serviceplatformen.password', NULL, 'testpass'],
    ]);
    $this->configFactory->method('get')->willReturn($config);

    // Create client.
    $this->client = new ServiceplatformenClient(
      $this->configFactory,
      $this->cache,
      $loggerFactory,
      $this->httpClient
    );
  }

  /**
   * Tests SF1520 SOAP envelope building.
   *
   * @covers ::buildSoapEnvelope
   * @covers ::buildSf1520Envelope
   */
  public function testBuildSf1520Envelope(): void {
    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('buildSoapEnvelope');
    $method->setAccessible(TRUE);

    $envelope = $method->invoke($this->client, 'SF1520', 'PersonLookup', ['cpr' => '0101701234']);

    // Verify envelope structure.
    $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $envelope);
    $this->assertStringContainsString('<soap:Envelope', $envelope);
    $this->assertStringContainsString('<wsse:UsernameToken>', $envelope);
    $this->assertStringContainsString('<wsse:Username>testuser</wsse:Username>', $envelope);
    $this->assertStringContainsString('<wsse:Password>testpass</wsse:Password>', $envelope);
    $this->assertStringContainsString('<ns:PNR>0101701234</ns:PNR>', $envelope);
  }

  /**
   * Tests SF1530 SOAP envelope building.
   *
   * @covers ::buildSoapEnvelope
   * @covers ::buildSf1530Envelope
   */
  public function testBuildSf1530Envelope(): void {
    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('buildSoapEnvelope');
    $method->setAccessible(TRUE);

    $envelope = $method->invoke($this->client, 'SF1530', 'CompanyLookup', ['cvr' => '20016175']);

    // Verify envelope structure.
    $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $envelope);
    $this->assertStringContainsString('<soap:Envelope', $envelope);
    $this->assertStringContainsString('<ns:CVR>20016175</ns:CVR>', $envelope);
  }

  /**
   * Tests SF1601 SOAP envelope building.
   *
   * @covers ::buildSoapEnvelope
   * @covers ::buildSf1601Envelope
   */
  public function testBuildSf1601Envelope(): void {
    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('buildSoapEnvelope');
    $method->setAccessible(TRUE);

    $envelope = $method->invoke($this->client, 'SF1601', 'SendMessage', [
      'cpr' => '0101701234',
      'subject' => 'Test Subject',
      'content' => 'Test Content',
    ]);

    // Verify envelope structure.
    $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $envelope);
    $this->assertStringContainsString('<soap:Envelope', $envelope);
    $this->assertStringContainsString('<ns:CPR>0101701234</ns:CPR>', $envelope);
    $this->assertStringContainsString('<ns:Subject>Test Subject</ns:Subject>', $envelope);
    $this->assertStringContainsString('<![CDATA[Test Content]]>', $envelope);
  }

  /**
   * Tests SF1520 response parsing.
   *
   * @covers ::parseResponse
   * @covers ::parseSf1520Response
   */
  public function testParseSf1520Response(): void {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <PersonLookupResponse>
      <CPR>0101701234</CPR>
      <FirstName>Anders</FirstName>
      <LastName>Andersen</LastName>
      <Address>Testvej 1</Address>
      <PostalCode>8000</PostalCode>
      <City>Aarhus C</City>
    </PersonLookupResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('parseResponse');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->client, $xml);

    $this->assertTrue($result['success']);
    $this->assertEquals('SF1520', $result['service']);
    $this->assertEquals('0101701234', $result['person']['cpr']);
    $this->assertEquals('Anders', $result['person']['first_name']);
    $this->assertEquals('Andersen', $result['person']['last_name']);
    $this->assertEquals('Anders Andersen', $result['person']['full_name']);
    $this->assertEquals('Testvej 1', $result['person']['address']);
    $this->assertEquals('8000', $result['person']['postal_code']);
    $this->assertEquals('Aarhus C', $result['person']['city']);
  }

  /**
   * Tests SF1530 response parsing.
   *
   * @covers ::parseResponse
   * @covers ::parseSf1530Response
   */
  public function testParseSf1530Response(): void {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <CompanyLookupResponse>
      <CVR>20016175</CVR>
      <CompanyName>Test Company A/S</CompanyName>
      <Address>Hovedgaden 10</Address>
      <PostalCode>1000</PostalCode>
      <City>København K</City>
      <Status>ACTIVE</Status>
    </CompanyLookupResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('parseResponse');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->client, $xml);

    $this->assertTrue($result['success']);
    $this->assertEquals('SF1530', $result['service']);
    $this->assertEquals('20016175', $result['company']['cvr']);
    $this->assertEquals('Test Company A/S', $result['company']['name']);
    $this->assertEquals('ACTIVE', $result['company']['status']);
  }

  /**
   * Tests SF1601 response parsing.
   *
   * @covers ::parseResponse
   * @covers ::parseSf1601Response
   */
  public function testParseSf1601Response(): void {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SendMessageResponse>
      <MessageID>msg_12345</MessageID>
      <Status>OK</Status>
      <StatusCode>200</StatusCode>
    </SendMessageResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('parseResponse');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->client, $xml);

    $this->assertTrue($result['success']);
    $this->assertEquals('SF1601', $result['service']);
    $this->assertEquals('msg_12345', $result['status']['message_id']);
    $this->assertEquals('OK', $result['status']['status']);
    $this->assertTrue($result['status']['sent']);
  }

  /**
   * Tests SOAP fault handling.
   *
   * @covers ::parseResponse
   */
  public function testSoapFaultHandling(): void {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <soap:Fault>
      <faultcode>soap:Server</faultcode>
      <faultstring>Internal server error</faultstring>
    </soap:Fault>
  </soap:Body>
</soap:Envelope>
XML;

    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('parseResponse');
    $method->setAccessible(TRUE);

    $this->expectException(ServiceplatformenException::class);
    $this->expectExceptionMessage('SOAP Fault: Internal server error');

    $method->invoke($this->client, $xml);
  }

  /**
   * Tests cache key generation.
   *
   * @covers ::getCacheKey
   */
  public function testGetCacheKey(): void {
    $reflection = new \ReflectionClass($this->client);
    $method = $reflection->getMethod('getCacheKey');
    $method->setAccessible(TRUE);

    $key1 = $method->invoke($this->client, 'SF1520', 'PersonLookup', ['cpr' => '0101701234']);
    $key2 = $method->invoke($this->client, 'SF1520', 'PersonLookup', ['cpr' => '0101701234']);
    $key3 = $method->invoke($this->client, 'SF1520', 'PersonLookup', ['cpr' => '9999999999']);

    // Same params should generate same key.
    $this->assertEquals($key1, $key2);

    // Different params should generate different key.
    $this->assertNotEquals($key1, $key3);

    // Key format verification.
    $this->assertStringStartsWith('aabenforms_core:serviceplatformen:SF1520:PersonLookup:', $key1);
  }

}
