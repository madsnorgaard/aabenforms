<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock GIS service for zoning validation and neighbor lookup.
 *
 * This service simulates GIS/zoning data lookups for building permit
 * workflows. In production, this would integrate with municipal GIS systems
 * and property databases.
 */
class GisService {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Mock zoning data by type.
   *
   * @var array
   */
  protected static array $zoningData = [
    'residential' => [
      'allowed_construction' => ['renovation', 'extension', 'garage', 'fence'],
      'restricted_construction' => ['commercial', 'industrial'],
      'max_height_meters' => 8.5,
    ],
    'commercial' => [
      'allowed_construction' => ['renovation', 'extension', 'commercial'],
      'restricted_construction' => ['residential', 'industrial'],
      'max_height_meters' => 12,
    ],
  ];

  /**
   * Constructs a GisService.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Gets zoning information for an address.
   */
  public function getZoningInfo(string $address): array {
    usleep(300000);

    $zone_type = str_contains(strtolower($address), 'erhverv') ? 'commercial' : 'residential';
    $zoning_data = self::$zoningData[$zone_type];

    return [
      'status' => 'success',
      'address' => $address,
      'zone_type' => $zone_type,
      'zone_code' => strtoupper(substr($zone_type, 0, 3)) . '-' . rand(100, 999),
      'allowed_construction' => $zoning_data['allowed_construction'],
      'restricted_construction' => $zoning_data['restricted_construction'],
      'max_height_meters' => $zoning_data['max_height_meters'],
    ];
  }

  /**
   * Validates construction type for address.
   */
  public function validateConstructionType(string $address, string $construction_type): array {
    $zoning_info = $this->getZoningInfo($address);
    $allowed = in_array(strtolower($construction_type), $zoning_info['allowed_construction']);

    return [
      'status' => 'success',
      'allowed' => $allowed,
      'zone_type' => $zoning_info['zone_type'],
      'construction_type' => $construction_type,
      'reason' => $allowed ? 'Permitted' : 'Not permitted in this zone',
    ];
  }

  /**
   * Finds neighbors within radius.
   */
  public function findNeighborsInRadius(string $address, int $radius_meters = 50): array {
    usleep(400000);

    $neighbor_count = rand(3, 8);
    $neighbors = [];

    for ($i = 1; $i <= $neighbor_count; $i++) {
      $neighbors[] = [
        'property_id' => 'PROP-' . uniqid(),
        'address' => "Nabovej $i, 8000 Aarhus",
        'distance_meters' => rand(10, $radius_meters),
        'owner_name' => 'Nabo ' . $i,
        'contact_email' => "nabo$i@example.dk",
        'contact_phone' => '+45' . rand(10000000, 99999999),
      ];
    }

    $this->logger->info('Found @count neighbors within @radius m', [
      '@count' => count($neighbors),
      '@radius' => $radius_meters,
    ]);

    return [
      'status' => 'success',
      'total_neighbors' => count($neighbors),
      'neighbors' => $neighbors,
    ];
  }

}
