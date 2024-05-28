<?php

namespace Drupal\grants_profile\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\grants_profile\GrantsProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ValidIban constraint.
 *
 * @phpstan-consistent-constructor
 */
class RequiredIfRegisteredValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new RequiredIfRegisteredValidator object.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   */
  public function __construct(private GrantsProfileService $grantsProfileService) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grants_profile.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, $constraint) {
    if (!$this->isRequired($value)) {
      $this->context->addViolation($constraint->requiredMissing, ['%value' => $value]);
    }
  }

  /**
   * Is value valid IBAN.
   *
   * @param string|null $value
   *   Value to be validated.
   *
   * @return bool
   *   If value is conditionally required.
   */
  private function isRequired(?string $value): bool {

    $applicantType = $this->grantsProfileService->getApplicantType();

    if ($applicantType == 'registered_community') {
      if (empty($value)) {
        return FALSE;
      }
      return TRUE;
    }
    // All other scenarios return true.
    return TRUE;
  }

}
