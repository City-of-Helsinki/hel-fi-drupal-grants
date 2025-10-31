<?php

declare(strict_types=1);

namespace Drupal\helfi_gdpr_api\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Language\ContextProvider\CurrentLanguageContext;
use Drupal\helfi_atv\AtvAuthFailedException;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for helfi_gdpr_api routes.
 */
class HelfiGdprApiController extends ControllerBase {

  /**
   * Profiili data access.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $request;

  /**
   * User jwt token decoded.
   *
   * @var array
   */
  protected array $jwtData;

  /**
   * User jwt token string.
   *
   * @var string
   */
  protected string $jwtToken;

  /**
   * Access to ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Translator for texts.
   *
   * @var \Drupal\Core\Language\ContextProvider\CurrentLanguageContext
   */
  protected CurrentLanguageContext $currentLanguageContext;

  /**
   * Db connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Audience configuration from db.
   *
   * @var array|mixed|null
   */
  protected array $audienceConfig;

  /**
   * Entitytype manager for users.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * DEbug or not?
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Is debug on?
   *
   * @return bool
   *   Debug on / off?
   */
  public function isDebug(): bool {
    return $this->debug;
  }

  /**
   * Set debug value.
   *
   * @param bool $debug
   *   True / False?
   */
  public function setDebug(bool $debug): void {
    $this->debug = $debug;
  }

  /**
   * CompanyController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Helsinki profile data access.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Atv access.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Drupal\Core\Language\ContextProvider\CurrentLanguageContext $currentLanguageContext
   *   Language.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database.
   * @param Drupal\user\UserStorageInterface $userStorage
   *   User storage.
   */
  public function __construct(
    RequestStack $request,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
    AtvService $atvService,
    ClientInterface $httpClient,
    CurrentLanguageContext $currentLanguageContext,
    Connection $connection,
    UserStorageInterface $userStorage
  ) {
    $this->request = $request;
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;
    $this->atvService = $atvService;
    $this->httpClient = $httpClient;
    $this->currentLanguageContext = $currentLanguageContext;
    $this->connection = $connection;
    $this->userStorage = $userStorage;

    $this->audienceConfig = [
      'service_name' => getenv('GDPR_API_AUD_SERVICE'),
      'audience_host' => getenv('GDPR_API_AUD_HOST'),
    ];

    $this->setDebug(getenv('DEBUG') == 'true' || getenv('DEBUG') == TRUE);
    $this->parseJwt();

    $this->debug('Audience config: @config', ['@config' => Json::encode($this->audienceConfig)]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('helfi_helsinki_profiili.userdata'),
      $container->get('helfi_atv.atv_service'),
      $container->get('http_client'),
      $container->get('language.current_language_context'),
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('user'),
    );
  }

  /**
   * Checks access for this controller.
   */
  public function access($userId): AccessResultForbidden|AccessResultAllowed {

    $deniedReason = NULL;
    $decoded = NULL;

    try {
      $this->debug('GDPR Api access called. JWT token: @token', ['@token' => $this->jwtToken], TRUE);
      $decoded = $this->helsinkiProfiiliUserData->verifyJwtToken($this->jwtToken);
      $this->debug('GDPR Api access called. JWT token contents: @token', ['@token' => Json::encode($decoded)], TRUE);
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
      $this->debug(
        'GDPR Api access failed: User ID mismatch - JWT value: @jwt Endpoint value: @endpoint',
        [
          '@jwt' => $decoded['sub'],
          '@endpoint' => $userId,
        ],
        TRUE
      );
      return AccessResult::forbidden('User ID mismatch');
    }

    // If audience does not match, forbid access.
    if ($audience != $expectedAudience) {
      $this->debug(
        'Access DENIED. Reason: @reason. JWT token: @token',
        [
          '@token' => $this->jwtToken,
          '@reason' => 'Audience mismatch',
        ],
        TRUE
      );
      return AccessResult::forbidden('Audience mismatch');
    }

    $hostkey = '';
    if ($this->request->getCurrentRequest()->getMethod() == 'GET') {
      $hostkey = 'gdprquery';
    }
    if ($this->request->getCurrentRequest()->getMethod() == 'DELETE') {
      $hostkey = 'gdprdelete';
    }

    if (in_array($hostkey, $decoded['authorization']->permissions[0]->scopes)) {
      $this->debug(
        'Local access GRANTED. Reason: @reason. JWT token: @token',
        [
          '@token' => $this->jwtToken,
          '@reason' => 'All match..',
        ],
        TRUE
      );
      return AccessResult::allowed();
    }
    else {
      $deniedReason = 'Scope mismatch';
    }

    // We should never reach here, but just return forbidden access.
    if ($deniedReason != NULL) {
      return AccessResult::forbidden($deniedReason);
    }
    else {
      return AccessResult::forbidden('Generic token parse error');
    }
  }

