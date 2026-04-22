<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_helsinki_profiili\DTO\OpenIdConfiguration;
use Drupal\helfi_helsinki_profiili\DTO\ProfiiliToken;
use Drupal\helfi_helsinki_profiili\Helper\Filters;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Profiili API client.
 *
 * There are two types of tokens:
 *  - Profiili token: Used to access profile graphql endpoint and maybe ATV.
 *  - Auth token: Given by OpenID when a user logs in.
 *
 * Profiili token can be fetched using the auth token.
 *
 * @internal
 */
final readonly class ProfiiliApiClient {

  private const string CACHE_KEY = 'helfi_helsinki_profiili';

  public function __construct(
    #[Autowire(service: 'cache.default')]
    private CacheBackendInterface $cache,
    private ClientInterface $client,
    private ConfigFactoryInterface $configFactory,
    private EnvironmentResolverInterface $environmentResolver,
  ) {
  }

  /**
   * Get openid configurations.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function getOpenIdConfiguration(): OpenIdConfiguration {
    if ($cache = $this->cache->get(self::CACHE_KEY . ':openid_configuration')) {
      return $cache->data;
    }

    // Cache miss. Fetch openid configuration.
    $url = $this->configFactory
      ->get('helfi_helsinki_profiili.settings')
      ->get('tunnistamo_environment_url');

    if (empty($url)) {
      throw new ProfiiliException('No tunnistamo environment url set');
    }

    try {
      $body = $this->client
        ->request('GET', "$url/.well-known/openid-configuration/")
        ->getBody()
        ->getContents();

      $parsed = Utils::jsonDecode($body, TRUE);
    }
    catch (GuzzleException $e) {
      throw new ProfiiliException('Failed to fetch openid configuration: ' . $e->getMessage(), previous: $e);
    }

    if (!is_array($parsed)) {
      throw new ProfiiliException('Could not parse openid configuration');
    }

    $config = new OpenIdConfiguration(...array_intersect_key($parsed, array_flip([
      'token_endpoint',
      'jwks_uri',
    ])));

    $this->cache->set(self::CACHE_KEY . ':openid_configuration', $config);

    return $config;
  }

  /**
   * Get JWKS from openid configuration.
   *
   * @return array<mixed>
   *   JWKS response.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function getJsonWebKeySet(): array {
    if ($cache = $this->cache->get(self::CACHE_KEY . ':jwks')) {
      return $cache->data;
    }

    $config = $this->getOpenIdConfiguration();

    try {
      $response = $this->client->request('GET', $config->jwks_uri);

      $jwks = Utils::jsonDecode($response->getBody()->getContents(), TRUE);
    }
    catch (GuzzleException $e) {
      throw new ProfiiliException('Failed to fetch jwks: ' . $e->getMessage(), previous: $e);
    }

    if (!is_array($jwks)) {
      throw new ProfiiliException('Could not parse jwks');
    }

    // Cache public keys for 10 minutes.
    $this->cache->set(self::CACHE_KEY . ':jwks', $jwks, time() + 6000);

    return $jwks;
  }

  /**
   * Fetch proper tokens from api-tokens endpoint.
   *
   * @todo If this is called on most requests, we should cache the token
   * to user session for faster lookups. The token response specifies the
   * expiration time.
   *
   * @param string $authToken
   *   Token from authorization service.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function getHelsinkiProfiiliToken(#[\SensitiveParameter] string $authToken): ProfiiliToken {
    $config = $this->getOpenIdConfiguration();

    try {
      $response = $this->client->request('POST', $config->token_endpoint, [
        'headers' => [
          'Authorization' => "Bearer $authToken",
        ],
        'form_params' => $this->getProfileTokenParams(),
      ]);

      $parsed = Utils::jsonDecode($response->getBody()->getContents(), TRUE);
    }
    catch (GuzzleException $e) {
      // If we resume failure, assume that the given token is expired.
      // This might not be the case (e.g., the service is down).
      throw new TokenExpiredException($e->getMessage(), previous: $e);
    }

    if (is_array($parsed)) {
      return new ProfiiliToken(...array_intersect_key($parsed, array_flip([
        'access_token',
        'expires_in',
      ])));
    }

    throw new ProfiiliException('Failed to fetch profile token');
  }

  /**
   * Get user profile data from tunnistamo.
   *
   * @return array<mixed>
   *   User profile data.
   *
   * @throws \Drupal\helfi_helsinki_profiili\ProfiiliException
   */
  public function getUserProfileData(#[\SensitiveParameter] string $authToken): array {
    $endpoint = $this->configFactory
      ->get('helfi_helsinki_profiili.settings')
      ->get('userinfo_profile_endpoint');

    if (empty($endpoint)) {
      throw new ProfiiliException('No profile endpoint set');
    }

    // Use access token to fetch profiili token from token service.
    $apiAccessToken = $this->getHelsinkiProfiiliToken($authToken);

    try {
      $response = $this->client->request('POST', $endpoint, [
        'headers' => [
          'Authorization' => "Bearer $apiAccessToken->access_token",
        ],
        'json' => [
          'query' => self::graphqlQuery(),
        ],
      ]);

      $body = Utils::jsonDecode($response->getBody()->getContents(), TRUE);
    }
    catch (GuzzleException $e) {
      throw new ProfiiliException(sprintf('/userinfo endpoint threw errorcode %d: %s', $e->getCode(), $e->getMessage()));
    }

    if (!is_array($body)) {
      throw new ProfiiliException('No profile data found');
    }

    if (isset($body['errors']) || empty($body['data'])) {
      throw new ProfileDataException('No profile data found', errors: $body['errors'] ?? []);
    }

    // Normalize and sanitize the return value.
    return Filters::checkPrimaryFields(Filters::filterData($body['data']));
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
   * Build query for profile.
   *
   * @return string
   *   Graphql query.
   */
  private static function graphqlQuery(): string {
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

}
