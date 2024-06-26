<?php

namespace Drupal\Tests\grants_handler\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\ApplicationValidator;
use Drupal\grants_metadata\Tests\TestDataRetriever;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Test for ApplicationValidator.
 *
 * @group grants_handler
 */
class ApplicationValidatorTest extends UnitTestCase {

  /**
   * The ApplicationValidator service.
   *
   * @var \Drupal\grants_handler\ApplicationValidator
   */
  protected ApplicationValidator $applicationValidator;

  /**
   * The TestDataRetriever service.
   *
   * @var \Drupal\grants_metadata\Tests\TestDataRetriever
   */
  protected TestDataRetriever $testDataRetriever;

  /**
   * Test data.
   *
   * @var array
   */
  protected array $testData;

  /**
   *
   */
  public static function getModules(): array {
    return ['grants_handler', 'grants_metadata'];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $navigationHelper = $this->createMock('Drupal\grants_handler\GrantsHandlerNavigationHelper');
    $loggerFactory = $this->createMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerChannel = $this->createMock('Drupal\Core\Logger\LoggerChannelInterface');

    $loggerFactory->method('get')->willReturn($loggerChannel);

    $this->applicationValidator = new ApplicationValidator($navigationHelper, $loggerFactory);

    $this->testDataRetriever = new TestDataRetriever();

    try {
      $this->testData = $this->testDataRetriever->loadTestData();
      $d = 'asdf';
    }
    catch (\Exception $e) {
      $ed = 'asdf';
    }
  }

  /**
   * Test for validateApplication.
   */
  public function testProcessViolation(): void {
    $violation = $this->createMock(ConstraintViolationInterface::class);
    $formState = $this->createMock(FormStateInterface::class);
    $appProps = [];
    $formElementsDecodedAndFlattened = [];
    $erroredItems = [];
    $violationPrints = [];

    $violation->expects($this->once())
      ->method('getMessage')
      ->willReturn('Test violation message');

    $formState->expects($this->once())
      ->method('setErrorByName')
      ->with($this->equalTo('test'), $this->equalTo('Test violation message'));

    $this->applicationValidator->processViolation(
      $violation,
      $appProps,
      $formState,
      $formElementsDecodedAndFlattened,
      $erroredItems,
      $violationPrints);
  }

}
