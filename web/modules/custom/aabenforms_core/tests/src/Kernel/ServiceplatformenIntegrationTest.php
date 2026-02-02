<?php

namespace Drupal\Tests\aabenforms_core\Kernel;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\KernelTests\KernelTestBase;
use Drupal\aabenforms_core\Exception\ServiceplatformenException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

/**
 * Tests Serviceplatformen SOAP integration (SF1520, SF1530, SF1601).
 *
 * Validates:
 * - CPR person lookup (SF1520)
 * - CVR company lookup (SF1530)
 * - Digital Post messaging (SF1601)
 * - SOAP fault handling
 * - Authentication
 * - Response parsing.
 *
 * @group aabenforms_core
 * @group integration
 */
class ServiceplatformenIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'domain',
    'key',
    'encrypt',
    'aabenforms_core',
  ];

  /**
   * The Serviceplatformen client.
   *
   * @var \Drupal\aabenforms_core\Service\ServiceplatformenClient
   */
  protected $serviceplatformenClient;

  /**
   * The mock HTTP handler.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $mockHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('domain');
    $this->installConfig(['system', 'domain', 'aabenforms_core']);

    // Configure mock Serviceplatformen URLs and credentials.
    $config = $this->config('aabenforms_core.settings');
    $config->set('serviceplatformen.urls', [
      'SF1520' => 'https://test.serviceplatformen.dk/sf1520',
      'SF1530' => 'https://test.serviceplatformen.dk/sf1530',
      'SF1601' => 'https://test.serviceplatformen.dk/sf1601',
    ]);
    $config->set('serviceplatformen.username', 'test_user');
    $config->set('serviceplatformen.password', 'test_pass');
    $config->save();

    // Create mock HTTP handler for WireMock-style responses.
    $this->mockHandler = new MockHandler();
    $handlerStack = HandlerStack::create($this->mockHandler);
    $httpClient = new Client(['handler' => $handlerStack]);

    // Get Serviceplatformen client with mocked HTTP client.
    $this->serviceplatformenClient = new ServiceplatformenClient(
      $this->container->get('config.factory'),
      $this->container->get('cache.default'),
      $this->container->get('logger.factory'),
      $httpClient
    );
  }

  /**
   * Tests SF1520 CPR person lookup with valid response.
   */
  public function testSf1520CprLookupSuccess(): void {
    // Mock successful SF1520 response.
    $soapResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <PersonLookupResponse xmlns="http://serviceplatformen.dk/cprlookup/1">
      <CPR>0101701234</CPR>
      <FirstName>Jane</FirstName>
      <LastName>Doe</LastName>
      <Address>Viborgvej 2</Address>
      <PostalCode>8000</PostalCode>
      <City>Aarhus C</City>
    </PersonLookupResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $this->mockHandler->append(new Response(200, [], $soapResponse));

    // Execute CPR lookup.
    $result = $this->serviceplatformenClient->request('SF1520', 'PersonLookup', [
      'cpr' => '0101701234',
    ]);

    // Verify response structure.
    $this->assertTrue($result['success']);
    $this->assertEquals('SF1520', $result['service']);
    $this->assertArrayHasKey('person', $result);
    $this->assertEquals('0101701234', $result['person']['cpr']);
    $this->assertEquals('Jane', $result['person']['first_name']);
    $this->assertEquals('Doe', $result['person']['last_name']);
    $this->assertEquals('Jane Doe', $result['person']['full_name']);
    $this->assertEquals('Viborgvej 2', $result['person']['address']);
    $this->assertEquals('8000', $result['person']['postal_code']);
    $this->assertEquals('Aarhus C', $result['person']['city']);
  }

  /**
   * Tests SF1530 CVR company lookup with valid response.
   */
  public function testSf1530CvrLookupSuccess(): void {
    // Mock successful SF1530 response.
    $soapResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <CompanyLookupResponse xmlns="http://serviceplatformen.dk/cvrlookup/1">
      <CVR>12345678</CVR>
      <CompanyName>Aarhus Kommune</CompanyName>
      <Address>Rådhuspladsen 2</Address>
      <PostalCode>8000</PostalCode>
      <City>Aarhus C</City>
      <Status>ACTIVE</Status>
    </CompanyLookupResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $this->mockHandler->append(new Response(200, [], $soapResponse));

    // Execute CVR lookup.
    $result = $this->serviceplatformenClient->request('SF1530', 'CompanyLookup', [
      'cvr' => '12345678',
    ]);

    // Verify response structure.
    $this->assertTrue($result['success']);
    $this->assertEquals('SF1530', $result['service']);
    $this->assertArrayHasKey('company', $result);
    $this->assertEquals('12345678', $result['company']['cvr']);
    $this->assertEquals('Aarhus Kommune', $result['company']['name']);
    $this->assertEquals('Rådhuspladsen 2', $result['company']['address']);
    $this->assertEquals('8000', $result['company']['postal_code']);
    $this->assertEquals('Aarhus C', $result['company']['city']);
    $this->assertEquals('ACTIVE', $result['company']['status']);
  }

  /**
   * Tests SF1601 Digital Post message send with valid response.
   */
  public function testSf1601DigitalPostSuccess(): void {
    // Mock successful SF1601 response.
    $soapResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SendMessageResponse xmlns="http://serviceplatformen.dk/digitalpost/1">
      <MessageID>msg_123456</MessageID>
      <Status>OK</Status>
      <StatusCode>200</StatusCode>
    </SendMessageResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $this->mockHandler->append(new Response(200, [], $soapResponse));

    // Execute Digital Post send.
    $result = $this->serviceplatformenClient->request('SF1601', 'SendMessage', [
      'cpr' => '0101701234',
      'subject' => 'Building permit approved',
      'content' => 'Your building permit has been approved.',
      'message_id' => 'msg_123456',
    ]);

    // Verify response structure.
    $this->assertTrue($result['success']);
    $this->assertEquals('SF1601', $result['service']);
    $this->assertArrayHasKey('status', $result);
    $this->assertEquals('msg_123456', $result['status']['message_id']);
    $this->assertEquals('OK', $result['status']['status']);
    $this->assertEquals('200', $result['status']['status_code']);
    $this->assertTrue($result['status']['sent']);
  }

  /**
   * Tests SOAP fault handling.
   */
  public function testSoapFaultHandling(): void {
    // Mock SOAP fault response.
    $soapFault = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <soap:Fault>
      <faultcode>soap:Client</faultcode>
      <faultstring>Invalid CPR number format</faultstring>
    </soap:Fault>
  </soap:Body>
</soap:Envelope>
XML;

    $this->mockHandler->append(new Response(500, [], $soapFault));

    // Expect ServiceplatformenException.
    try {
      $this->serviceplatformenClient->request('SF1520', 'PersonLookup', [
        'cpr' => 'invalid',
      ]);
      $this->fail('Expected ServiceplatformenException was not thrown');
    }
    catch (ServiceplatformenException $e) {
      // Verify the exception was thrown and contains expected message.
      $this->assertStringContainsString('Serviceplatformen request failed', $e->getMessage());
      $this->assertEquals('SF1520', $e->getService());
      $this->assertEquals('PersonLookup', $e->getOperation());
    }
  }

  /**
   * Tests authentication in SOAP request.
   */
  public function testAuthenticationIncluded(): void {
    // We'll verify the request body contains credentials.
    $soapResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <PersonLookupResponse xmlns="http://serviceplatformen.dk/cprlookup/1">
      <CPR>0101701234</CPR>
      <FirstName>Jane</FirstName>
      <LastName>Doe</LastName>
    </PersonLookupResponse>
  </soap:Body>
</soap:Envelope>
XML;

    // Create a callback to verify authentication header.
    $requestValidator = function ($request) use ($soapResponse) {
      $body = (string) $request->getBody();
      // Verify credentials are in the SOAP header.
      $this->assertStringContainsString('<wsse:Username>test_user</wsse:Username>', $body);
      $this->assertStringContainsString('<wsse:Password>test_pass</wsse:Password>', $body);
      $this->assertStringContainsString('<ns:PNR>0101701234</ns:PNR>', $body);
      return new Response(200, [], $soapResponse);
    };

    $this->mockHandler->append($requestValidator);

    $result = $this->serviceplatformenClient->request('SF1520', 'PersonLookup', [
      'cpr' => '0101701234',
    ]);

    $this->assertTrue($result['success']);
  }

  /**
   * Tests retry logic on transient failures.
   */
  public function testRetryOnTransientFailure(): void {
    // First request: Connection timeout (retryable).
    $this->mockHandler->append(
      new ConnectException(
        'Connection timeout',
        new Request('POST', 'https://test.serviceplatformen.dk/sf1520')
      )
    );

    // Second request: Success.
    $soapResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <PersonLookupResponse xmlns="http://serviceplatformen.dk/cprlookup/1">
      <CPR>0101701234</CPR>
      <FirstName>Jane</FirstName>
      <LastName>Doe</LastName>
    </PersonLookupResponse>
  </soap:Body>
</soap:Envelope>
XML;

    $this->mockHandler->append(new Response(200, [], $soapResponse));

    // Execute request (should retry and succeed).
    $result = $this->serviceplatformenClient->request('SF1520', 'PersonLookup', [
      'cpr' => '0101701234',
    ], ['no_cache' => TRUE]);

    $this->assertTrue($result['success']);
    $this->assertEquals('Jane', $result['person']['first_name']);
  }

}
