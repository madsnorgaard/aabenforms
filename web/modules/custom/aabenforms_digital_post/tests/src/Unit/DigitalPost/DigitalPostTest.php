<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Attachment;
use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the DigitalPost DTO.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\DigitalPost
 * @group aabenforms_digital_post
 */
class DigitalPostTest extends TestCase {

  /**
   * Creates a valid recipient for testing.
   */
  private function createRecipient(): Recipient {
    return Recipient::cpr('1234567890');
  }

  /**
   * Creates a valid sender for testing.
   */
  private function createSender(): Sender {
    return new Sender('12345678', 'Test Kommune');
  }

  /**
   * Tests that empty subject throws InvalidArgumentException.
   *
   * @covers ::__construct
   */
  public function testEmptySubjectThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('DigitalPost subject cannot be empty.');

    new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: '',
      body: 'Test body content',
    );
  }

  /**
   * Tests that whitespace-only subject throws InvalidArgumentException.
   *
   * @covers ::__construct
   */
  public function testWhitespaceOnlySubjectThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('DigitalPost subject cannot be empty.');

    new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: '   ',
      body: 'Test body content',
    );
  }

  /**
   * Tests that subject over 255 chars throws InvalidArgumentException.
   *
   * @covers ::__construct
   */
  public function testSubjectOver255CharsThrows(): void {
    $longSubject = str_repeat('a', 256);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('DigitalPost subject is 256 chars, max 255.');

    new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: $longSubject,
      body: 'Test body content',
    );
  }

  /**
   * Tests that exactly 255 char subject is valid.
   *
   * @covers ::__construct
   */
  public function testSubjectExactly255CharsIsValid(): void {
    $subject = str_repeat('a', 255);

    $post = new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: $subject,
      body: 'Test body content',
    );

    $this->assertEquals($subject, $post->subject);
  }

  /**
   * Tests that empty body throws InvalidArgumentException.
   *
   * @covers ::__construct
   */
  public function testEmptyBodyThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('DigitalPost body cannot be empty.');

    new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: 'Test Subject',
      body: '',
    );
  }

  /**
   * Tests that invalid type throws InvalidArgumentException.
   *
   * @covers ::__construct
   */
  public function testInvalidTypeThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('DigitalPost type "InvalidType" is invalid. Must be one of: Automatisk Valg, Digital Post, Fysisk Post, NemSMS');

    new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: 'Test Subject',
      body: 'Test body',
      type: 'InvalidType',
    );
  }

  /**
   * Tests that all valid types are accepted.
   *
   * @covers ::__construct
   * @dataProvider validTypesProvider
   */
  public function testValidTypesAccepted(string $type): void {
    $post = new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: 'Test Subject',
      body: 'Test body',
      type: $type,
    );

    $this->assertEquals($type, $post->type);
  }

  /**
   * Data provider for valid types.
   */
  public static function validTypesProvider(): array {
    return [
      'Automatisk Valg' => [DigitalPost::TYPE_AUTOMATISK_VALG],
      'Digital Post' => [DigitalPost::TYPE_DIGITAL_POST],
      'Fysisk Post' => [DigitalPost::TYPE_FYSISK_POST],
      'NemSMS' => [DigitalPost::TYPE_NEM_SMS],
    ];
  }

  /**
   * Tests that non-Attachment in array throws InvalidArgumentException.
   *
   * @covers ::__construct
   */
  public function testNonAttachmentInArrayThrows(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('attachments[0] must be an Attachment.');

    new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: 'Test Subject',
      body: 'Test body',
      attachments: ['not an attachment'],
    );
  }

  /**
   * Tests totalAttachmentBytes() sums correctly with no attachments.
   *
   * @covers ::totalAttachmentBytes
   */
  public function testTotalAttachmentBytesNoAttachments(): void {
    $post = new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: 'Test Subject',
      body: 'Test body',
    );

    $this->assertEquals(0, $post->totalAttachmentBytes());
  }

  /**
   * Tests totalAttachmentBytes() sums correctly with multiple attachments.
   *
   * @covers ::totalAttachmentBytes
   */
  public function testTotalAttachmentBytesSumsCorrectly(): void {
    $attachment1 = Attachment::fromBytes('12345', 'file1.txt', 'text/plain');
    $attachment2 = Attachment::fromBytes('1234567890', 'file2.txt', 'text/plain');
    $attachment3 = Attachment::fromBytes('abc', 'file3.txt', 'text/plain');

    $post = new DigitalPost(
      recipient: $this->createRecipient(),
      sender: $this->createSender(),
      subject: 'Test Subject',
      body: 'Test body',
      attachments: [$attachment1, $attachment2, $attachment3],
    );

    // 5 + 10 + 3 = 18 bytes
    $this->assertEquals(18, $post->totalAttachmentBytes());
  }

  /**
   * Tests that valid DigitalPost is created successfully.
   *
   * @covers ::__construct
   */
  public function testValidDigitalPostCreation(): void {
    $recipient = $this->createRecipient();
    $sender = $this->createSender();

    $post = new DigitalPost(
      recipient: $recipient,
      sender: $sender,
      subject: 'Test Subject',
      body: '<p>Test body content</p>',
      type: DigitalPost::TYPE_DIGITAL_POST,
      meta: ['transaction_id' => 'abc-123'],
    );

    $this->assertSame($recipient, $post->recipient);
    $this->assertSame($sender, $post->sender);
    $this->assertEquals('Test Subject', $post->subject);
    $this->assertEquals('<p>Test body content</p>', $post->body);
    $this->assertEquals(DigitalPost::TYPE_DIGITAL_POST, $post->type);
    $this->assertEquals(['transaction_id' => 'abc-123'], $post->meta);
    $this->assertEmpty($post->attachments);
  }

}
