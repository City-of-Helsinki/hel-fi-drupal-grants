<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\Hook\Hooks;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for Hooks.
 */
#[Group('helfi_helsinki_profiili')]
#[RunTestsInSeparateProcesses]
class HooksTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    $request = Request::create('/test');
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get(RequestStack::class)->push($request);
  }

  /**
   * Builds a minimal context array.
   *
   * @phpstan-param array<string, mixed> $overrides
   *
   * @return array<string, mixed>
   *   The context array.
   */
  private function buildContext(array $overrides = []): array {
    return array_replace_recursive([
      'plugin_id' => 'tunnistamo',
      'tokens' => [
        'id_token' => 'fake.jwt.token',
        'access_token' => 'fake-access-token',
        'refresh_token' => 'fake-refresh-token',
        'expire' => time() + 3600,
      ],
      'user_data' => [
        'sub' => 'test-sub',
      ],
      'sub' => 'test-sub',
    ], $overrides);
  }

  /**
   * Creates a Hooks instance with a mocked HelsinkiProfiiliUserData.
   */
  private function createHooks(?HelsinkiProfiiliUserData $userDataMock = NULL): Hooks {
    return new Hooks(
      $this->container->get(RequestStack::class),
      $userDataMock ?? $this->createMock(HelsinkiProfiiliUserData::class),
      $this->container->get(MessengerInterface::class),
      $this->createMock(LoggerInterface::class),
    );
  }

  /**
   * Tests preAuthorize hook.
   */
  public function testPreAuthorize(): void {
    $user = $this->createUser();
    $this->assertNotFalse($user);

    $hooks = $this->createHooks();

    // Tests that preAuthorize accepts a valid sub claim.
    $this->assertTrue($hooks->preAuthorize($user, $this->buildContext()));

    // Tests preAuthorize rejects a missing sub claim.
    $this->assertFalse($hooks->preAuthorize($user, $this->buildContext(['user_data' => ['sub' => '']])));

    $session = $this->container->get(RequestStack::class)->getSession();
    $this->assertEquals('<front>', $session->get('openid_connect_destination'));

  }

  /**
   * Tests that postAuthorize fetches profile data on success.
   */
  public function testPostAuthorizeFetchesProfileData(): void {
    $user = $this->createUser();
    $this->assertNotFalse($user);
    $this->setCurrentUser($user);

    $userDataMock = $this->createMock(HelsinkiProfiiliUserData::class);
    $userDataMock->expects($this->once())
      ->method('getUserProfileData')
      ->willReturn(['myProfile' => ['firstName' => 'Test']]);

    $hooks = $this->createHooks($userDataMock);
    $hooks->postAuthorize($user, $this->buildContext());

    $messenger = $this->container->get(MessengerInterface::class);
    $this->assertEmpty($messenger->messagesByType('error'));
    $this->assertNotEmpty($messenger->messagesByType('status'));
  }

}