  /**
   * Builds the response.
   *
   * @param string $userId
   *   User id.
   *
   * @return \Drupal\Component\Serialization\JsonResponse
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
  public function delete($userId): JsonResponse {

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
        $user = $this->userStorage->load($authuid->uid);
        $user?->delete();
        // phpcs:enable
      }

      $this->atvService->deleteGdprData($this->jwtData['sub'], $this->jwtToken);
      $statusCode = 204;

    }
    catch (AtvDocumentNotFoundException $e) {
      $statusCode = 404;
    }
    catch (AtvFailedToConnectException $e) {
      $statusCode = 500;
    }
    catch (TokenExpiredException $e) {
      $statusCode = 401;
    }
    catch (GuzzleException $e) {
      $statusCode = 500;
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
  public function parseJwt(): void {

    $currentRequest = $this->request->getCurrentRequest();

    $authHeader = $currentRequest->headers->get('authorization');

    if (!$authHeader) {
      throw new AccessDeniedHttpException('No authorization header', NULL, 403);
    }

    $jwtToken = str_replace('Bearer ', '', $authHeader);
    $tokenData = $this->helsinkiProfiiliUserData->parseToken($jwtToken);
    $this->jwtData = $tokenData;
    $this->jwtToken = $jwtToken;
  }

  /**
   * Get user GDPR data from ATV api.
   *
   * @return array
   *   User's GDPR data
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_atv\AtvAuthFailedException
   */
  public function getData(): array {

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
   * @return \Drupal\Core\Entity\EntityBase|EntityInterface|User|null
   *   User or some other types.
   */
  public function getUser(): User|EntityBase|EntityInterface|null {
    $query = $this->connection->select('users', 'u',);
    $query->join('authmap', 'am', 'am.uid = u.uid');
    $query
      ->fields('u', ['uid'])
      ->condition('am.authname', $this->jwtData['sub']);
    $res = $query->execute()->fetchObject();

    $user = $this->userStorage->load($res->uid);
    return $user;
  }

  /**
   * Print to debug stream.
   *
   * @param string $msg
   *   Message.
   * @param array $options
   *   Options.
   * @param bool $sensitive
   *   Does the debug msg contain sensitive information?
   *   These will be removed in production environments.
   */
  private function debug(string $msg, array $options = [], $sensitive = FALSE) {
    if ($sensitive && $this->isProduction()) {
      $sensitiveValues = ['@jwt', '@token'];
      foreach ($sensitiveValues as $sensitiveValue) {
        if (isset($options[$sensitiveValue])) {
          $options[$sensitiveValue] = '<redacted>';
        }
      }
    }

    if ($this->isDebug()) {
      $this->getLogger('helf_gdpr_api')->debug($msg, $options);
    }
  }

  /**
   * Check if current environment is production.
   *
   * @return bool
   *   Returns true if the environment is production.
   */
  private function isProduction(): bool {
    $appEnv = getenv('APP_ENV');
    return in_array($appEnv, ['production', 'PRODUCTION', 'prod', 'PROD']);
  }

}
