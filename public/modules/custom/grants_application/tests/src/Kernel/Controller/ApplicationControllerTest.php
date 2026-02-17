<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Controller;

use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Controller\ApplicationController;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_events\EventsService;
use Drupal\helfi_av\AntivirusService;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('application_submission');

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
      'application_number' => $this->applicationNumber,
      'created' => '1765430954',
      'changed' => '1765430954',
    ]);
    $this->applicationSubmission->save();

    $eventService = $this->createMock(EventsService::class);
    $eventService->expects($this->any())->method('logEvent')->withAnyParameters()->willReturn([]);

    $helfiAtvService = $this->createMock(HelfiAtvService::class);
    $helfiAtvService->expects($this->any())->method('addAttachment')->willReturn([
      'filename' => 'test-file.txt',
      'id' => '9595',
      'href' => 'testhref.example.com/file/9595',
      'size' => '999',
    ]);
    $helfiAtvService->expects($this->any())->method('removeAttachment')->willReturn(TRUE);

    $antiVirusService = $this->createMock(AntivirusService::class);
    $antiVirusService->expects($this->any())->method('scan')->willReturn(TRUE);

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->container->set('grants_events.events_service', $eventService);
    $this->container->set(HelfiAtvService::class, $helfiAtvService);
    $this->container->set(AntivirusService::class, $antiVirusService);
    $this->container->set('request_stack', $requestStack);
  }

  /**
   * Test the file upload.
   */
  public function testFileUpload(): void {
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
    $this->assertTrue($response->getStatusCode() === 200);
  }

}
