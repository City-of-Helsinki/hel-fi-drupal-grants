<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\openid_connect\OpenIDConnectSession;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests HelsinkiProfiiliUserData class.
 */
#[Group('helfi_helsinki_profiili')]
class HelsinkiProfiiliUserDataTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Helper method to return fresh instance of HelsinkiProfiiliUserData.
   */
  public function getService(): HelsinkiProfiiliUserData {

    $configFactory = $this->getConfigFactoryStub([
      'helfi_helsinki_profiili.settings' => ['roles' => []],
    ]);
    $service = new HelsinkiProfiiliUserData(
      $this->prophesize(OpenIDConnectSession::class)->reveal(),
      $this->prophesize(ClientInterface::class)->reveal(),
      $this->prophesize(AccountProxyInterface::class)->reveal(),
      $this->prophesize(RequestStack::class)->reveal(),
      $this->prophesize(EnvironmentResolverInterface::class)->reveal(),
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
      $this->prophesize(EventDispatcherInterface::class)->reveal(),
      $configFactory,
      $this->prophesize(TimeInterface::class)->reveal(),
    );
    return $service;
  }

  /**
   * Loads fixture json and returns it.
   *
   * @return array<mixed>
   *   JSON decoded array.
   */
  private function getFixture(string $file): array {
    $contents = file_get_contents(__DIR__ . '/../../../fixtures/' . $file);
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
    $service = $this->getService();
    $data = $service->checkPrimaryFields($json);

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
    $service = $this->getService();
    $filteredData = $service->filterData($json);

    $this->assertEquals(
      $filteredData['myProfile']['verifiedPersonalInformation']['firstName'],
      'Nordea alert(1)'
    );
  }

}
