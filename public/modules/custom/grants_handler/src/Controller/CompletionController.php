<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\Plugin\WebformElement\CompensationsComposite;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for application completion & thankyou page.
 */
class CompletionController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build($submission_id): array {







    $build = [
      '#theme' => 'grants_handler_completion',
      '#submissionId' => $submission_id,
    ];

//    try {
//      $submissionObject = ApplicationHandler::submissionObjectFromApplicationNumber($submission_id);
//      $build['#submissionObject'] = $submissionObject;
//
//    }
//    catch (\Throwable $e) {
//      throw new NotFoundHttpException('Submission not found');
//    }

    return $build;
  }

  /**
   * Returns a page title.
   */
  public function getTitle($submission_id): TranslatableMarkup {
    return $this->t('Completion page for @submissionId', ['@submissionId' => $submission_id]);
  }

}
