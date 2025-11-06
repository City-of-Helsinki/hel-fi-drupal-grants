<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_yjdh\Unit\Plugin\DebugData;

use Drupal\helfi_yjdh\Plugin\DebugDataItem\ApiAvailability;
use Drupal\helfi_yjdh\YjdhClient;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\helfi_yjdh\Plugin\DebugDataItem\ApiAvailability
 * @group helfi_yjdh
 */
class ApiAvailabilityTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Gets the SUT.
   *
   * @param array $returnValue
   *   The expected return value for getCompany() call.
   *
   * @return \Drupal\helfi_yjdh\Plugin\DebugDataItem\ApiAvailability
   *   The SUT.
   */
  public function getSut(?array $returnValue): ApiAvailability {
    $client = $this->prophesize(YjdhClient::class);
    $client->getCompany(Argument::any(), Argument::any())
      ->willReturn($returnValue);

    return new ApiAvailability([], '', [], $client->reveal());
  }

  /**
   * Test successful check().
   */
  public function testCheck(): void {
    $this->assertTrue($this->getSut(['BusinessId' => '123'])->check());
  }

  /**
   * Tests failed check.
   */
  public function testFailedCheck(): void {
    $this->assertFalse($this->getSut(NULL)->check());
  }

}
