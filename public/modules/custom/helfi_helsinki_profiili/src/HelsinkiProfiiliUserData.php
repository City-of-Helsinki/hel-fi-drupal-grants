<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\helfi_helsinki_profiili\Helper\JwtHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliExceptionEvent;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliOperationEvent;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Integrate HelsinkiProfiili data to Drupal User.
 *
 * There are two types of 'user data':
 *  - User profile data: Data fetched from profiili graphql endpoint.
 *  - `userData`: claim returned by authenticator. Stored in session,
 *     but not sure if this is read anywhere.
 */
class HelsinkiProfiiliUserData implements LoggerAwareInterface {

  use LoggerAwareTrait;

  public function __construct(
    private readonly OpenIDConnectSessionInterface $openidConnectSession,
    private readonly ClientInterface $httpClient,
    private readonly AccountProxyInterface $currentUser,
    private readonly RequestStack $requestStack,
    private readonly EntityTypeManagerInterface $entityManager,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly TimeInterface $time,
    private readonly ProfiiliApiClient $profiili,
  ) {
  }

  /**
   * Get user authentication level from suomifi / helsinkiprofile.
   *
   * @return string
   *   Authentication level to be tested.
   *
   * @todo When auth levels are set in HP, check that these match.
   */
  public function getAuthenticationLevel(): string {
    $authLevel = 'noAuth';

    $userData = $this->getUserData();

    if ($userData == NULL) {
      return $authLevel;
    }

    if ($userData['loa'] == 'substantial') {
      return 'strong';
    }
    if ($userData['loa'] == 'low') {
      return 'weak';
    }

    return $authLevel;
  }

  /**
   * Get user data from tempstore.
   *
   * Note: This has nothing to do with setUserData().
   *
   * @return array
   *   Userdata from tempstore.
   */
  public function getUserData(): array {
    $token = $this->openidConnectSession->retrieveIdToken();
    if (empty($token)) {
      return [];
    }

    return JwtHelper::parseToken($this->openidConnectSession->retrieveIdToken());
  }

  /**
   * Get access tokens from helsinki profiili.
   *
   * @return array|null
   *   Accesstokens or null.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function getApiAccessTokens(): ?array {
    // Access token to get api access tokens in next step.
    $accessToken = $this->openidConnectSession->retrieveAccessToken();

    if ($accessToken == NULL) {
      throw new TokenExpiredException('No token data available');
    }

    // Use access token to fetch profiili token from token service.
    return (array) $this->profiili->getHelsinkiProfiiliToken($accessToken);
  }

  /**
   * Get user profile data from tunnistamo.
   *
   * @param bool $refetch
   *   Non false value will bypass caching.
   *
   * @return array|null
   *   User profile data.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function getUserProfileData(bool $refetch = FALSE): ?array {
    // Access token to get api access tokens in next step.
    $accessToken = $this->openidConnectSession->retrieveAccessToken();

    if ($accessToken == NULL) {
      return NULL;
    }

    if (!$refetch) {
      $cacheData = $this->requestStack
        ->getCurrentRequest()
        ->getSession()
        ->get('myProfile');

      // Return cached value if available.
      if (!empty($cacheData)) {
        return $cacheData;
      }
    }

    try {
      $data = $this->profiili->getUserProfileData($accessToken);
    }
    catch (ProfiiliException $e) {
      $this->dispatchExceptionEvent($e);
      $this->logger->error($e->getMessage());

      if ($e instanceof ProfileDataException) {
        foreach ($e->errors as $error) {
          $this->logger->error('/userinfo endpoint threw errorcode %ecode: @error', [
            '%ecode' => $error['extensions']['code'] ?? 'unknown',
            '@error' => $error['message'] ?? 'N/A',
          ]);
        }
      }

      return NULL;
    }

    // We want to log if user data is accessed.
    $this->dispatchOperationEvent('PROFILE DATA FETCH');

    $this->logger->notice('User with UUID %drupal_uuid got their HelsinkiProfiili data form endpoint', [
      '%drupal_uuid' => $this->currentUser->getAccount()->get('uuid')->getString(),
    ]);

    // Set profile data to cache so that no need to fetch more data.
    $this->setToCache('myProfile', $data);

    return $data;
  }

  /**
   * Add item to cache.
   *
   * @param string $key
   *   Used key for caching.
   * @param array $data
   *   Cached data.
   */
  private function setToCache(string $key, array $data): void {
    $this->requestStack->getCurrentRequest()
      ->getSession()
      ->set($key, $data);
  }

