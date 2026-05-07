<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\Core\Link;
use Drupal\grants_application\Avus2DataParser;
use Drupal\Tests\grants_application\Trait\AtvDocumentTrait;

/**
 * @coversDefaultClass \Drupal\grants_application\Avus2DataParser
 *
 * @group grants_application
 */
final class Avus2DataParserTest extends KernelTestBase {

  use AtvDocumentTrait;

  /**
   * The application number.
   *
   * @var string
   */
  private string $applicationNumber = "KERNELTEST-058-0000001";

  /**
   * Test message parsing.
   */
  public function testMessageParsing() {
    $document = $this->getAtvDocument($this->applicationNumber);
    $messages = $this->container->get(Avus2DataParser::class)->getMessages($document);

    $this->assertCount(2, $messages);

    // Unread message.
    $this->assertInstanceOf(Link::class, $messages[0]['markReadLink']);
    $this->assertEquals('UNREAD', $messages[0]['messageStatus']);

    // Read message.
    $this->assertEquals('', $messages[1]['markReadLink']);
    $this->assertEquals('READ', $messages[1]['messageStatus']);
  }

}
