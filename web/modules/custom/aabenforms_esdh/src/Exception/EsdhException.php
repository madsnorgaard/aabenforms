<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Exception;

/**
 * Thrown when an ESDH connector is misconfigured or cannot be resolved.
 *
 * A live connector fails hard (throws) rather than silently degrading to the
 * demo driver, mirroring the Digital Post fail-closed rule.
 */
class EsdhException extends \RuntimeException {}
