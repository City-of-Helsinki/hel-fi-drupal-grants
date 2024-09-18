<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_handler\ApplicationGetterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for application completion & thankyou page.
 */
class CompletionController extends ControllerBase {

  /**
   * Getter service for applications.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\grants_handler\Controller\CompletionController
   *   Controller object
   */
  public static function create(ContainerInterface $container): CompletionController {
    $instance = parent::create($container);
    $instance->applicationGetterService = $container->get('grants_handler.application_getter_service');
    return $instance;
  }

  /**
   * Build the completion page.
   *
   * @param string $submission_id
   *   The submission id.
   *
   * @return array
   *   The render array.
   */
  public function build(string $submission_id): array {

    $build = [
      '#theme' => 'grants_handler_completion',
      '#submissionId' => $submission_id,
    ];

    try {

      $submissionObject = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
      $build['#submissionObject'] = $submissionObject;

    }
    catch (\Throwable $e) {
      throw new NotFoundHttpException('Submission not found');
    }

    return $build;
  }

  /**
   * Get the title for the completion page.
   *
   * @param string $submission_id
   *   The submission id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  public function getTitle(string $submission_id): TranslatableMarkup {
    $tOpts = ['context' => 'grants_handler'];
    return $this->t('Completion page for @submissionId', ['@submissionId' => $submission_id], $tOpts);
  }

}
