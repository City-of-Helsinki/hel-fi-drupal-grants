<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Language\ContextProvider\CurrentLanguageContext;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\EnvironmentResolverInterface;
use Drupal\helfi_atv\AtvAuthFailedException;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\Helper\JwtHelper;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Drupal\user\Entity\User;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for helfi_gdpr_api routes.
 */
class HelfiGdprApiController extends ControllerBase {

  /**
   * User jwt token decoded.
   *
   * @phpstan-var array<mixed>
   */
  protected array $jwtData;

  /**
   * User jwt token string.
   */
  protected string $jwtToken;

  /**
   * Audience configuration from db.
   *
   * @phpstan-var array{service_name: string, audience_host: string}
   */
  protected array $audienceConfig;

  public function __construct(
    protected RequestStack $request,
    protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
    protected AtvService $atvService,
    protected ClientInterface $httpClient,
    #[Autowire(service: 'language.current_language_context')]
    protected CurrentLanguageContext $currentLanguageContext,
    protected Connection $connection,
    private readonly EnvironmentResolverInterface $environmentResolver,
  ) {
    // @todo Fail if these variables are not set.
    $serviceName = getenv('GDPR_API_AUD_SERVICE') ?: '';
    $audienceHost = getenv('GDPR_API_AUD_HOST') ?: '';

    $this->audienceConfig = ['service_name' => $serviceName, 'audience_host' => $audienceHost];
    $this->parseJwt();
  }

  /**
   * Checks access for this controller.
   */
  public function access(string $userId): AccessResultInterface {

    $deniedReason = NULL;
    $decoded = NULL;

    try {
      $decoded = $this->helsinkiProfiiliUserData->verifyJwtToken($this->jwtToken);
      $this->log('GDPR Api access called. JWT token contents: @token', ['@token' => Json::encode($decoded)]);
    }
    catch (\InvalidArgumentException $e) {
      $deniedReason = $e->getMessage();
    }
    catch (\DomainException $e) {
      // Provided algorithm is unsupported OR
      // provided key is invalid OR
      // unknown error thrown in openSSL or libsodium OR
      // libsodium is required but not available.
      $deniedReason = $e->getMessage();
    }
    catch (SignatureInvalidException $e) {
      // Provided JWT signature verification failed.
      $deniedReason = $e->getMessage();
    }
    catch (BeforeValidException $e) {
      // Provided JWT is trying to be used before "nbf" claim OR
      // provided JWT is trying to be used before "iat" claim.
      $deniedReason = $e->getMessage();
    }
    catch (ExpiredException $e) {
      // Provided JWT is trying to be used after "exp" claim.
      $deniedReason = $e->getMessage();
    }
    catch (\UnexpectedValueException $e) {
      // Provided JWT is malformed OR
      // provided JWT is missing an algorithm / using an unsupported algorithm
      // provided JWT algorithm does not match provided key OR
      // provided key ID in key/key-array is empty or invalid.
      $deniedReason = $e->getMessage();
    }
    catch (GuzzleException $e) {
      // Generic guzzle exception.
      $deniedReason = $e->getMessage();
    }

    if ($decoded == NULL) {
      if ($deniedReason == NULL) {
        return AccessResult::forbidden('JWT verification failed.');
      }
      else {
        return AccessResult::forbidden($deniedReason);
      }
    }

    $audience = $decoded['aud'];
    $expectedAudience = $this->audienceConfig['service_name'];

    if ($decoded['sub'] !== $userId) {
      $this->log(
        'GDPR Api access failed: User ID mismatch - JWT value: @jwt Endpoint value: @endpoint',
        [
          '@jwt' => $decoded['sub'],
          '@endpoint' => $userId,
        ],
      );
      return AccessResult::forbidden('User ID mismatch');
    }

    // If audience does not match, forbid access.
    if ($audience != $expectedAudience) {
      $this->log(
        'Access DENIED. Reason: @reason. JWT token: @token',
        [
          '@token' => $this->jwtToken,
          '@reason' => 'Audience mismatch',
        ],
      );
      return AccessResult::forbidden('Audience mismatch');
    }

    $hostkey = match ($this->request->getCurrentRequest()->getMethod()) {
      'GET' => 'gdprquery',
      'DELETE' => 'gdprdelete',
      default => throw new BadRequestException('Unsupported method: ' . $this->request->getCurrentRequest()->getMethod()),
    };

    if (in_array($hostkey, $decoded['authorization']->permissions[0]->scopes)) {
      $this->log(
        'Local access GRANTED. Reason: @reason. JWT token: @token',
        [
          '@token' => $this->jwtToken,
          '@reason' => 'All match..',
        ],
      );
      return AccessResult::allowed();
    }

    return AccessResult::forbidden('Scope mismatch');
  }

