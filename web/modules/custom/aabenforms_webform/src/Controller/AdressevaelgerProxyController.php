<?php

namespace Drupal\aabenforms_webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Proxies address autocomplete requests to the Adressevælger service.
 *
 * Keeps the access token server-side. Mirrors the upstream contract:
 *   search: GET {apiUrl}/{type}/soeg?tekst=&token=&[vejnavn|postnummer|kommuneKode|maksimum|medtagForeloebige]
 *   lookup: GET {apiUrl}/{type}/{id}?token=
 */
class AdressevaelgerProxyController extends ControllerBase {

  /**
   * Query parameters forwarded to the upstream search endpoint.
   */
  private const ALLOWED_PARAMS = [
    'tekst', 'vejnavn', 'postnummer', 'kommuneKode', 'maksimum', 'medtagForeloebige',
  ];

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Constructs the controller.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * Forwards a search or lookup request to the upstream service.
   *
   * @param string $type
   *   Either 'adresser' (full addresses) or 'husnumre' (access addresses).
   * @param string $op
   *   Either the literal 'soeg' (search) or an address id (lookup).
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The upstream JSON, or an error envelope matching the upstream shape.
   */
  public function proxy(string $type, string $op, Request $request): JsonResponse {
    if (!in_array($type, ['adresser', 'husnumre'], TRUE)) {
      return new JsonResponse(['status' => 'fejl', 'beskrivelse' => 'Invalid endpoint'], 400);
    }

    $api_url = rtrim(Settings::get('aabenforms_adressevaelger_api_url', 'https://adressevaelger.dk'), '/');
    $query = ['token' => Settings::get('aabenforms_adressevaelger_token', 'aabenforms-adr-2026')];

    if ($op === 'soeg') {
      foreach (self::ALLOWED_PARAMS as $key) {
        $value = $request->query->get($key);
        if ($value !== NULL && $value !== '') {
          $query[$key] = $value;
        }
      }
      $url = $api_url . '/' . $type . '/soeg';
    }
    else {
      $url = $api_url . '/' . $type . '/' . rawurlencode($op);
    }

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => $query,
        'timeout' => 6,
      ]);
      $data = json_decode((string) $response->getBody(), TRUE);
      return new JsonResponse($data, 200, ['Cache-Control' => 'public, max-age=60']);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['status' => 'fejl', 'beskrivelse' => 'Address service unavailable'], 502);
    }
  }

}
