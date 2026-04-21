<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Unit;

use Drupal\helfi_helsinki_profiili\Helper\Filters;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests HelsinkiProfiiliUserData class.
 */
#[Group('helfi_helsinki_profiili')]
class FiltersTest extends UnitTestCase {

  /**
   * Loads fixture json and returns it.
   *
   * @return array<mixed>
   *   JSON decoded array.
   */
  private function getFixture(string $file): array {
    $contents = file_get_contents(__DIR__ . '/../../../../fixtures/' . $file);
    $this->assertIsString($contents);
    return json_decode($contents, TRUE, JSON_THROW_ON_ERROR);
  }

  /**
   * Data provider for checkPrimaryFields tests.
   *
   * @phpstan-return array<mixed>
   */
  public static function checkPrimaryFieldsDataProvider(): array {
    return [
      'first primary node' => [
        'multiple_primaries.json',
        'primary@test.test',
        '+358111111111',
      ],
      'first node when no primary' => [
        'profile_data.json',
        'primary@test.test',
      ],
      'valid primary data unchanged' => [
        'profile_data_valid_primary.json',
        'primary@test.test',
        '+358000000000',
      ],
    ];
  }

  /**
   * Tests checkPrimaryFields with various fixture data.
   */
  #[DataProvider('checkPrimaryFieldsDataProvider')]
  public function testCheckPrimaryFields(string $fixture, string $expectedEmail, ?string $expectedPhone = NULL): void {
    $json = $this->getFixture($fixture);
    $data = Filters::checkPrimaryFields($json);

    $this->assertEquals($expectedEmail, $data['myProfile']['primaryEmail']['email']);
    if ($expectedPhone !== NULL) {
      $this->assertEquals($expectedPhone, $data['myProfile']['primaryPhone']['phone']);
    }
  }

  /**
   * Tests that data is filtered through XSS::filter.
   */
  public function testXssFiltering(): void {
    $json = $this->getFixture('xss.json');
    $filteredData = Filters::filterData($json);

    $this->assertEquals(
      $filteredData['myProfile']['verifiedPersonalInformation']['firstName'],
      'Nordea alert(1)'
    );
  }

}
