<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Plugin\rest\resource;

use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Avus2Integration;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Mapper\JsonMapperService;
use Drupal\grants_application\User\GrantsProfile;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\RequestHandler;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\grants_application\Plugin\rest\resource\Application
 *
 * @group grants_application
 */
final class ApplicationTest extends KernelTestBase {

  /**
   * The application submission.
   *
   * @var \Drupal\grants_application\Entity\ApplicationSubmission
   */
  private ApplicationSubmission $applicationSubmission;

  /**
   * The application number.
   *
   * @var string
   */
  private string $applicationNumber = "KERNELTEST-058-0000001";

  /**
   * The side document id.
   *
   * @var string
   */
  private string $sideDocumentId = 'sidedocu-1111-2222-3333-mentidabcdef';

  /**
   * The request handler.
   *
   * @var \Drupal\rest\RequestHandler
   */
  protected RequestHandler $requestHandler;


  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'externalauth',
    'entity_reference_revisions',
    'grants_application',
    'grants_handler',
    'grants_profile',
    'grants_mandate',
    'grants_metadata',
    'grants_attachments',
    'grants_events',
    'helfi_yjdh',
    'helfi_audit_log',
    'locale',
    'language',
    'block',
    'block_content',
    'path_alias',
    'file',
    'field',
    'helfi_api_base',
    'helfi_atv',
    'helfi_helsinki_profiili',
    'helfi_tunnistamo',
    'node',
    'openid_connect',
    'options',
    'openid_connect_logout_redirect',
    'paragraphs',
    'system',
    'taxonomy',
    'text',
    'user',
    'webform',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('application_submission');
    $this->installSchema('webform', ['webform']);
    $this->installSchema('locale', [
      'locales_source',
      'locales_target',
      'locales_location',
    ]);
    $this->installEntitySchema('webform');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('paragraphs_type');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('block_content');

    $this->installConfig([
      'user',
      'externalauth',
      'grants_profile',
      'grants_mandate',
      'grants_metadata',
      'grants_attachments',
      'grants_events',
      'grants_handler',
      'helfi_yjdh',
      'helfi_audit_log',
      'locale',
      'language',
      'file',
      'field',
      'helfi_api_base',
      'helfi_atv',
      'helfi_tunnistamo',
      'openid_connect',
      'openid_connect_logout_redirect',
      'paragraphs',
      'system',
      'webform',
    ]);

    // $this->parameterBag = $this->createMock(ParameterBagInterface::class);
    // We have a dependency to anonymous user when checking menu permissions
    // and might run into 'entity:user' context is required error when trying
    // to generate an entity link.
    User::create([
      'name' => '',
      'uid' => 0,
    ])->save();

    Role::load('anonymous')
      ->grantPermission('restful post application_rest_resource')
      ->save();

    $config = $this->config('content_lock.settings')
      ->set('types.application_submission', ['*' => '*'])
      ->set('verbose', TRUE);
    $config->save();
    $this->installSchema('content_lock', 'content_lock');

    $this->installConfig(['rest']);
    $this->container->get('router.builder')->rebuild();

    $this->applicationSubmission = ApplicationSubmission::create([
      'id' => 1,
      'uuid' => 'aaaaaaaa-1111-2222-3333-bbbcccdddeee',
      'document_id' => 'bbbbbbbb-4444-5555-6666-fffggghhhiii',
      'sub' => '123345678-abcd-1234-ab12-abcdefgh',
      'business_id' => 'qwertyui-1234-1234-1234-qweasdzxcrty',
      'draft' => TRUE,
      'langcode' => 'fi',
      'application_type_id' => 58,
      'form_identifier' => 'liikunta_suunnistuskartta_avustu',
      'side_document_id' => 'sidedocu-1111-2222-3333-mentidabcdef',
      'application_number' => $this->applicationNumber,
      'created' => '1765430954',
      'changed' => '1765430954',
    ]);
    $this->applicationSubmission->save();

