<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_gdpr_api\Kernel\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\helfi_atv\AtvAuthFailedException;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_helsinki_profiili\Kernel\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;
use Firebase\JWT\SignatureInvalidException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the GDPR API controller.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_gdpr_api')]
class HelfiGdprApiControllerTest extends KernelTestBase {

  use ApiTestTrait;
  use UserCreationTrait;

  /**
   * The HelsinkiProfiiliUserData mock.
   */
  protected HelsinkiProfiiliUserData&MockObject $helsinkiProfiiliMock;

  /**
   * The AtvService mock.
   */
  protected AtvService&MockObject $atvServiceMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('externalauth', ['authmap']);
    $this->installSchema('user', ['users_data']);

    putenv('GDPR_API_AUD_SERVICE=test-service');
    putenv('GDPR_API_AUD_HOST=test-host');
    putenv('DEBUG=false');
    putenv('ATV_BASE_URL=http://localhost');
    putenv('ATV_VERSION=1.1');
    putenv('ATV_USE_CACHE=false');
    putenv('APP_ENV=UNIT_TEST');
    putenv('ATV_SERVICE=service');
    putenv('ATV_MAX_PAGES=10');
    putenv('ATV_API_KEY=fake');
    putenv('ATV_USE_TOKEN_AUTH=true');
    putenv('ATV_TOKEN_NAME=tokenName');

    $this->helsinkiProfiiliMock = $this->createMock(HelsinkiProfiiliUserData::class);
    $this->atvServiceMock = $this->createMock(AtvService::class);

