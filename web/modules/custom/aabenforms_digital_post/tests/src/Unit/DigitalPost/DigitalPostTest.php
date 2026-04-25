<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Attachment;
use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the DigitalPost DTO constructor guards + totalAttachmentBytes.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\DigitalPost
 * @group aabenforms_digital_post
 */
class DigitalPostTest extends UnitTestCase {

  /**
   * Builds a baseline valid DigitalPost - tweak via the args to test guards.
   */
  protected function build(
    string $subject = 'Subject',
    string $body = 'Body',
    array $attachments = [],
    string $type = DigitalPost::TYPE_DIGITAL_POST,
  ): DigitalPost {
    return new DigitalPost(
      recipient: Recipient::cpr('0000000001'),
      sender: new Sender('12345678'),
      subject: $subject,
      body: $body,
      attachments: $attachments,
      type: $type,
    );
  }

  /**
   * The happy path constructs without throwing.
   */
  public function testValidPostConstructs(): void {
    $post = $this->build();
    $this->assertSame('Subject', $post->subject);
    $this->assertSame(DigitalPost::TYPE_DIGITAL_POST, $post->type);
  }

  /**
   * Empty subject is rejected.
   */
  public function testEmptySubjectRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->build(subject: '');
  }

  /**
   * Whitespace-only subject is rejected (trim guard).
   */
  public function testWhitespaceOnlySubjectRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->build(subject: "   \t\n  ");
  }

  /**
   * Subject longer than 255 chars is rejected.
   */
  public function testOversizeSubjectRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->build(subject: str_repeat('a', 256));
  }

  /**
   * Boundary case: 255-char subject is accepted as the upper limit.
   */
  public function testSubjectAtMaxBoundaryAccepted(): void {
    $post = $this->build(subject: str_repeat('a', 255));
    $this->assertSame(255, strlen($post->subject));
  }

  /**
   * Empty body is rejected.
   */
  public function testEmptyBodyRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->build(body: '');
  }

  /**
   * An invalid type string fails the in_array allowlist.
   */
  public function testInvalidTypeRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->build(type: 'NotARealType');
  }

  /**
   * All four valid types construct without error.
   */
  public function testAllValidTypesAccepted(): void {
    foreach (DigitalPost::VALID_TYPES as $type) {
      $post = $this->build(type: $type);
      $this->assertSame($type, $post->type);
    }
  }

  /**
   * A non-Attachment in the attachments array is rejected by index.
   */
  public function testNonAttachmentInArrayRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('attachments[1]');
    $real = Attachment::fromBytes('payload', 'a.txt', 'text/plain');
    $this->build(attachments: [$real, 'not-an-attachment']);
  }

  /**
   * TotalAttachmentBytes() sums sizeBytes across all attachments.
   */
  public function testTotalAttachmentBytesSums(): void {
    $a = Attachment::fromBytes(str_repeat('x', 10), 'a.txt', 'text/plain');
    $b = Attachment::fromBytes(str_repeat('y', 25), 'b.txt', 'text/plain');
    $post = $this->build(attachments: [$a, $b]);
    $this->assertSame(35, $post->totalAttachmentBytes());
  }

  /**
   * TotalAttachmentBytes() returns 0 with no attachments.
   */
  public function testTotalAttachmentBytesEmpty(): void {
    $this->assertSame(0, $this->build()->totalAttachmentBytes());
  }

}
