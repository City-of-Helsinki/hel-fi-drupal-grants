<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Xss;
use Drupal\helfi_helsinki_profiili\Helper\JwtHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliExceptionEvent;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliOperationEvent;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Utils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Integrate HelsinkiProfiili data to Drupal User.
 */
class HelsinkiProfiiliUserData implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Store details about oidc issuer.
   *
   * @phpstan-var array<mixed>
   */
  private array $openIdConfiguration = [];

  public function __construct(
    private readonly OpenIDConnectSessionInterface $openidConnectSession,
    private readonly ClientInterface $httpClient,
    private readonly AccountProxyInterface $currentUser,
    private readonly RequestStack $requestStack,
    private readonly EnvironmentResolverInterface $environmentResolver,
    private readonly EntityTypeManagerInterface $entityManager,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
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
   * Set user data to private store.
   *
   * @param array $userData
   *   Userdata retrieved from HP.
   */
  public function setUserData(array $userData): void {
    $this->setToCache('userData', $userData);
  }

  /**
   * Get user data from tempstore.
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
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getApiAccessTokens(): ?array {
    // Access token to get api access tokens in next step.
    $accessToken = $this->openidConnectSession->retrieveAccessToken();

    if ($accessToken == NULL) {
      throw new TokenExpiredException('No token data available');
    }

    // Use access token to fetch profiili token from token service.
    return $this->getHelsinkiProfiiliToken($accessToken);
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
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
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

    // End point to access profile data.
    $endpoint = $this->configFactory
      ->get('helfi_helsinki_profiili.settings')
      ->get('userinfo_profile_endpoint');

    try {
      // Use access token to fetch profiili token from token service.
      $apiAccessToken = $this->getHelsinkiProfiiliToken($accessToken);

      $headers = [
        'Content-Type' => 'application/json',
      ];
      // Use api access token if set, if not, return NULL.
      if (isset($apiAccessToken['access_token'])) {
        $headers['Authorization'] = 'Bearer ' . $apiAccessToken["access_token"];
      }
      else {
        // No point going further if no token is received.
        return NULL;
      }

      // Get profiili data with api access token.
      $response = $this->httpClient->request('POST', $endpoint, [
        'headers' => $headers,
        'json' => [
          'query' => self::graphqlQuery(),
        ],
      ]);
      $this->dispatchOperationEvent('PROFILE DATA FETCH');

      $json = $response->getBody()->getContents();
      $body = Utils::jsonDecode($json, TRUE);
      /** @var array<string, mixed> $body */
      $data = $body['data'];

      if (!empty($body['errors'])) {
        foreach ($body['errors'] as $error) {
          $this->logger->error(
            '/userinfo endpoint threw errorcode %ecode: @error',
            [
              '%ecode' => $error['extensions']['code'],
              '@error' => $error['message'],
            ]
          );
        }
        throw new ProfileDataException('No profile data found');
      }
      else {
        $this->logger->notice('User with UUID %drupal_uuid got their HelsinkiProfiili data form endpoint', [
          '%drupal_uuid' => $this->currentUser->getAccount()->get('uuid')->getString(),
        ]);
      }

      $filteredData = $this->filterData($data);
      $modifiedData = $this->checkPrimaryFields($filteredData);

      // Set profile data to cache so that no need to fetch more data.
      $this->setToCache('myProfile', $modifiedData);
      return $modifiedData;
    }
    catch (ClientException | ServerException $e) {
      $this->dispatchExceptionEvent($e);
      $this->logger->error(
        '/userinfo endpoint threw errorcode %ecode: @error',
        [
          '%ecode' => $e->getCode(),
          '@error' => $e->getMessage(),
        ]
      );
    }
    catch (TempStoreException $e) {
      $this->dispatchExceptionEvent($e);
      $this->logger->error(
        'Caching userprofile data failed',
        [
          '%ecode' => $e->getCode(),
          '@error' => $e->getMessage(),
        ]
      );
    }
    catch (GuzzleException $e) {
      $this->dispatchExceptionEvent($e);
    }
    catch (ProfileDataException $e) {
      $this->dispatchExceptionEvent($e);
      $this->logger->error(
        $e->getMessage()
      );
    }

    return NULL;
  }

  /**
   * Get query params for profile token.
   *
   * Dev/test use api-test, others per env.
   *
   * @return string[]
   *   String array containing token parameters.
   */
  private function getProfileTokenParams(): array {
    $endpointAudience = match ($this->environmentResolver->getActiveEnvironmentName()) {
      EnvironmentEnum::Prod->value => 'profile-api',
      EnvironmentEnum::Stage->value => 'profile-api-stage',
      default => 'profile-api-test',
    };

    return [
      'audience' => $endpointAudience,
      'grant_type' => 'urn:ietf:params:oauth:grant-type:uma-ticket',
      'permission' => '#access',
    ];
  }

  /**
   * Fetch proper tokens from api-tokens endopoint.
   *
   * @param string $accessToken
   *   Token from authorization service.
   *
   * @return array|null
   *   Token data
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  private function getHelsinkiProfiiliToken(string $accessToken): ?array {
    try {
      $oid_config = $this->getOpenIdConfiguration();

      $response = $this->httpClient->request('POST', $oid_config["token_endpoint"], [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
        ],
        'form_params' => $this->getProfileTokenParams(),
      ]);

      $parsed = Utils::jsonDecode($response->getBody()->getContents(), TRUE);

      if (!empty($parsed) && is_array($parsed)) {
        return $parsed;
      }

      // Should we throw something here?
      $this->dispatchExceptionEvent(new ProfileDataException('No data from profile endpoint'));
      $this->logger->error('Trying to get tokens from api-tokens endpoint, got invalid body: @body');
      return NULL;
    }
    catch (GuzzleException $e) {
      $this->dispatchExceptionEvent($e);
      $this->logger->error(
        'Error retrieving access token %ecode: @error',
        [
          '%ecode' => $e->getCode(),
          '@error' => $e->getMessage(),
        ]
      );

      throw new TokenExpiredException($e->getMessage(), previous: $e);
    }
  }

  /**
   * Build query for profile.
   *
   * @return string
   *   Graphql query.
   */
  protected static function graphqlQuery(): string {
    return <<<'GRAPHQL'
      query MyProfileQuery {
        myProfile {
          id
          firstName
          lastName
          nickname
          language
          primaryAddress {
            id
            primary
            address
            postalCode
            city
            countryCode
            addressType
          }
          addresses {
            edges {
              node {
                primary
                id
                address
                postalCode
                city
                countryCode
                addressType
              }
            }
          }
          primaryEmail {
            id
            email
            primary
            emailType
          }
          emails {
            edges {
              node {
                primary
                id
                email
                emailType
              }
            }
          }
          primaryPhone {
            id
            phone
            primary
            phoneType
          }
          phones {
            edges {
              node {
                primary
                id
                phone
                phoneType
              }
            }
          }
          verifiedPersonalInformation {
            firstName
            lastName
            givenName
            nationalIdentificationNumber
            municipalityOfResidence
            municipalityOfResidenceNumber
            permanentAddress {
              streetAddress
              postalCode
              postOffice
            }
            temporaryAddress {
              streetAddress
              postalCode
              postOffice
            }
            permanentForeignAddress {
              streetAddress
              additionalAddress
              countryCode
            }
          }
        }
      }
      GRAPHQL;
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
   * Fill primaryPhone field from edge nodes, if it is missing.
   *
   * @param array $data
   *   Data array.
   *
   * @return array
   *   Modified array
   */
  public function checkPrimaryFields(array $data): array {
    static $fieldMapping = [
      'phone' => [
        'primary_field_key' => 'primaryPhone',
        'field_key' => 'phones',
      ],
      'email' => [
        'primary_field_key' => 'primaryEmail',
        'field_key' => 'emails',
      ],
      'address' => [
        'primary_field_key' => 'primaryAddress',
        'field_key' => 'addresses',
      ],
    ];

    foreach ($fieldMapping as $mapping) {
      [
        'primary_field_key' => $primaryFieldKey,
        'field_key' => $fieldKey,
      ] = $mapping;

      $primaryField = $data['myProfile'][$primaryFieldKey];
      if ($primaryField === NULL) {
        /*
         * Loop the edges. Get first node with verified flag, or
         * the first edge if none is verified.
         */
        foreach ($data['myProfile'][$fieldKey]['edges'] as $edge) {
          if ($edge['node']['primary']) {
            $primaryField = $edge['node'];
            break;
          }
        }

        // No primary flagged. Try to get first edge number.
        if ($primaryField === NULL) {
          $primaryField = $data['myProfile'][$fieldKey]['edges'][0]['node'] ?? NULL;
        }

        // If we have a edge, let's add it to the data array.
        if ($primaryField !== NULL) {
          $data['myProfile'][$primaryFieldKey] = $primaryField;
        }
      }
    }

    return $data;
  }

  /**
   * Runs the array items through Xss::filter function.
   *
   * @param array $data
   *   Input array.
   *
   * @return array
   *   Filtered data.
   */
  public function filterData(array $data): array {
    // Make sure that data coming from HP is sanitized and does not contain
    // anything worth removing.
    array_walk_recursive(
      $data,
      function (&$item) {
        if (is_string($item)) {
          $item = Xss::filter($item);
        }
      }
    );

    return $data;
  }

  /**
   * Get openid configurations.
   *
   * @todo We should cache this response.
   *
   * @return array<mixed>
   *   Open id config from endpoint.
   *
   * @todo Improve exception type.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getOpenIdConfiguration(): array {
    if (!$this->openIdConfiguration) {
      $url = $this->configFactory
        ->get('helfi_helsinki_profiili.settings')
        ->get('tunnistamo_environment_url');

      if (empty($url)) {
        throw new \UnexpectedValueException('No tunnistamo environment url set');
      }

      $response = $this->httpClient->request('GET', "$url/.well-known/openid-configuration/");

      $parsed = Utils::jsonDecode($response->getBody()->getContents(), TRUE);
      if (empty($parsed) || !is_array($parsed)) {
        throw new \UnexpectedValueException('Could not parse openid configuration');
      }

      $this->openIdConfiguration = $parsed;
    }

    return $this->openIdConfiguration;
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
    $endpoints = $this->getOpenIdConfiguration();
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
      $response = $this->httpClient->request('POST', $endpoints['token_endpoint'], $request_options);
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
   * @return array
   *   Is token valid or not.
   *
   * @todo Improve exception type.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function verifyJwtToken(string $jwt): array {
    $config = $this->getOpenIdConfiguration();

    $response = $this->httpClient->request(
      'GET',
      $config["jwks_uri"]
    );

    $jwks = Utils::jsonDecode($response->getBody()->getContents(), TRUE);
    if (!is_array($jwks)) {
      throw new \UnexpectedValueException('Could not parse jwks');
    }

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
