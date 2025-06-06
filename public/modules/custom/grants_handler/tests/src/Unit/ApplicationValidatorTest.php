<?php

namespace Drupal\Tests\grants_handler\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
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
   * {@inheritdoc}
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
    }
    catch (\Exception $e) {
      $this->fail('Failed to load test data');
    }
  }

  /**
   * Test for validateApplication.
   *
   * @covers \Drupal\grants_handler\ApplicationValidator::processViolation
   * @covers \Drupal\grants_handler\ApplicationValidator::__construct
   * @covers \Drupal\grants_handler\ApplicationValidator::handleOtherViolation
   * @covers \Drupal\grants_handler\ApplicationValidator::handleViolation
   */
  public function testProcessViolation(): void {
    $violation = $this->createMock(ConstraintViolationInterface::class);
    $formState = $this->createMock(FormStateInterface::class);
    $dataInterface = $this->createMock(ComplexDataInterface::class);

    $definition = $this->createMock('Drupal\grants_test_base\TypedData\Definition\TestDataDefinition');
    $appProps = ['test' => $dataInterface];
    $formElementsDecodedAndFlattened = [];
    $erroredItems = [];
    $violationPrints = [];

    $violation->expects($this->any())
      ->method('getMessage')
      ->willReturn('Test violation message');

    $violation->expects($this->once())
      ->method('getPropertyPath')
      ->willReturn('test.violation');

    $formState->expects($this->once())
      ->method('setErrorByName')
      ->with($this->equalTo('test][violation'), $this->equalTo('Test violation message'));

    $dataInterface->expects($this->once())
      ->method('getDataDefinition')
      ->willReturn($definition);

    $this->applicationValidator->processViolation(
      $violation,
      $appProps,
      $formState,
      $formElementsDecodedAndFlattened,
      $erroredItems,
      $violationPrints);
    $this->assertEquals('Test violation message', $violationPrints['test.violation']);
  }

}
