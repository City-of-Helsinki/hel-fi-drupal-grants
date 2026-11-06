<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_handler\ApplicationGetterServiceInterface;
use Drupal\grants_handler\ApplicationListItemBuilder;
use Drupal\grants_handler\ApplicationStatusServiceInterface;
use Drupal\helfi_atv\AtvDocument;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Entity\Webform;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests the application list item builder.
 */
#[Group('grants_handler')]
class ApplicationListItemBuilderTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The application status service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\grants_handler\ApplicationStatusServiceInterface>
   */
  protected ObjectProphecy $applicationStatusService;

  /**
   * The application getter service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\grants_handler\ApplicationGetterServiceInterface>
   */
  protected ObjectProphecy $applicationGetterService;

  /**
   * The form settings service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\grants_application\Form\FormSettingsServiceInterface>
   */
  protected ObjectProphecy $formSettingsService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
    $urlGenerator->generateFromRoute(Argument::cetera())
      ->will(fn (array $args) => '/' . $args[0] . '/' . implode('/', $args[1]));

    $container = new ContainerBuilder();
    $container->set('url_generator', $urlGenerator->reveal());
    \Drupal::setContainer($container);

    $this->applicationStatusService = $this->prophesize(ApplicationStatusServiceInterface::class);
    $this->applicationStatusService->getApplicationStatuses()
      ->willReturn(['DRAFT' => 'DRAFT', 'SUBMITTED' => 'SUBMITTED']);
    $this->applicationStatusService->getWebformStatus(Argument::any())
      ->willReturn('released');

    $this->applicationGetterService = $this->prophesize(ApplicationGetterServiceInterface::class);
    $this->formSettingsService = $this->prophesize(FormSettingsServiceInterface::class);
  }

  /**
   * Gets the builder under test.
   *
   * @return \Drupal\grants_handler\ApplicationListItemBuilder
   *   The builder.
   */
  private function getSut(): ApplicationListItemBuilder {
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('fi');
    $languageManager = $this->prophesize(LanguageManagerInterface::class);
    $languageManager->getCurrentLanguage()->willReturn($language->reveal());

    $configFactory = $this->getConfigFactoryStub([
      'grants_handler.settings' => [
        'statusStrings' => [
          'fi' => ['DRAFT' => 'Luonnos', 'SUBMITTED' => 'Lähetetty'],
          'sv' => ['DRAFT' => 'Utkast'],
        ],
      ],
    ]);
    assert($configFactory instanceof ConfigFactoryInterface);

    return new ApplicationListItemBuilder(
      $this->applicationStatusService->reveal(),
      $this->applicationGetterService->reveal(),
      $this->formSettingsService->reveal(),
      $languageManager->reveal(),
      $configFactory,
      $this->getStringTranslationStub(),
      $this->prophesize(LoggerChannelInterface::class)->reveal(),
    );
  }

  /**
   * Creates a mocked webform with given grants_metadata settings.
   *
   * @param array<string, mixed> $thirdPartySettings
   *   The grants_metadata third party settings.
   *
   * @return \Drupal\webform\Entity\Webform
   *   The mocked webform.
   */
  private function createWebform(array $thirdPartySettings): Webform {
    $webform = $this->prophesize(Webform::class);
    $webform->id()->willReturn('test_webform');
    $webform->label()->willReturn('Test webform');
    $webform->getThirdPartySettings('grants_metadata')->willReturn($thirdPartySettings);
    return $webform->reveal();
  }

  /**
   * Creates a webform application as returned by getCompanyApplications().
   *
   * @param array<string, mixed> $submission
   *   Submission data overrides.
   *
   * @return array<string, mixed>
   *   The application.
   */
  private function createWebformApplication(array $submission = []): array {
    return [
      '#webform' => TRUE,
      '#submission_id' => 123,
      '#submission' => $submission + [
        'application_number' => 'LOCAL-058-0000001',
        'status' => 'SUBMITTED',
        'form_timestamp_submitted' => '2025-03-01T10:15:00',
        'metadata' => ['form_uuid' => 'uuid'],
        'messages' => [],
      ],
      '#document' => AtvDocument::create(['status' => 'SUBMITTED']),
    ];
  }

  /**
   * Tests that applications missing required data are not listed.
   */
  public function testBuildReturnsNullForInvalidApplication(): void {
    $sut = $this->getSut();

    $application = $this->createWebformApplication();
    unset($application['#document']);
    $this->assertNull($sut->build($application));

    $application = $this->createWebformApplication();
    unset($application['#submission_id']);
    $this->assertNull($sut->build($application));

    // Webform application without form uuid.
    $this->assertNull($sut->build($this->createWebformApplication(['metadata' => []])));

    // Webform cannot be found.
    $this->applicationGetterService->getWebformFromApplicationNumber(Argument::any())->willReturn(NULL);
    $this->assertNull($sut->build($this->createWebformApplication()));

    // React application without submission entity.
    $application = $this->createWebformApplication();
    $application['#webform'] = FALSE;
    $this->assertNull($sut->build($application));
  }

  /**
   * Tests building a webform application list item.
   */
  public function testBuildWebformApplication(): void {
    $this->applicationGetterService->getWebformFromApplicationNumber('LOCAL-058-0000001')
      ->willReturn($this->createWebform(['applicationContinuous' => 1]));

    $item = $this->getSut()->build($this->createWebformApplication([
      'messages' => [
        ['sentBy' => 'Avustusten kasittelyjarjestelma', 'messageStatus' => 'UNREAD'],
        ['sentBy' => 'Avustusten kasittelyjarjestelma', 'messageStatus' => 'UNREAD'],
        ['sentBy' => 'Avustusten kasittelyjarjestelma', 'messageStatus' => 'READ'],
        ['sentBy' => 'Applicant', 'messageStatus' => 'UNREAD'],
      ],
    ]));

    $this->assertEquals([
      'applicationNumber' => 'LOCAL-058-0000001',
      'formName' => 'Test webform',
      'status' => 'SUBMITTED',
      'statusLabel' => 'Lähetetty',
      'submitted' => '01.03.2025 10:15',
      'submittedSortable' => '2025-03-01T10:15:00',
      'errorType' => NULL,
      'links' => [
        'edit' => '/grants_handler.edit_application/test_webform/123',
        'delete' => '/grants_handler.clear-navigations/LOCAL-058-0000001',
        'view' => '/grants_handler.view_application/LOCAL-058-0000001',
        'print' => '/grants_webform_print.submission_print/LOCAL-058-0000001',
      ],
      'unreadMessages' => '2 new messages',
    ], $item);
  }

  /**
   * Tests the error type of a webform application.
   */
  public function testWebformApplicationErrorType(): void {
    $sut = $this->getSut();

    // Application period has ended.
    $this->applicationGetterService->getWebformFromApplicationNumber(Argument::any())
      ->willReturn($this->createWebform([
        'applicationContinuous' => 0,
        'applicationOpen' => '-2 months',
        'applicationClose' => '-1 month',
      ]));
    $this->assertEquals('NOT_OPEN', $sut->build($this->createWebformApplication())['errorType']);

    // Application period is open, but the webform is archived.
    $this->applicationGetterService->getWebformFromApplicationNumber(Argument::any())
      ->willReturn($this->createWebform([
        'applicationContinuous' => 0,
        'applicationOpen' => '-1 month',
        'applicationClose' => '+1 month',
      ]));
    $this->applicationStatusService->getWebformStatus(Argument::any())->willReturn('archived');
    $this->assertEquals('ARCHIVED', $sut->build($this->createWebformApplication())['errorType']);
  }

  /**
   * Tests building a React form application list item.
   */
  public function testBuildReactApplication(): void {
    $this->formSettingsService->getFormSettings(58, 'test_form')
      ->willReturn(new FormSettings(
        settings: [
          'title' => 'Test form',
          'continuous' => FALSE,
          'application_open' => '-1 month',
          'application_close' => '-1 day',
        ],
        schema: [],
        uiSchema: [],
        translation: ['translations' => ['form_title' => ['fi' => 'Testilomake']]],
      ));

    $url = $this->prophesize(Url::class);
    $url->toString()->willReturn('/url');
    $link = $this->prophesize(Link::class);
    $link->getUrl()->willReturn($url->reveal());

    $entity = $this->prophesize(ApplicationSubmission::class);
    $entity->get('langcode')->willReturn((object) ['value' => 'sv']);
    $entity->getSubmittedDateTime('d.m.Y H:i')->willReturn('01.03.2025 10:15');
    $entity->getSubmittedDateTime('Y-m-d\TH:i:s')->willReturn('2025-03-01T10:15:00');
    $entity->getEditApplicationLink('Test form')->willReturn($link->reveal());
    $entity->getDeleteApplicationUrl()->willReturn($url->reveal());
    $entity->getViewApplicationLink('Test form', TRUE)->willReturn($url->reveal());
    $entity->getPrintApplicationUrl()->shouldNotBeCalled();

    $item = $this->getSut()->build([
      '#webform' => FALSE,
      '#submission_id' => 'LOCAL-058-0000002',
      '#submission' => [
        'application_number' => 'LOCAL-058-0000002',
        'application_type_id' => 58,
        'form_identifier' => 'test_form',
        'status' => 'DRAFT',
      ],
      '#document' => AtvDocument::create(['status' => 'DRAFT']),
      '#submission_entity' => $entity->reveal(),
    ]);

    $this->assertEquals([
      'applicationNumber' => 'LOCAL-058-0000002',
      'formName' => 'Testilomake',
      'status' => 'DRAFT',
      // The status label uses the language of the submission.
      'statusLabel' => 'Utkast',
      'submitted' => '01.03.2025 10:15',
      'submittedSortable' => '2025-03-01T10:15:00',
      'errorType' => 'NOT_OPEN',
      'links' => [
        'edit' => '/url',
        'delete' => '/url',
        'view' => '/url',
        // Drafts can't be printed.
        'print' => NULL,
      ],
      'unreadMessages' => NULL,
    ], $item);
  }

}
