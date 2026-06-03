<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\grants_application\ApplicationService;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\User\GrantsProfile;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_events\EventsService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_av\AntivirusService;
use Drupal\helfi_helsinki_profiili\DTO\AuthenticationLevel;
use Drupal\helfi_helsinki_profiili\DTO\HelsinkiProfiiliUser;
use Drupal\Tests\grants_application\Trait\AtvDocumentTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\grants_application\ApplicationService
 *
 * @group grants_application
 */
final class ApplicationServiceTest extends KernelTestBase {

  use AtvDocumentTrait;

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
   * The application number.
   *
   * @var string
   */
  private string $copiedApplicationNumber = "KERNELTEST-058-0000002";

  /**
   * The business id.
   *
   * @var string
   */
  private string $businessId = 'qwertyui-1234-1234-1234-qweasdzxcrty';


  /**
   * The atv document.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  private AtvDocument $atvDocument;

  /**
   * The copied atv document.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  private AtvDocument $copiedAtvDocument;

  /**
   * Copied side document.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  private AtvDocument $copiedSideDocument;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['helfi_atv'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('application_submission');

    $config = $this->config('content_lock.settings')
      ->set('types.application_submission', ['*' => '*'])
      ->set('verbose', TRUE);
    $config->save();
    $this->installSchema('content_lock', 'content_lock');

    $request = new Request();
    $request->files->set('file',
      new UploadedFile(
        path: "/app/public/modules/custom/grants_application/tests/fixtures/test-file.txt",
        originalName: 'test-file.txt',
        mimeType: 'text/plain',
        error: \UPLOAD_ERR_OK,
        test: TRUE
      ),
    );

    $this->sideDocument = ATVDocument::create([
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
      'content' => [],
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'delete_after' => '2075-01-01',
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);

    $this->copiedSideDocument = ATVDocument::create([
      'id' => 'sidedocu2-1111-2222-3333-mentidabcdef',
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
      'content' => [],
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'delete_after' => '2075-01-01',
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);

    $this->applicationSubmission = ApplicationSubmission::create([
      'id' => 1,
      'uuid' => 'aaaaaaaa-1111-2222-3333-bbbcccdddeeee',
      'document_id' => 'bbbbbbbb-4444-5555-6666-fffggghhhiiijjj',
      'sub' => 'abcdefg-1234-5678-9012-hijklmnopqro',
      'business_id' => $this->businessId,
      'draft' => TRUE,
      'langcode' => 'fi',
      'application_type_id' => 58,
      'form_identifier' => 'liikunta_suunnistuskartta_avustu',
      'application_number' => $this->applicationNumber,
      'side_document_id' => 'sidedocu-1111-2222-3333-mentidabcdef',
      'created' => '1765430954',
      'changed' => '1765430954',
    ]);
    $this->applicationSubmission->save();

    $this->atvDocument = $this->getAtvDocument($this->applicationNumber);
    $this->copiedAtvDocument = $this->getAtvDocument($this->copiedApplicationNumber);
    $eventService = $this->createMock(EventsService::class);
    $eventService->expects($this->any())->method('logEvent')->withAnyParameters()->willReturn([]);

    $antiVirusService = $this->createMock(AntivirusService::class);
    $antiVirusService->expects($this->any())->method('scan')->willReturn(TRUE);

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->container->set('grants_events.events_service', $eventService);
    $this->container->set(AntivirusService::class, $antiVirusService);
    $this->container->set('request_stack', $requestStack);
  }

  /**
   * Test the copying.
   */
  public function testCreateCopy(): void {
    $grantsProfile = new GrantsProfile(['businessId' => 'qwertyui-1234-1234-1234-qweasdzxcrty']);

    $userInformationService = $this->createMock(UserInformationService::class);
    $userInformationService->expects($this->any())->method('getApplicantType')->willReturn('registered_community');
    $userInformationService->method('getGrantsProfileContent')->willReturn($grantsProfile);
    $userInformationService->method('getUserData')->willReturn(new HelsinkiProfiiliUser(sub: 'abcdefg-1234-5678-9012-hijklmnopqro', loa: AuthenticationLevel::Strong, name: 'Test User', given_name: 'Test', family_name: 'User', email: 'test@test.com'));
    $userInformationService->method('getSelectedCompany')->willReturn(
      ['identifier' => 'company-identifier', 'type' => 'registered_community']
    );
    $this->container->set(UserInformationService::class, $userInformationService);

    $helfiAtvService = $this->createMock(HelfiAtvService::class);
    $helfiAtvService->expects($this->any())->method('getDocument')->willReturn($this->atvDocument);
    $helfiAtvService->expects($this->any())->method('createAtvDocument')->willReturn($this->copiedAtvDocument);
    $helfiAtvService->expects($this->exactly(2))->method('saveNewDocument')->willReturnOnConsecutiveCalls(
      $this->copiedAtvDocument,
      $this->copiedSideDocument,
    );
    $helfiAtvService->expects($this->any())->method('createSideDocument')->willReturn($this->copiedSideDocument);
    $this->container->set(HelfiAtvService::class, $helfiAtvService);

    $numberService = $this->createMock(ApplicationNumberService::class);
    $numberService->expects($this->any())->method('createNewApplicationNumber')->willReturn($this->copiedApplicationNumber);
    $this->container->set(ApplicationNumberService::class, $numberService);

    $applicationService = $this->container->get(ApplicationService::class);
    $response = $applicationService->createCopy('liikunta_suunnistuskartta_avustu', $this->applicationNumber);

    $this->assertEquals($this->copiedApplicationNumber, $response['application_number']);
    $this->assertTrue(str_contains($response['redirect_url'], $this->copiedApplicationNumber));
  }

}