  /**
   * Builds the response.
   *
   * @param string $userId
   *   User id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSONresponse.
   *
   * @throws \Drupal\helfi_atv\AtvAuthFailedException
   */
  public function get(string $userId): JsonResponse {

    // Decode the json data.
    try {
      $data = $this->getData();
      $statusCode = 200;
      if (empty($data)) {
        $data = NULL;
        $statusCode = 204;
      }
    }
    catch (AtvDocumentNotFoundException $e) {
      $data = NULL;
      $statusCode = 204;
    }
    catch (AtvFailedToConnectException | GuzzleException $e) {
      $data = NULL;
      $statusCode = 500;
    }
    catch (TokenExpiredException $e) {
      $data = NULL;
      $statusCode = 401;
    }

    return new JsonResponse($data, $statusCode);

  }

  /**
   * Builds the response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse.
   */
  public function delete(string $userId): JsonResponse {
    try {
      // Try to load user via openid / tunnistamo id.
      $authuid = $this->connection->select('authmap', 'am')
        ->fields('am', ['uid'])
        ->condition('authname', $userId)
        ->condition('provider', 'openid_connect.tunnistamo')
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if ($authuid) {
        // Try to load & delete user.
        $this->entityTypeManager()
          ->getStorage('user')
          ->load($authuid->uid)
          ?->delete();
      }

      $this->atvService->deleteGdprData($this->jwtData['sub'], $this->jwtToken);
      $statusCode = 204;

    }
    catch (AtvDocumentNotFoundException $e) {
      $statusCode = 404;
    }
    catch (AtvFailedToConnectException | GuzzleException $e) {
      $statusCode = 500;
    }
    catch (TokenExpiredException $e) {
      $statusCode = 401;
    }
    catch (EntityStorageException $e) {
      $statusCode = 204;
    }
    catch (AtvAuthFailedException $e) {
      $statusCode = 403;
    }

    return new JsonResponse(NULL, $statusCode);
  }

  /**
   * Parse jwt token data from token in request.
   */
  private function parseJwt(): void {

    $currentRequest = $this->request->getCurrentRequest();

    $authHeader = $currentRequest->headers->get('authorization');

    if (!$authHeader) {
      throw new AccessDeniedHttpException('No authorization header', NULL, 403);
    }

    $jwtToken = str_replace('Bearer ', '', $authHeader);
    $tokenData = JwtHelper::parseToken($jwtToken);
    $this->jwtData = $tokenData;
    $this->jwtToken = $jwtToken;
  }