    $atvDocument = AtvDocument::create([
      'id' => 'test-id',
      'type' => 'type',
      'status' => [
        'value' => 'DRAFT',
      ],
      'status_histories' => [
        'DRAFT',
      ],
      'transaction_id' => '1234567890',
      'business_id' => '1234567-1',
      'tos_function_id' => '12345',
      'tos_record_id' => '54321',
      'draft' => TRUE,
      'human_readable_type' => ['humanType'],
      'metadata' => '{"name": "Name", "value": "Value"}',
      'content' => '{"compensation": {"events": {}}}',
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'delete_after' => '2075-01-01',
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);

    $sideDocument = ATVDocument::create([
      'id' => 'sidedocu-1111-2222-3333-mentidabcdef',
      'status' => [
        'value' => 'DRAFT',
      ],
      'status_histories' => [
        'DRAFT',
      ],
      'transaction_id' => '1234567890',
      'business_id' => '1234567-1',
      'tos_function_id' => '12345',
      'tos_record_id' => '54321',
      'draft' => TRUE,
      'human_readable_type' => ['humanType'],
      'metadata' => '{"name": "Name", "value": "Value"}',
      'content' => '{"data": "content"}',
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'delete_after' => '2075-01-01',
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);

    $applicationGetterService = $this->createMock(ApplicationGetterService::class);
    $applicationGetterService->expects($this->any())->method('getAtvDocument')->willReturn($atvDocument);

    $eventService = $this->createMock(EventsService::class);
    $eventService->expects($this->any())->method('logEvent')->withAnyParameters()->willReturn([]);

    $userData = json_decode(file_get_contents(__DIR__ . '/../../../../../fixtures/reactForm/commonDatasources.json'), TRUE);
    $userService = $this->createMock(UserInformationService::class);
    $userService->expects($this->any())->method('getGrantsProfileContent')->willReturn(new GrantsProfile($userData['grants_profile_array']));
    $userService->expects($this->any())->method('getSelectedCompany')->willReturn($userData['company']);
    $userService->expects($this->any())->method('getUserData')->willReturn($userData['user']);

    $helfiAtvService = $this->createMock(HelfiAtvService::class);
    $helfiAtvService->expects($this->any())->method('getDocument')->with($this->applicationNumber)->willReturn($atvDocument);
    $helfiAtvService->expects($this->any())->method('getDocumentById')->with($this->sideDocumentId)->willReturn($sideDocument);
    $helfiAtvService->expects($this->any())->method('updateExistingDocument')->with()->willReturn($atvDocument);
    $helfiAtvService->expects($this->any())->method('updateExistingDocument')->with()->willReturn($sideDocument);

    $jsonMapperService = $this->createMock(JsonMapperService::class);
    $jsonMapperService->expects($this->any())->method('getSelectedBankFile')->willReturn([]);
    $jsonMapperService->expects($this->any())->method('documentBankFileIsSet')->willReturn(TRUE);
    $jsonMapperService->expects($this->any())->method('handleMapping')->willReturn(
      json_decode(file_get_contents(__DIR__ . '/../../../../../fixtures/reactForm/form58-nofiles-result.json'), TRUE)
    );

    $integration = $this->createMock(Avus2Integration::class);
    $integration->expects($this->any())->method('sendToAvus2')->willReturn(TRUE);

    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $eventDispatcher->expects($this->any())->method('dispatch');

    $this->container->set('grants_handler.application_getter_service', $applicationGetterService);
    $this->container->set('grants_events.events_service', $eventService);
    $this->container->set(UserInformationService::class, $userService);

    $this->container->set(HelfiAtvService::class, $helfiAtvService);
    $this->container->set(JsonMapperService::class, $jsonMapperService);
    $this->container->set(Avus2Integration::class, $integration);
    $this->container->set(EventDispatcherInterface::class, $eventDispatcher);
  }

  /**
   * Test the file upload.
   */
  public function testPost(): void {
    $form_identifier = 'liikunta_suunnistuskartta_avustu';
    $content = json_encode([
      'form_data' => json_decode(file_get_contents(__DIR__ . '/../../../../../fixtures/reactForm/form58-nofiles-formdata.json'), TRUE),
    ]);

    $uri = "/applications/$form_identifier/application/$this->applicationNumber";
    $request = Request::create($uri, "POST", [], [], [], [], $content);
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');

    $http_kernel = $this->container->get('http_kernel');
    $response = $http_kernel->handle($request);

    $this->assertTrue($response instanceof JsonResponse && $response->isSuccessful());
  }

}

/**
 * Stub class where we can prophesize methods.
 */
class StubRequestHandlerResourcePlugin extends ResourceBase {

  /**
   * Handles a GET request.
   */
  public function get($example = NULL, ?Request $request = NULL) {}

  /**
   * Handles a POST request.
   */
  public function post() {}

  /**
   * Handles a PATCH request.
   */
  public function patch($data, Request $request) {}

  /**
   * Handles a DELETE request.
   */
  public function delete() {}

}
