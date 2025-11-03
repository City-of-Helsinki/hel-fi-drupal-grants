<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_atv\Unit\Plugin\DebugData;

use Drupal\helfi_atv\AtvService;
use Drupal\helfi_atv\Plugin\DebugDataItem\ApiAvailability;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\helfi_atv\Plugin\DebugDataItem\ApiAvailability
 * @group helfi_atv
 */
class ApiAvailabilityTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Gets the SUT.
   *
   * @param bool $returnValue
   *   The expected return value for ping.
   *
   * @return \Drupal\helfi_atv\Plugin\DebugDataItem\ApiAvailability
   *   The SUT.
   */
  public function getSut(bool $returnValue): ApiAvailability {
    $atvService = $this->prophesize(AtvService::class);
    $atvService->ping()->willReturn($returnValue);

    return new ApiAvailability([], '', [], $atvService->reveal());
  }

  /**
   * Test successful check().
   */
  public function testCheck(): void {
    $this->assertTrue($this->getSut(TRUE)->check());
  }

  /**
   * Tests failed check.
   */
  public function testFailedCheck(): void {
    $this->assertFalse($this->getSut(FALSE)->check());
  }

}