    $this->container->set(HelsinkiProfiiliUserData::class, $this->helsinkiProfiiliMock);
    $this->container->set(AtvService::class, $this->atvServiceMock);
  }

  /**
   * Creates a request to the GDPR API endpoint with Authorization header.
   */
  private function createGdprRequest(
    string $userId,
    string $method = 'GET',
  ): Request {
    $request = $this->getMockedRequest("/helfi-gdpr-api/endpoint/$userId", $method);

    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
    $body = base64_encode(json_encode([
      'sub' => 'user-123',
      'aud' => 'test-service',
      'exp' => time() + 3600,
    ], JSON_THROW_ON_ERROR));
    $signature = base64_encode('fake-signature');

    $token = "$header.$body.$signature";
    $request->headers->set('Authorization', "Bearer $token");

    return $request;
  }

  /**
   * Configures the HelsinkiProfiili mock for JWT verification.
   *
   * @phpstan-param array<string> $scopes
   */
  private function configureJwtVerification(
    string $sub = 'user-123',
    string $aud = 'test-service',
    array $scopes = ['gdprquery', 'gdprdelete'],
    ?\Throwable $verifyException = NULL,
  ): void {
    if ($verifyException) {
      $this->helsinkiProfiiliMock
        ->method('verifyJwtToken')
        ->willThrowException($verifyException);
    }
    else {
      $permission = new \stdClass();
      $permission->scopes = $scopes;
      $authorization = new \stdClass();
      $authorization->permissions = [$permission];

      $this->helsinkiProfiiliMock
        ->method('verifyJwtToken')
        ->willReturn([
          'sub' => $sub,
          'aud' => $aud,
          'authorization' => $authorization,
        ]);
    }
  }

  /**
   * Creates a Drupal user with an authmap entry.
   */
  private function createTestUserWithAuthmap(string $authname): UserInterface {
    $user = $this->createUser([], 'testuser');
    $this->assertInstanceof(UserInterface::class, $user);
    $user->setEmail('test@example.com');
    $user->save();

    $this->container->get('database')->insert('authmap')
      ->fields([
        'uid' => $user->id(),
        'provider' => 'openid_connect.tunnistamo',
        'authname' => $authname,
      ])
      ->execute();

    return $user;
  }

  /**
   * Tests various access denied scenarios.
   */
  public function testAccessDeniedScenarios(): void {
    $cases = [
      'no Authorization header' => ['user-123', 'test-service', ['gdprquery', 'gdprdelete'], NULL, 'user-123', FALSE],
      'user ID mismatch' => ['user-123', 'test-service', ['gdprquery', 'gdprdelete'], NULL, 'different-user', TRUE],
      'audience mismatch' => ['user-123', 'wrong-service', ['gdprquery', 'gdprdelete'], NULL, 'user-123', TRUE],
      'GET without gdprquery scope' => ['user-123', 'test-service', ['gdprdelete'], NULL, 'user-123', TRUE],
      'invalid JWT signature' => [
        'user-123',
        'test-service',
        ['gdprquery', 'gdprdelete'],
        new SignatureInvalidException('Invalid signature'),
        'user-123',
        TRUE,
      ],
    ];

    foreach ($cases as $name => [$sub, $aud, $scopes, $verifyException, $requestUserId, $withAuth]) {
      $this->helsinkiProfiiliMock = $this->createMock(HelsinkiProfiiliUserData::class);
      $this->container->set(HelsinkiProfiiliUserData::class, $this->helsinkiProfiiliMock);
      $this->configureJwtVerification($sub, $aud, $scopes, $verifyException);

      $request = $withAuth
        ? $this->createGdprRequest($requestUserId)
        : $this->getMockedRequest("/helfi-gdpr-api/endpoint/$requestUserId");
      $response = $this->processRequest($request);

      $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode(), "Failed for case: $name");
    }
  }

  /**
   * Tests valid requests.
   */
  public function testGetWithValidData(): void {
    $this->configureJwtVerification();
    $this->createTestUserWithAuthmap('user-123');

    // Tests that a valid GET request with data returns 200.
    $this->atvServiceMock
      ->method('getGdprData')
      ->willReturn([
        'total_deletable' => 1,
        'total_undeletable' => 0,
        'documents' => [
          [
            'id' => 'doc-1',
            'created_at' => '2024-01-01T00:00:00Z',
            'user_id' => 'user-123',
            'type' => 'application',
            'deletable' => TRUE,
            'attachment_count' => 2,
          ],
        ],
      ]);

    $request = $this->createGdprRequest('user-123');
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

    $content = $response->getContent();
    $this->assertIsString($content);
    $data = json_decode($content, TRUE);
    $this->assertIsArray($data);
    // First element: user data.
    $this->assertEquals('TEST-SERVICE_USER', $data[0]['key']);
    $this->assertEquals('user-123', $data[0]['children'][0]['value']);
    // Second element: document data.
    $this->assertEquals('TEST-SERVICE', $data[1]['key']);
    $this->assertCount(1, $data[1]['children']);

    // Tests that a valid GET request with empty data returns 204.
    $atvMock = $this->createMock(AtvService::class);
    $atvMock->method('getGdprData')
      ->willReturn([
        'total_deletable' => 0,
        'total_undeletable' => 0,
        'documents' => [],
      ]);
    $this->container->set(AtvService::class, $atvMock);

    $request = $this->createGdprRequest('user-123');
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
  }

  /**
   * Tests GET request exception handling.
   */
  public function testGetExceptionHandling(): void {
    $this->configureJwtVerification();
    $this->createTestUserWithAuthmap('user-123');

    $cases = [
      'document not found' => [new AtvDocumentNotFoundException('Not found'), Response::HTTP_NO_CONTENT],
      'connection failure' => [
        new AtvFailedToConnectException('Connection failed'), Response::HTTP_INTERNAL_SERVER_ERROR,
      ],
      'token expired' => [new TokenExpiredException('Token expired'), Response::HTTP_UNAUTHORIZED],
    ];

    foreach ($cases as $name => [$exception, $expectedStatus]) {
      $atvMock = $this->createMock(AtvService::class);
      $atvMock->method('getGdprData')->willThrowException($exception);
      $this->container->set(AtvService::class, $atvMock);

      $request = $this->createGdprRequest('user-123');
      $response = $this->processRequest($request);

      $this->assertEquals($expectedStatus, $response->getStatusCode(), "Failed for case: $name");
    }
  }

  /**
   * Tests that a valid DELETE request returns 204 and deletes the user.
   */
  public function testDeleteWithUserReturns204(): void {
    $this->configureJwtVerification();
    $user = $this->createTestUserWithAuthmap('user-123');
    $uid = $user->id();

    $this->atvServiceMock
      ->method('deleteGdprData')
      ->willReturn([]);

    $request = $this->createGdprRequest('user-123', 'DELETE');
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    // Verify user was deleted.
    $userStorage = $this->container
      ->get(EntityTypeManagerInterface::class)
      ->getStorage('user');

    $userStorage->resetCache([$uid]);
    $this->assertNull($userStorage->load($uid));
  }

  /**
   * Tests DELETE request exception handling.
   */
  public function testDeleteExceptionHandling(): void {
    $this->configureJwtVerification();

    $cases = [
      'document not found' => [new AtvDocumentNotFoundException('Not found'), Response::HTTP_NOT_FOUND],
      'auth failed' => [new AtvAuthFailedException('Auth failed'), Response::HTTP_FORBIDDEN],
      'token expired' => [new TokenExpiredException('Token expired'), Response::HTTP_UNAUTHORIZED],
      'connection failure' => [
        new AtvFailedToConnectException('Connection failed'), Response::HTTP_INTERNAL_SERVER_ERROR,
      ],
    ];

    foreach ($cases as $name => [$exception, $expectedStatus]) {
      $atvMock = $this->createMock(AtvService::class);
      $atvMock->method('deleteGdprData')->willThrowException($exception);
      $this->container->set(AtvService::class, $atvMock);

      $request = $this->createGdprRequest('user-123', 'DELETE');
      $response = $this->processRequest($request);

      $this->assertEquals($expectedStatus, $response->getStatusCode(), "Failed for case: $name");
    }
  }

}
