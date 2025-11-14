<?php

namespace Drupal\grants_application;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\grants_application\Entity\ApplicationSubmission;

/**
 *
 */
class ApplicationRepository {

  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * Get application by application number.
   *
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\grants_application\Entity\ApplicationSubmission
   *   The application submission.
   */
  public function getApplicationSubmission(
    string $application_number,
  ): ApplicationSubmission|NULL {
    $ids = $this->entityTypeManager
      ->getStorage('application_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('application_number', $application_number)
      ->execute();

    if (!$ids) {
      return NULL;
    }

    return ApplicationSubmission::load(reset($ids));
  }

}
