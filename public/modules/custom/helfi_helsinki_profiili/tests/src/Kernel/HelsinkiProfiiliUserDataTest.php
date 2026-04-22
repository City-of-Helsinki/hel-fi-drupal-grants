<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliExceptionEvent;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliOperationEvent;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\ProfiiliApiClient;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for HelsinkiProfiiliUserData.
 */
#[Group('helfi_helsinki_profiili')]
#[RunTestsInSeparateProcesses]
class HelsinkiProfiiliUserDataTest extends KernelTestBase {

  use ApiTestTrait;
  use EnvironmentResolverTrait;
  use UserCreationTrait;

  /**
   * Dispatched events captured during test.
   *
   * @var array<\Symfony\Contracts\EventDispatcher\Event>
   */
  private array $dispatchedEvents = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    // Push a request with a session so getUserProfileData can access it.
    $request = SymfonyRequest::create('/test');
    $request->setSession(new Session(new MockArraySessionStorage()));
    $this->container->get(RequestStack::class)->push($request);

    // Capture dispatched events.
    $dispatcher = $this->container->get(EventDispatcherInterface::class);
    $dispatcher->addListener(HelsinkiProfiiliExceptionEvent::class, function ($event) {
      $this->dispatchedEvents[] = $event;
    });
    $dispatcher->addListener(HelsinkiProfiiliOperationEvent::class, function ($event) {
      $this->dispatchedEvents[] = $event;
    });
  }

  /**
   * Helper to build an openid-configuration JSON response.
   */
  private function openIdConfigResponse(): Response {
    return new Response(200, body: json_encode([
      'token_endpoint' => 'https://tunnistamo.example.com/token',
      'jwks_uri' => 'https://tunnistamo.example.com/jwks',
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Helper to build a token endpoint JSON response.
   */
  private function tokenResponse(): Response {
    return new Response(200, body: json_encode([
      'access_token' => 'profiili-token',
      'expires_in' => 3600,
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Helper to build a profile data JSON response.
   */
  private function profileDataResponse(): Response {
    return new Response(200, body: json_encode([
      'data' => [
        'myProfile' => [
          'firstName' => 'Test',
          'lastName' => 'User',
          'primaryEmail' => ['email' => 'test@example.com', 'primary' => TRUE],
          'primaryPhone' => NULL,
          'primaryAddress' => NULL,
          'emails' => ['edges' => []],
          'phones' => ['edges' => []],
          'addresses' => ['edges' => []],
        ],
      ],
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Constructs the SUT with a real ProfiiliApiClient.
   *
   * @param array<Response|RequestException> $httpResponses
   *   Queued HTTP responses for the mock client.
   * @param string|null $accessToken
   *   The access token to return from the OpenID session.
   */
  private function getSut(
    array $httpResponses,
    ?string $accessToken = 'test-token',
  ): HelsinkiProfiiliUserData {
    $httpClient = $this->createMockHttpClient($httpResponses);

    // Set module config used by ProfiiliApiClient.
    $this->config('helfi_helsinki_profiili.settings')
      ->set('tunnistamo_environment_url', 'https://tunnistamo.example.com')
      ->set('userinfo_profile_endpoint', 'https://profile.example.com/graphql')
      ->save();

    $profiiliClient = new ProfiiliApiClient(
      new MemoryBackend($this->container->get(TimeInterface::class)),
      $httpClient,
      $this->container->get(ConfigFactoryInterface::class),
      $this->getEnvironmentResolver(Project::GRANTS, EnvironmentEnum::Test),
    );

    $sessionMock = $this->createMock(OpenIDConnectSessionInterface::class);
    $sessionMock->method('retrieveAccessToken')->willReturn($accessToken);

    $sut = new HelsinkiProfiiliUserData(
      $sessionMock,
      $httpClient,
      $this->container->get(AccountProxyInterface::class),
      $this->container->get(RequestStack::class),
      $this->container->get(EntityTypeManagerInterface::class),
      $this->container->get(EventDispatcherInterface::class),
      $this->container->get(TimeInterface::class),
      $profiiliClient,
    );

    $sut->setLogger($this->createMock(LoggerInterface::class));

    return $sut;
  }

  /**
   * Tests user profile data cache.
   */
  public function testCachedData(): void {
    $cachedData = ['myProfile' => ['firstName' => 'Cached']];

    $this->container->get('request_stack')
      ->getCurrentRequest()
      ->getSession()
      ->set('myProfile', $cachedData);

    $user = $this->createUser();
    $this->assertNotFalse($user);
    $this->setCurrentUser($user);

    $sut = $this->getSut([
      $this->openIdConfigResponse(),
      $this->tokenResponse(),
      $this->profileDataResponse(),
    ]);

    // Tests that cached data is returned without calling the API.
    $this->assertEquals($cachedData, $sut->getUserProfileData());
    $this->assertEmpty($this->dispatchedEvents);

    // Tests that cache is bypassed when refetch is TRUE.
    $result = $sut->getUserProfileData(refetch: TRUE);

    $this->assertNotEquals($cachedData, $result);
    $this->assertArrayHasKey('myProfile', $result);
    $this->assertEquals('Test', $result['myProfile']['firstName']);

    $this->assertCount(1, $this->dispatchedEvents);
    $this->assertInstanceOf(HelsinkiProfiiliOperationEvent::class, $this->dispatchedEvents[0]);
    $this->assertEquals('PROFILE DATA FETCH', $this->dispatchedEvents[0]->getName());
  }

  /**
   * Tests a successful profile data fetch end-to-end.
   */
  public function testSuccessfulFetch(): void {
    $user = $this->createUser();
    $this->assertNotFalse($user);
    $this->setCurrentUser($user);

    $sut = $this->getSut([
      $this->openIdConfigResponse(),
      $this->tokenResponse(),
      $this->profileDataResponse(),
    ]);

    $result = $sut->getUserProfileData();

    // Returns the fetched data.
    $this->assertIsArray($result);
    $this->assertArrayHasKey('myProfile', $result);
    $this->assertEquals('Test', $result['myProfile']['firstName']);
    $this->assertEquals('User', $result['myProfile']['lastName']);

    // Dispatches operation event.
    $this->assertCount(1, $this->dispatchedEvents);
    $this->assertInstanceOf(HelsinkiProfiiliOperationEvent::class, $this->dispatchedEvents[0]);
    $this->assertEquals('PROFILE DATA FETCH', $this->dispatchedEvents[0]->getName());

    // Caches result in session.
    $cached = $this->container->get(RequestStack::class)
      ->getCurrentRequest()
      ->getSession()
      ->get('myProfile');
    $this->assertEquals($result, $cached);
  }

  /**
   * Tests that a ProfiiliException is handled gracefully.
   */
  public function testProfiiliExceptionReturnsNull(): void {
    $sut = $this->getSut([
      $this->openIdConfigResponse(),
      $this->tokenResponse(),
      new RequestException('Server error', new Request('POST', '/graphql')),
    ]);

    $result = $sut->getUserProfileData();

    $this->assertNull($result);

    // Dispatches exception event.
    $this->assertCount(1, $this->dispatchedEvents);
    $this->assertInstanceOf(HelsinkiProfiiliExceptionEvent::class, $this->dispatchedEvents[0]);
  }

}
