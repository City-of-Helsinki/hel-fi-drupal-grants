<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_profile\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationListItemBuilder;
use Drupal\grants_handler\ApplicationStatusServiceInterface;
use Drupal\grants_handler\MessageService;
use Drupal\grants_profile\Controller\GrantsOmaAsiointiController;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Entity\Webform;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the oma asiointi controller.
 */
#[Group('grants_profile')]
class GrantsOmaAsiointiControllerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $loggerFactory->get(Argument::any())
      ->willReturn($this->prophesize(LoggerChannelInterface::class)->reveal());

    $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
    $urlGenerator->generateFromRoute(Argument::cetera())->willReturn('/url');

    $container = new ContainerBuilder();
    $container->set('logger.factory', $loggerFactory->reveal());
    $container->set('url_generator', $urlGenerator->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Creates a webform application as returned by getCompanyApplications().
   *
   * @param string $applicationNumber
   *   The application number.
   * @param string $status
   *   The application status.
   *
   * @return array<string, mixed>
   *   The application.
   */
  private function createApplication(string $applicationNumber, string $status): array {
    return [
      '#webform' => TRUE,
      '#submission_id' => 1,
      '#submission' => [
        'application_number' => $applicationNumber,
        'status' => $status,
        'metadata' => ['form_uuid' => 'uuid'],
      ],
      '#document' => AtvDocument::create(['status' => $status]),
    ];
  }

  /**
   * Gets the controller under test.
   *
   * @param array<string, mixed> $applications
   *   Applications returned by the application getter service.
   *
   * @return \Drupal\grants_profile\Controller\GrantsOmaAsiointiController
   *   The controller.
   */
  private function getSut(array $applications): GrantsOmaAsiointiController {
    $grantsProfileService = $this->prophesize(GrantsProfileService::class);
    $grantsProfileService->getSelectedRoleData()->willReturn(['identifier' => 'test_company']);
    $grantsProfileService->getGrantsProfile(Argument::any())->willReturn(NULL);

    $webform = $this->prophesize(Webform::class);
    $webform->id()->willReturn('test_webform');
    $webform->label()->willReturn('Test webform');
    $webform->getThirdPartySettings('grants_metadata')->willReturn(['applicationContinuous' => 1]);

    $applicationGetterService = $this->prophesize(ApplicationGetterService::class);
    $applicationGetterService->getCompanyApplications(Argument::cetera())->willReturn($applications);
    $applicationGetterService->getWebformFromApplicationNumber(Argument::any())->willReturn($webform->reveal());

    $messageService = $this->prophesize(MessageService::class);
    $messageService->parseMessages(Argument::cetera())->willReturn([]);

    $applicationStatusService = $this->prophesize(ApplicationStatusServiceInterface::class);
    $applicationStatusService->getApplicationStatuses()->willReturn([]);
    $applicationStatusService->getWebformStatus(Argument::any())->willReturn('released');

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('fi');
    $languageManager = $this->prophesize(LanguageManagerInterface::class);
    $languageManager->getCurrentLanguage()->willReturn($language->reveal());

    $applicationListItemBuilder = new ApplicationListItemBuilder(
      $applicationStatusService->reveal(),
      $applicationGetterService->reveal(),
      $this->prophesize(FormSettingsServiceInterface::class)->reveal(),
      $languageManager->reveal(),
      $this->getConfigFactoryStub(['grants_handler.settings' => ['statusStrings' => []]]),
      $this->getStringTranslationStub(),
      $this->prophesize(LoggerChannelInterface::class)->reveal(),
    );

    $atvService = $this->prophesize(AtvService::class)->reveal();

    return new GrantsOmaAsiointiController(
      new RequestStack(),
      $grantsProfileService->reveal(),
      $atvService,
      $messageService->reveal(),
      $applicationGetterService->reveal(),
      $atvService,
      $this->prophesize(QueueFactory::class)->reveal(),
      $applicationListItemBuilder,
    );
  }

  /**
   * Tests that the application lists are passed to drupalSettings.
   */
  public function testBuildAttachesApplicationLists(): void {
    $invalidApplication = $this->createApplication('LOCAL-058-0000004', 'RECEIVED');
    unset($invalidApplication['#document']);

    $build = $this->getSut([
      'DRAFT' => [
        $this->createApplication('LOCAL-058-0000001', 'DRAFT'),
      ],
      'SUBMITTED' => [
        $this->createApplication('LOCAL-058-0000002', 'SUBMITTED'),
      ],
      'RECEIVED' => [
        $invalidApplication,
        $this->createApplication('LOCAL-058-0000003', 'RECEIVED'),
      ],
    ])->build();

    $settings = $build['#attached']['drupalSettings']['own_applications'];

    $this->assertEquals(['LOCAL-058-0000001'], array_column($settings['drafts'], 'applicationNumber'));
    // Applications that can't be listed are filtered out and the list
    // is re-indexed so that it is passed to JS as an array.
    $this->assertSame(
      ['LOCAL-058-0000002', 'LOCAL-058-0000003'],
      array_column($settings['sent'], 'applicationNumber'),
    );
    $this->assertSame([0, 1], array_keys($settings['sent']));
  }

  /**
   * Tests that the lists are empty when the company has no applications.
   */
  public function testBuildWithNoApplications(): void {
    $build = $this->getSut([])->build();

    $this->assertSame(
      ['drafts' => [], 'sent' => []],
      $build['#attached']['drupalSettings']['own_applications'],
    );
  }

}
