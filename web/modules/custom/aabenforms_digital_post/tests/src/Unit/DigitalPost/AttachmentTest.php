<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Attachment;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Attachment value object factories + constructor guards.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\Attachment
 * @group aabenforms_digital_post
 */
class AttachmentTest extends UnitTestCase {

  /**
   * FromBytes() captures filename, mime, and the byte length as size.
   */
  public function testFromBytesHappyPath(): void {
    $a = Attachment::fromBytes('hello world', 'greeting.txt', 'text/plain');
    $this->assertSame('greeting.txt', $a->filename);
    $this->assertSame('text/plain', $a->mimeType);
    $this->assertSame(11, $a->sizeBytes);
    $this->assertNull($a->path);
    $this->assertSame('hello world', $a->bytes());
  }

  /**
   * FromFile() reads filename + size from the on-disk file.
   */
  public function testFromFileHappyPath(): void {
    $tmp = tempnam(sys_get_temp_dir(), 'af-attach-');
    file_put_contents($tmp, 'on-disk-payload');
    try {
      $a = Attachment::fromFile($tmp, 'doc.txt', 'text/plain');
      $this->assertSame('doc.txt', $a->filename);
      $this->assertSame('text/plain', $a->mimeType);
      $this->assertSame($tmp, $a->path);
      $this->assertSame(15, $a->sizeBytes);
      $this->assertSame('on-disk-payload', $a->bytes());
    }
    finally {
      @unlink($tmp);
    }
  }

  /**
   * FromFile() rejects a path that doesn't exist or isn't readable.
   */
  public function testFromFileMissingPathRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Attachment::fromFile('/nonexistent/' . uniqid('af-', TRUE));
  }

  /**
   * Empty filename is rejected.
   */
  public function testEmptyFilenameRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Attachment::fromBytes('payload', '', 'text/plain');
  }

  /**
   * Empty mime type is rejected.
   */
  public function testEmptyMimeRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Attachment::fromBytes('payload', 'a.txt', '');
  }

  /**
   * Zero-byte payload is rejected (sizeBytes must be positive).
   */
  public function testZeroSizeRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Attachment::fromBytes('', 'a.txt', 'text/plain');
  }

  /**
   * Attachment over the 10 MiB cap is rejected.
   */
  public function testOversizeRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Attachment::fromBytes(str_repeat('A', Attachment::DEFAULT_MAX_SIZE_BYTES + 1), 'big.bin', 'application/octet-stream');
  }

}
