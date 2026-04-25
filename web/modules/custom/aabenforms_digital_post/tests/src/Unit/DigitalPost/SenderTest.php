<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Sender DTO + fromConfig factory.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\Sender
 * @group aabenforms_digital_post
 */
class SenderTest extends UnitTestCase {

  /**
   * Bare 8-digit CVR is accepted unchanged.
   */
  public function testBareCvrAccepted(): void {
    $s = new Sender('12345678', 'Aarhus Kommune');
    $this->assertSame('12345678', $s->cvr);
    $this->assertSame('Aarhus Kommune', $s->name);
    $this->assertNull($s->returnAddress);
  }

  /**
   * Spaced CVR is normalised to 8 digits.
   */
  public function testSpacedCvrNormalised(): void {
    $s = new Sender('12 34 56 78');
    $this->assertSame('12345678', $s->cvr);
  }

  /**
   * Wrong-length CVR is rejected.
   */
  public function testWrongLengthCvrRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    new Sender('1234567');
  }

  /**
   * FromConfig() builds a Sender from the module's settings.
   */
  public function testFromConfigHappyPath(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['sender_cvr', '12345678'],
      ['sender_name', 'Test Kommune'],
    ]);
    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->method('get')
      ->with('aabenforms_digital_post.settings')
      ->willReturn($config);

    $s = Sender::fromConfig($factory);
    $this->assertSame('12345678', $s->cvr);
    $this->assertSame('Test Kommune', $s->name);
  }

  /**
   * FromConfig() with empty sender_cvr throws with a useful pointer.
   */
  public function testFromConfigEmptyCvrThrows(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['sender_cvr', ''],
      ['sender_name', ''],
    ]);
    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->method('get')->willReturn($config);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('aabenforms_digital_post.settings:sender_cvr');
    Sender::fromConfig($factory);
  }

}
