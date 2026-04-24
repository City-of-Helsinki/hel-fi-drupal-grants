<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_admin_applications\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\grants_admin_applications\Form\ResendApplicationsForm;
use Drupal\grants_attachments\AttachmentFixerService;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * Tests ResendApplicationsForm::resendApplicationCallback for React apps.
 */
class ResendApplicationsFormTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'grants_admin_applications',
    'file',
    'helfi_helsinki_profiili',
    'helfi_api_base',
    'user',
    'externalauth',
    'openid_connect',
    'helfi_atv',
  ];

  /**
   * Create an AtvDocument suitable for testing the resend path.
   */
  private function createDocument(string $status): AtvDocument {
    return AtvDocument::create([
      'status' => $status,
      'metadata' => ['appenv' => 'TEST'],
      'content' => [
        'events' => [],
        'attachmentsInfo' => ['attachmentsArray' => []],
      ],
      'attachments' => [],
    ]);
  }

  /**
   * Build the form with mocked dependencies.
   *
   * SendApplicationToIntegrations is overridden in the testable subclass so
   * we do not need to mock the database, HTTP client or events service.
   *
   * @param array $entities
   *   Return value for application_submission storage loadByProperties().
   * @param array $atvDocuments
   *   Return value for AtvService::searchDocuments().
   */
  private function buildForm(array $entities, array $atvDocuments): ResendApplicationsFormTestable {
    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->loadByProperties(Argument::any())->willReturn($entities);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('application_submission')->willReturn($storage->reveal());

    $atvService = $this->prophesize(AtvService::class);
    $atvService->searchDocuments(Argument::any())->willReturn($atvDocuments);

    $attachmentFixerService = new AttachmentFixerService(
      $this->prophesize(LoggerInterface::class)->reveal()
    );

    return new ResendApplicationsFormTestable(
      $this->prophesize(Connection::class)->reveal(),
      $this->prophesize(ApplicationGetterService::class)->reveal(),
      $this->prophesize(Client::class)->reveal(),
      $this->prophesize(EventsService::class)->reveal(),
      $atvService->reveal(),
      $this->prophesize(MessageService::class)->reveal(),
      $this->prophesize(AccountProxyInterface::class)->reveal(),
      $this->prophesize(TimeInterface::class)->reveal(),
      $attachmentFixerService,
      $entityTypeManager->reveal(),
    );
  }

  /**
   * React app whose ATV document cannot be found shows a warning.
   */
  public function testResendReactAppDocumentNotFound(): void {
    $form = $this->buildForm(entities: ['entity'], atvDocuments: []);
    $formState = new FormState();
    $formState->setValue('applicationId', 'GRANTS-TEST-0001');

    $form->resendApplicationCallback([], $formState);

    $this->assertNotEmpty(\Drupal::messenger()->messagesByType('warning'));
    $this->assertTrue($formState->isRebuilding());
  }

  /**
   * React app with a status other than RECEIVED/PREPARING shows an error.
   */
  public function testResendReactAppInvalidStatus(): void {
    $doc = AtvDocument::create(['status' => 'DRAFT']);
    $form = $this->buildForm(entities: ['entity'], atvDocuments: [$doc]);
    $formState = new FormState();
    $formState->setValue('applicationId', 'GRANTS-TEST-0001');

    $form->resendApplicationCallback([], $formState);

    $this->assertNotEmpty(\Drupal::messenger()->messagesByType('error'));
    $this->assertFalse($form->sendToIntegrationsCalled);
  }

  /**
   * React app with RECEIVED status is resent successfully.
   */
  public function testResendReactAppReceived(): void {
    $doc = $this->createDocument('RECEIVED');
    $form = $this->buildForm(entities: ['entity'], atvDocuments: [$doc]);
    $formState = new FormState();
    $formState->setValue('applicationId', 'GRANTS-TEST-0001');

    $form->resendApplicationCallback([], $formState);

    $this->assertTrue($form->sendToIntegrationsCalled);
    $this->assertTrue($formState->isRebuilding());
  }

  /**
   * React app with PREPARING status is resent successfully.
   */
  public function testResendReactAppPreparing(): void {
    $doc = $this->createDocument('PREPARING');
    $form = $this->buildForm(entities: ['entity'], atvDocuments: [$doc]);
    $formState = new FormState();
    $formState->setValue('applicationId', 'GRANTS-TEST-0001');

    $form->resendApplicationCallback([], $formState);

    $this->assertTrue($form->sendToIntegrationsCalled);
    $this->assertTrue($formState->isRebuilding());
  }

}

/**
 * Testable subclass that stubs out the integration call.
 *
 * This avoids needing real database/HTTP dependencies in the test while still
 * exercising the full resendApplicationCallback code path.
 */
class ResendApplicationsFormTestable extends ResendApplicationsForm {

  /**
   * Whether sendApplicationToIntegrations was called.
   *
   * @var bool
   */
  public bool $sendToIntegrationsCalled = FALSE;

  /**
   * {@inheritdoc}
   */
  public function sendApplicationToIntegrations(AtvDocument $atvDoc, string $applicationId): void {
    $this->sendToIntegrationsCalled = TRUE;
  }

}