  /**
   * Refresh tokens.
   *
   * @return false|array
   *   Tokens or false.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function refreshTokens(): false|array {
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $refresh_token = $session->get('openid_connect_refresh_token');

    $plugin_id = $this->requestStack->getCurrentRequest()
      ->getSession()
      ->get('openid_connect_plugin_id');

    $storage = $this->entityManager->getStorage('openid_connect_client');
    $entities = $storage->loadByProperties([
      'plugin' => 'tunnistamo',
      'id' => $plugin_id,
    ]);

    if (!isset($entities[$plugin_id])) {
      return FALSE;
    }

    /** @var \Drupal\helfi_tunnistamo\Plugin\OpenIDConnectClient\Tunnistamo $client */
    $client = $entities[$plugin_id];
    $configuration = $client->getPlugin()->getConfiguration();

    // Exchange a refresh token for new tokens.
    $endpoints = $this->profiili->getOpenIdConfiguration();
    $request_options = [
      'form_params' => [
        'refresh_token' => $refresh_token,
        'client_id' => $configuration['client_id'],
        'client_secret' => $configuration['client_secret'],
        'grant_type' => 'refresh_token',
      ],
      'headers' => [
        'Accept' => 'application/json',
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', $endpoints->token_endpoint, $request_options);
      $this->dispatchOperationEvent('TOKEN FETCH');
      $response_data = json_decode((string) $response->getBody(), TRUE);
      // Expected result.
      $tokens = [
        'id_token' => $response_data['id_token'] ?? NULL,
        'access_token' => $response_data['access_token'] ?? NULL,
      ];

      $this->openidConnectSession->saveIdToken($tokens['id_token']);
      $this->openidConnectSession->saveAccessToken($tokens['access_token']);

      if (array_key_exists('expires_in', $response_data)) {
        $tokens['expire'] = $this->time->getRequestTime() + $response_data['expires_in'];
        $session->set('openid_connect_expire', $tokens['expire']);
      }
      if (array_key_exists('refresh_token', $response_data)) {
        $tokens['refresh_token'] = $response_data['refresh_token'];
        $session->set('openid_connect_refresh_token', $response_data['refresh_token']);
      }
      return $tokens;
    }
    catch (\Exception $e) {
      $this->dispatchExceptionEvent($e);
      $variables = [
        '@message' => 'Could not refresh tokens',
        '@error_message' => $e->getMessage(),
      ];
      $this->logger->error(
        '@message: @error_message',
        $variables
      );
      return FALSE;
    }
  }

  /**
   * Verify JWT token.
   *
   * @param string $jwt
   *   JWT token.
   *
   * @return array<mixed>
   *   Is token valid or not.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function verifyJwtToken(string $jwt): array {
    $jwks = $this->profiili->getJsonWebKeySet();

    return (array) JWT::decode($jwt, JWK::parseKeySet($jwks));
  }

  /**
   * Dispatches exception event.
   *
   * @param \Throwable $exception
   *   The exception.
   */
  private function dispatchExceptionEvent(\Throwable $exception): void {
    $event = new HelsinkiProfiiliExceptionEvent($exception);
    $this->eventDispatcher->dispatch($event);
  }

  /**
   * Dispatches exception event.
   *
   * @param string $message
   *   The message.
   */
  private function dispatchOperationEvent(string $message): void {
    $event = new HelsinkiProfiiliOperationEvent($message);
    $this->eventDispatcher->dispatch($event);
  }

}
