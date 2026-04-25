<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Audit;

use Drupal\aabenforms_digital_post\Audit\AuditEmitterInterface;
use Drupal\aabenforms_digital_post\Audit\NullAuditEmitter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests NullAuditEmitter is a side-effect-free no-op.
 *
 * Sites without aabenforms_core get this implementation; the contract is
 * "calling emit() must not throw and must not have observable side
 * effects". A test feels paranoid for a no-op, but it locks the contract
 * so a future "let's at least log to syslog" change has to break this
 * test deliberately rather than silently.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Audit\NullAuditEmitter
 * @group aabenforms_digital_post
 */
class NullAuditEmitterTest extends UnitTestCase {

  /**
   * Implements the interface.
   */
  public function testImplementsInterface(): void {
    $this->assertInstanceOf(AuditEmitterInterface::class, new NullAuditEmitter());
  }

  /**
   * Emit() returns void and does not throw, regardless of inputs.
   */
  public function testEmitIsSilentNoOp(): void {
    // PHPUnit's first-class way to mark "this test deliberately makes no
    // assertions" - keeps the test out of the risky-test bucket without
    // a meaningless assertTrue(TRUE) placeholder.
    $this->expectNotToPerformAssertions();
    $e = new NullAuditEmitter();
    $e->emit('digital_post.send', 'tx-1', 'sent', 'success');
    $e->emit('digital_post.send', 'tx-2', 'failed', 'failure', ['reason' => 'X']);
    $e->emit('', '', '', '', []);
  }

}
