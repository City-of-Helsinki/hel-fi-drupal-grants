<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\grants_handler\ApplicationGetterServiceInterface;
use Drupal\grants_handler\Controller\WebformNavigationController;
use Drupal\grants_handler\FormLockService;
use Drupal\grants_handler\GrantsHandlerNavigationHelper;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Tests Debug controller.
 *
 * @group helfi_api_base
 */
class WebformNavigationControllerTest extends GrantsHandlerKernelTestBase {

  use ProphecyTrait;
  use UserCreationTrait {
    checkPermissions as drupalCheckPermissions;
    createAdminRole as drupalCreateAdminRole;
    createRole as drupalCreateRole;
    createUser as drupalCreateUser;
    grantPermissions as drupalGrantPermissions;
    setCurrentUser as drupalSetCurrentUser;
    setUpCurrentUser as drupalSetUpCurrentUser;
  }

  /**
   * The mocked request.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $request;

  /**
   * The mocked messenger.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $messenger;

  /**
   * The mocked logger.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $logger;

  /**
   * The mocked form lock service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $formLockService;

  /**
   * The mocked application getter service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|ApplicationGetterServiceInterface
   */
  protected ObjectProphecy|ApplicationGetterServiceInterface $applicationGetterService;

  /**
   * The mocked submission.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $submission;

  /**
   * The mocked ATV service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $atvService;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
    $urlGenerator->generateFromRoute(Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn('https://example.com/foo/bar');

    $this->request = $this->prophesize(Request::class);
    $this->request->getMethod()->willReturn('POST');

    $this->messenger = $this->prophesize(MessengerInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $loggerFactory->get('grants_handler')->willReturn($this->logger->reveal());

    $this->formLockService = $this->prophesize(FormLockService::class);
    $this->formLockService->isApplicationFormLocked('123')->willReturn(FALSE);

    $this->submission = $this->prophesize(WebformSubmission::class);
    $this->submission->getData()->willReturn(['status' => 'DRAFT']);

    $this->applicationGetterService = $this->prophesize(ApplicationGetterServiceInterface::class);
    $this->applicationGetterService->submissionObjectFromApplicationNumber('123')->willReturn($this->submission->reveal());
    $this->applicationGetterService->getAtvDocument('123')->willReturn(new AtvDocument());

    $navigationHelper = $this->prophesize(GrantsHandlerNavigationHelper::class);
    $navigationHelper->deleteSubmissionLogs($this->submission->reveal())->willReturn(1);

    $this->atvService = $this->prophesize(AtvService::class);
    $this->atvService->deleteDocument(new AtvDocument())->willReturn(TRUE);

    $this->container->set('url_generator', $urlGenerator->reveal());
    $this->container->set('messenger', $this->messenger->reveal());
    $this->container->set('logger.factory', $loggerFactory->reveal());
    $this->container->set('grants_handler.form_lock_service', $this->formLockService->reveal());
    $this->container->set('grants_handler.application_getter_service', $this->applicationGetterService->reveal());
    $this->container->set('grants_handler.navigation_helper', $navigationHelper->reveal());
    $this->container->set('helfi_atv.atv_service', $this->atvService->reveal());
  }

  /**
   * Test redirect with non-post method.
   */
  public function testRedirectWithNonPostMethod() {
    $this->request->getMethod()->willReturn('GET');
    $this->messenger->addError(Argument::any())->shouldBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(RedirectResponse::class, $result);
  }

  /**
   * Test redirect with locked content.
   */
  public function testRedirectWithLockedContent() {
    $this->formLockService->isApplicationFormLocked('123')->willReturn(TRUE);
    $this->messenger->addError(Argument::any())->shouldBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with application getter error.
   */
  public function testRedirectWithApplicationGetterError() {
    $this->applicationGetterService->submissionObjectFromApplicationNumber('123')->willThrow(new \Exception('Error: Test exception'));
    $this->messenger->addError(Argument::any())->shouldBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with empty submission data.
   */
  public function testRedirectWithEmptySubmissionData() {
    $this->submission->getData()->willReturn([]);
    $this->submission->delete()->shouldBeCalled();
    $this->messenger->addError(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldNotBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with non-draft submission data.
   */
  public function testRedirectWithNonDraftSubmissionData() {
    $this->submission->getData()->willReturn(['status' => 'NOT_DRAFT']);
    $this->submission->delete()->shouldNotBeCalled();
    $this->messenger->addError(Argument::any())->shouldBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldNotBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with ATV document error.
   */
  public function testRedirectWithAtvDocumentError() {
    $this->applicationGetterService->getAtvDocument('123')->willThrow(new \Exception('Error: Test exception'));
    $this->submission->delete()->shouldNotBeCalled();
    $this->messenger->addError(Argument::any())->shouldBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with ATV document not found.
   */
  public function testRedirectWithAtvDocumentNotFound() {
    $this->applicationGetterService->getAtvDocument('123')->willReturn(NULL);
    $this->expectException(\TypeError::class);
    $this->submission->delete()->shouldNotBeCalled();
    $this->messenger->addError(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldNotBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with ATV service error.
   */
  public function testRedirectWithAtvServiceError() {
    $this->atvService->deleteDocument(new AtvDocument())->willThrow(new \Exception('Error: Test exception'));
    $this->submission->delete()->shouldNotBeCalled();
    $this->messenger->addError(Argument::any())->shouldBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Test redirect with successful deletion.
   */
  public function testRedirectWithSuccessfulDeletion() {
    $this->atvService->deleteDocument(new AtvDocument())->willReturn(TRUE);
    $this->submission->delete()->shouldBeCalled();
    $this->messenger->addStatus(Argument::any())->shouldBeCalled();
    $this->messenger->addError(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any(), Argument::any())->shouldNotBeCalled();

    $controller = WebformNavigationController::create($this->container);
    $result = $controller->clearDraftData($this->request->reveal(), '123');

    $this->assertInstanceOf(JsonResponse::class, $result);
  }

}
