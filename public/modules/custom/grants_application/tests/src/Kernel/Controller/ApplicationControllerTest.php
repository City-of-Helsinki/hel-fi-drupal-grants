<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Controller;

use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Controller\ApplicationController;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\User\GrantsProfile;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_av\AntivirusService;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\grants_application\Controller\ApplicationController
 *
 * @group grants_application
 */
final class ApplicationControllerTest extends KernelTestBase {

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
   * The atv document.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  private AtvDocument $atvDocument;

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

    $this->applicationSubmission = ApplicationSubmission::create([
      'id' => 1,
      'uuid' => 'aaaaaaaa-1111-2222-3333-bbbcccdddeeee',
      'document_id' => 'bbbbbbbb-4444-5555-6666-fffggghhhiiijjj',
      'sub' => 'abcdefg-1234-5678-9012-hijklmnopqro',
      'business_id' => 'qwertyui-1234-1234-1234-qweasdzxcrty',
      'draft' => TRUE,
      'langcode' => 'fi',
      'application_type_id' => 58,
      'form_identifier' => 'liikunta_suunnistuskartta_avustu',
      'application_number' => $this->applicationNumber,
      'created' => '1765430954',
      'changed' => '1765430954',
    ]);
    $this->applicationSubmission->save();

    $this->atvDocument = AtvDocument::create([
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
    $applicationGetterService->expects($this->any())->method('getAtvDocument')->willReturn($this->atvDocument);

    $eventService = $this->createMock(EventsService::class);
    $eventService->expects($this->any())->method('logEvent')->withAnyParameters()->willReturn([]);

    $helfiAtvService = $this->createMock(HelfiAtvService::class);
    $helfiAtvService->expects($this->any())->method('addAttachment')->willReturn([
      'filename' => 'test-file.txt',
      'id' => '9595',
      'href' => 'testhref.example.com/file/9595',
      'size' => '999',
    ]);

    $atvService = $this->createMock(AtvService::class);
    $atvService->expects($this->any())->method('deleteAttachment')->willReturn(TRUE);

    $antiVirusService = $this->createMock(AntivirusService::class);
    $antiVirusService->expects($this->any())->method('scan')->willReturn(TRUE);

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->container->set('grants_handler.application_getter_service', $applicationGetterService);
    $this->container->set('grants_events.events_service', $eventService);
    $this->container->set(HelfiAtvService::class, $helfiAtvService);
    $this->container->set('helfi_atv.atv_service', $atvService);
    $this->container->set(AntivirusService::class, $antiVirusService);
    $this->container->set('request_stack', $requestStack);
  }

  /**
   * Test the file upload.
   */
  public function testFileUpload(): void {
    $userInformationService = $this->createMock(UserInformationService::class);
    $userInformationService->expects($this->any())->method('getUserData')
      ->willReturn(['sub' => 'abcdefg-1234-5678-9012-hijklmnopqro']);
    $this->container->set(UserInformationService::class, $userInformationService);

    $controller = ApplicationController::create($this->container);
    $request = $this->container->get('request_stack')->getCurrentRequest();

    $response = $controller->uploadFile($this->applicationNumber, $request);
    $responseData = json_decode($response->getContent(), TRUE);
    $this->assertArrayNotHasKey('error', $responseData);
    $this->assertArrayHasKey('href', $responseData);
    $this->assertEquals('testhref.example.com/file/9595', $responseData['href']);
  }

  /**
   * Test file remove.
   */
  public function testFileDelete(): void {
    $controller = ApplicationController::create($this->container);
    $response = $controller->removeFile($this->applicationNumber, '9595');

    $errors = $this->container->get('messenger')->messagesByType('error');
    $this->assertCount(0, $errors);
    $this->assertTrue($response->getStatusCode() === 200);
  }

  /**
   * Test application removal.
   */
  public function testRemoveApplication(): void {
    $controller = ApplicationController::create($this->container);
    $controller->removeApplication($this->applicationNumber);

    $errors = $this->container->get('messenger')->messagesByType('error');
    $this->assertCount(0, $errors);
  }

  /**
   * Test application print route throws when user info service fails.
   */
  public function testPrintApplication(): void {
    $this->expectException(NotFoundHttpException::class);
    $controller = ApplicationController::create($this->container);
    $controller->printApplication($this->applicationNumber);
  }

  /**
   * Test print route throws when no matching submission is found.
   */
  public function testPrintApplicationNoSubmissionThrowsNotFound(): void {
    $grantsProfile = new GrantsProfile(['businessId' => 'no-match']);
    $userInformationService = $this->createMock(UserInformationService::class);
    $userInformationService->method('getUserData')->willReturn(['sub' => 'no-match-sub']);
    $userInformationService->method('getGrantsProfileContent')->willReturn($grantsProfile);
    $this->container->set(UserInformationService::class, $userInformationService);

    $this->expectException(NotFoundHttpException::class);
    $controller = ApplicationController::create($this->container);
    $controller->printApplication($this->applicationNumber);
  }

  /**
   * Test print route returns render array on happy path.
   */
  public function testPrintApplicationReturnsRenderArray(): void {
    // Match the sub/business_id of the saved entity so access is granted.
    $grantsProfile = new GrantsProfile(['businessId' => 'qwertyui-1234-1234-1234-qweasdzxcrty']);
    $userInformationService = $this->createMock(UserInformationService::class);
    $userInformationService->method('getUserData')->willReturn(['sub' => 'abcdefg-1234-5678-9012-hijklmnopqro']);
    $userInformationService->method('getGrantsProfileContent')->willReturn($grantsProfile);
    $this->container->set(UserInformationService::class, $userInformationService);

    $formSettings = new FormSettings(
      ['title' => 'Test Grant', 'application_type_id' => 58],
      [],
      [],
      [],
    );
    $formSettingsService = $this->createMock(FormSettingsServiceInterface::class);
    $formSettingsService->method('getFormSettingsByFormIdentifier')->willReturn($formSettings);
    $this->container->set(FormSettingsServiceInterface::class, $formSettingsService);

    $document = AtvDocument::create([
      'id' => 'print-doc-id',
      'type' => 'type',
      'status' => 'SUBMITTED',
      'status_histories' => [
        ['value' => 'SUBMITTED', 'timestamp' => '2024-06-01T10:00:00'],
      ],
      'transaction_id' => '1234567890',
      'business_id' => '1234567-1',
      'tos_function_id' => '12345',
      'tos_record_id' => '54321',
      'draft' => FALSE,
      'human_readable_type' => ['humanType'],
      'metadata' => '{}',
      'content' => '{}',
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'delete_after' => '2075-01-01',
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);
    $helfiAtvService = $this->createMock(HelfiAtvService::class);
    $helfiAtvService->method('getDocument')->willReturn($document);
    $this->container->set(HelfiAtvService::class, $helfiAtvService);

    $controller = ApplicationController::create($this->container);
    $result = $controller->printApplication($this->applicationNumber);

    $this->assertSame('grants_application_print', $result['#theme']);
    $this->assertSame($this->applicationNumber, $result['#submission_id']);
    $this->assertSame('Test Grant', $result['#title']);
    $this->assertNotEmpty($result['#status']);
    $this->assertSame('01.06.2024 10:00', $result['#submitted']);
    $this->assertTrue($result['#attached']['drupalSettings']['grants_react_form']['use_print']);
    $this->assertTrue($result['#attached']['drupalSettings']['grants_react_form']['use_preview']);
  }

  /**
   * Test form preview view.
   */
  public function testFormPreview(): void {
    $controller = ApplicationController::create($this->container);
    $response = $controller->formPreview($this->applicationNumber);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertEquals([], json_decode($response->getContent(), TRUE));
  }

}
