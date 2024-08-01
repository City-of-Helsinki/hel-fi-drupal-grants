<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Final class to provide validation for application data.
 */
final class ApplicationValidator {

  use AutowireTrait;
  use DebuggableTrait;
  use StringTranslationTrait;

  /**
   * Log errors.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\grants_handler\GrantsHandlerNavigationHelper $grantsHandlerNavigationHelper
   *   Navigation error helper.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel factory.
   */
  public function __construct(
    private readonly GrantsHandlerNavigationHelper $grantsHandlerNavigationHelper,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    $this->logger = $this->loggerChannelFactory->get('grants_handler');
    $this->debug = getenv('DEBUG') === 'true';
  }

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\grants_handler\ApplicationValidator
   *   Validator object
   */
  public static function create(ContainerInterface $container): ApplicationValidator {
    return new ApplicationValidator(
      $container->get('grants_handler.navigation_helper'),
      $container->get('logger.factory')
    );
  }

  /**
   * Validate application.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $applicationData
   *   Application data.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   Violations.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function validateApplication(
    ComplexDataInterface $applicationData,
    FormStateInterface &$formState,
    WebformSubmission $webform_submission,
  ): ConstraintViolationListInterface {

    $violations = $applicationData->validate();

    if ($violations->count() > 0) {
      $this->handleViolations($violations, $applicationData, $formState, $webform_submission);
    }

    try {
      $this->grantsHandlerNavigationHelper->logPageErrors($webform_submission, $formState);
    }
    catch (\Exception $e) {
      $this->logger->error('Error logging page errors: %msg', ['%msg' => $e->getMessage()]);
    }

    return $violations;
  }

  /**
   * Handle violations.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   Violations.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $applicationData
   *   Application data.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Webform submission.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function handleViolations(
    ConstraintViolationListInterface $violations,
    ComplexDataInterface $applicationData,
    FormStateInterface &$formState,
    WebformSubmission $webform_submission,
  ): void {
    $appProps = $applicationData->getProperties();
    $formElementsDecodedAndFlattened = $webform_submission->getWebform()->getElementsDecodedAndFlattened();
    $erroredItems = [];
    $violationPrints = [];

    foreach ($violations as $violation) {
      if ($violation->getPropertyPath() == 'hakijan_tiedot.email') {
        continue;
      }

      $this->handleViolation($violation, $appProps, $formState, $formElementsDecodedAndFlattened, $erroredItems, $violationPrints);
    }

    if ($this->isDebug()) {
      $this->logger->error('@appno data validation failed, errors: @errors',
        [
          '@appno' => $applicationData->getValue()["application_number"],
          '@errors' => json_encode($violationPrints),
        ]);
    }
  }

  /**
   * Process violation.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   Violation.
   * @param array $appProps
   *   Application properties.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $formElementsDecodedAndFlattened
   *   Form elements.
   * @param array $erroredItems
   *   Errored items.
   * @param array $violationPrints
   *   Violation prints.
   */
  public function processViolation(
    ConstraintViolationInterface $violation,
    array $appProps,
    FormStateInterface &$formState,
    array $formElementsDecodedAndFlattened,
    array &$erroredItems,
    array &$violationPrints,
  ): void {
    $this->handleViolation($violation, $appProps, $formState, $formElementsDecodedAndFlattened, $erroredItems, $violationPrints);
  }

  /**
   * Handle violation.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   Violation.
   * @param array $appProps
   *   Application properties.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $formElementsDecodedAndFlattened
   *   Form elements.
   * @param array $erroredItems
   *   Errored items.
   * @param array $violationPrints
   *   Violation prints.
   */
  private function handleViolation(
    ConstraintViolationInterface $violation,
    array $appProps,
    FormStateInterface &$formState,
    array $formElementsDecodedAndFlattened,
    array &$erroredItems,
    array &$violationPrints,
  ): void {
    $propertyPath = $violation->getPropertyPath();
    $propertyPathArray = explode('.', $propertyPath);
    $thisProperty = $appProps[$propertyPathArray[0]];
    $thisDefinition = $thisProperty->getDataDefinition();
    $label = $thisDefinition->getLabel();
    $thisDefinitionSettings = $thisDefinition->getSettings();
    $message = $violation->getMessage();

    $violationPrints[$propertyPath] = $message;

    if (isset($thisDefinitionSettings['formSettings']['formElement'])) {
      $this->handleFormElementViolation($violation, $propertyPath, $thisDefinitionSettings, $label, $formState, $erroredItems);
    }
    else {
      $this->handleOtherViolation($violation,
        $propertyPath,
        $propertyPathArray,
        $formElementsDecodedAndFlattened,
        $formState,
        $erroredItems);
    }
  }

  /**
   * Handle form element violation.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   Violation.
   * @param string $propertyPath
   *   Property path.
   * @param array $thisDefinitionSettings
   *   Definition settings.
   * @param string $label
   *   Label.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $erroredItems
   *   Errored items.
   */
  private function handleFormElementViolation(
    ConstraintViolationInterface $violation,
    string $propertyPath,
    array $thisDefinitionSettings,
    string $label,
    FormStateInterface &$formState,
    array &$erroredItems,
  ): void {
    $propertyPath = $thisDefinitionSettings['formSettings']['formElement'];

    if (!in_array($propertyPath, $erroredItems)) {
      $errorMsg = $thisDefinitionSettings['formSettings']['formError'] ?? $violation->getMessage();
      $message = $this->t('@label: @msg', ['@label' => $label, '@msg' => $errorMsg]);
      $formState->setErrorByName($propertyPath, $message);
      $erroredItems[] = $propertyPath;
    }
  }

  /**
   * Handle other violation.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   Violation.
   * @param string $propertyPath
   *   Property path.
   * @param array $propertyPathArray
   *   Property path array.
   * @param array $formElementsDecodedAndFlattened
   *   Form elements.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $erroredItems
   *   Errored items.
   */
  private function handleOtherViolation(
    ConstraintViolationInterface $violation,
    string $propertyPath,
    array $propertyPathArray,
    array $formElementsDecodedAndFlattened,
    FormStateInterface &$formState,
    array &$erroredItems,
  ): void {
    if (($formElement = $formElementsDecodedAndFlattened[$propertyPath] ?? NULL) && isset($formElement['#parents'])) {
      $formState->setError($formElement, $violation->getMessage());
    }
    else {
      $propertyKey = count($propertyPathArray) > 1 ? str_replace('.', '][', $propertyPath) : $propertyPath;
      $formState->setErrorByName($propertyKey, $violation->getMessage());
    }

    $erroredItems[] = $propertyPath;
  }

}
