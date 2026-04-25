<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Audit;

use Drupal\aabenforms_digital_post\Audit\NullAuditEmitter;
use PHPUnit\Framework\TestCase;

/**
 * Tests the NullAuditEmitter.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Audit\NullAuditEmitter
 * @group aabenforms_digital_post
 */
class NullAuditEmitterTest extends TestCase {

  /**
   * Tests that emit() does nothing and doesn't throw.
   *
   * @covers ::emit
   */
  public function testEmitDoesNothingNoException(): void {
    $emitter = new NullAuditEmitter();

    // Should not throw any exception
    $emitter->emit(
      eventType: 'digital_post_sent',
      identifier: 'hash-abc123',
      message: 'Test message',
      status: 'success',
      context: ['key' => 'value'],
    );

    // If we got here, the test passed - emit() completed without exception
    $this->assertTrue(TRUE);
  }

  /**
   * Tests that emit() can be called multiple times.
   *
   * @covers ::emit
   */
  public function testEmitCanBeCalledMultipleTimes(): void {
    $emitter = new NullAuditEmitter();

    for ($i = 0; $i < 10; $i++) {
      $emitter->emit(
        eventType: "event_$i",
        identifier: "id_$i",
        message: "Message $i",
        status: $i % 2 === 0 ? 'success' : 'failure',
      );
    }

    $this->assertTrue(TRUE);
  }

  /**
   * Tests that emit() accepts empty context.
   *
   * @covers ::emit
   */
  public function testEmitAcceptsEmptyContext(): void {
    $emitter = new NullAuditEmitter();

    $emitter->emit(
      eventType: 'test',
      identifier: 'id',
      message: 'msg',
      status: 'success',
      context: [],
    );

    $this->assertTrue(TRUE);
  }

  /**
   * Tests that emit() accepts complex context.
   *
   * @covers ::emit
   */
  public function testEmitAcceptsComplexContext(): void {
    $emitter = new NullAuditEmitter();

    $emitter->emit(
      eventType: 'complex_event',
      identifier: 'complex_id',
      message: 'Complex message',
      status: 'success',
      context: [
        'nested' => ['array' => ['of' => 'values']],
        'number' => 42,
        'boolean' => TRUE,
        'null' => NULL,
      ],
    );

    $this->assertTrue(TRUE);
  }

}