  /**
   * Get user GDPR data from ATV api.
   *
   * @return array<mixed>
   *   User's GDPR data
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_atv\AtvAuthFailedException
   */
  private function getData(): array {

    $data = [];

    $user = $this->getUser();

    // If we have user, then add user data.
    if ($user) {
      $data[0] = [
        'key' => strtoupper($this->audienceConfig['service_name']) . '_USER',
        'label' => [
          'en' => 'Grant applications user',
          'fi' => $this->t('Grant applications user', [], ['langcode' => 'fi'])
            ->render(),
          'sv' => $this->t('Grant applications user', [], ['langcode' => 'sv'])
            ->render(),
        ],
        'children' => [
          [
            'key' => 'USER_ID',
            'label' => [
              'en' => 'User ID',
              'fi' => $this->t('User ID', [], ['langcode' => 'fi'])->render(),
              'sv' => $this->t('User ID', [], ['langcode' => 'sv'])->render(),
            ],
            'value' => $this->jwtData['sub'],
          ],
          [
            'key' => 'USERNAME',
            'label' => [
              'en' => 'Username',
              'fi' => $this->t('Username', [], ['langcode' => 'fi'])->render(),
              'sv' => $this->t('Username', [], ['langcode' => 'sv'])->render(),
            ],
            'value' => $user->getDisplayName(),
          ],
          [
            'key' => 'MAIL',
            'label' => [
              'en' => 'Email address',
              'fi' => $this->t('Email address', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Email address', [], ['langcode' => 'sv'])
                ->render(),
            ],
            'value' => $user->getEmail(),
          ],
          [
            'key' => 'CREATED',
            'label' => [
              'en' => 'User created',
              'fi' => $this->t('User created', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('User created', [], ['langcode' => 'sv'])
                ->render(),
            ],
            'value' => $user->getCreatedTime(),
          ],
          [
            'key' => 'CHANGED',
            'label' => [
              'en' => 'User updated',
              'fi' => $this->t('User updated', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('User updated', [], ['langcode' => 'sv'])
                ->render(),
            ],
            'value' => $user->getChangedTime(),
          ],
        ],
      ];
    }

    // Get data.
    $gdprData = $this->atvService->getGdprData($this->jwtData['sub'], $this->jwtToken);
    /** @var array<mixed> $gdprData */
    if ($gdprData["total_deletable"] == 0 && $gdprData["total_undeletable"] == 0) {
      return [];
    }

    // If we have data, then parse it.
    if ($gdprData) {

      $data[1] = [
        'key' => strtoupper($this->audienceConfig['service_name']),
        'label' => [
          'en' => 'Grant applications',
          'fi' => $this->t('Grant applications', [], ['langcode' => 'fi'])
            ->render(),
          'sv' => $this->t('Grant applications', [], ['langcode' => 'sv'])
            ->render(),
        ],
      ];

      foreach ($gdprData['documents'] as $metadoc) {
        $data[1]['children'][] = [
          [
            'key' => 'ID',
            'value' => $metadoc['id'],
            'formatting' => [
              'datatype' => 'string',
            ],
            'label' => [
              'en' => 'Document identifier',
              'fi' => $this->t('Document identifier', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Document identifier', [], ['langcode' => 'sv'])
                ->render(),
            ],
          ],
          [
            'key' => 'CREATED_AT',
            'value' => $metadoc['created_at'],
            'formatting' => [
              'datatype' => 'date',
            ],
            'label' => [
              'en' => 'Document creation time',
              'fi' => $this->t('Document creation time', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Document creation time', [], ['langcode' => 'sv'])
                ->render(),
            ],
          ],
          [
            'key' => 'USER_ID',
            'value' => $metadoc['user_id'],
            'formatting' => [
              'datatype' => 'string',
            ],
            'label' => [
              'en' => 'Document owner ID',
              'fi' => $this->t('Document owner ID', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Document owner ID', [], ['langcode' => 'sv'])
                ->render(),
            ],
          ],
          [
            'key' => 'TYPE',
            'value' => $metadoc['type'],
            'formatting' => [
              'datatype' => 'string',
            ],
            'label' => [
              'en' => 'Document type',
              'fi' => $this->t('Document type', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Document type', [], ['langcode' => 'sv'])
                ->render(),
            ],
          ],
          [
            'key' => 'DELETABLE',
            'value' => $metadoc['deletable'] ? 1 : 0,
            'formatting' => [
              'datatype' => 'integer',
            ],
            'label' => [
              'en' => 'Document deletable',
              'fi' => $this->t('Document deletable', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Document deletable', [], ['langcode' => 'sv'])
                ->render(),
            ],
          ],
          [
            'key' => 'ATTACHMENT_COUNT',
            'value' => $metadoc['attachment_count'],
            'formatting' => [
              'datatype' => 'integer',
            ],
            'label' => [
              'en' => 'Document type',
              'fi' => $this->t('Document type', [], ['langcode' => 'fi'])
                ->render(),
              'sv' => $this->t('Document type', [], ['langcode' => 'sv'])
                ->render(),
            ],
          ],
        ];
      }
    }

    return $data;
  }

  /**
   * Get user from database.
   *
   * @return \Drupal\user\Entity\User|null
   *   User or some other types.
   */
  private function getUser(): User|null {
    $query = $this->connection->select('users', 'u');
    $query->join('authmap', 'am', 'am.uid = u.uid');
    $res = $query
      ->fields('u', ['uid'])
      ->condition('am.authname', $this->jwtData['sub'])
      ->execute()
      ->fetchObject();

    $user = $this->entityTypeManager()
      ->getStorage('user')
      ->load($res->uid);

    assert(!$user || $user instanceof User);

    return $user;
  }

  /**
   * Logs messages.
   *
   * @param string $msg
   *   Message.
   * @param array<mixed> $options
   *   Options.
   */
  private function log(string $msg, array $options = []): void {
    if ($this->environmentResolver->getActiveEnvironmentName() === EnvironmentEnum::Prod->value) {

      // Hide sensitive values in production environment.
      if (isset($options['@token'])) {
        $options['@token'] = '<redacted>';
      }
    }

    $this->getLogger('helf_gdpr_api')->info($msg, $options);
  }

}
