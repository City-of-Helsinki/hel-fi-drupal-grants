<?php

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_application\Entity\ApplicationSubmission;
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
   * @param string $application_number
   *   The submission id.
   *
   * @return array
   *   The render array.
   */
  public function build(string $application_number): array {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();

    try {
      $entities = $this->entityTypeManager()
        ->getStorage('application_submission')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('application_number', $application_number)
        ->execute();
    }
    catch (\Exception $e) {
      // redirect to someplace else.
    }

    if (!$entities) {
      // redirect to someplace else.
    }

    $entity = ApplicationSubmission::load(reset($entities));

    // @todo Status string, view application link.
    $build = [
      '#theme' => 'grants_application_completion',
      '#applicationTimestamp' => date('Y-m-d h:i:s', (int) $entity->get('created')->value),
      '#submissionId' => $application_number,
      '#langcode' => $langcode,
      '#applicationID' => $application_number,
      '#applicationNumber' => $application_number,
      '#statusString' => 'DRAFT',
      '#statusStringHumanReadable' => 'draft',
      '#ownApplicationsLink' => Url::fromRoute('grants_oma_asiointi.front'),
      '#viewApplicationLink' => $entity->getViewApplicationUrl(),
      '#printApplicationLink' => $entity->getPrintApplicationUrl(),
    ];

    try {
      $submissionObject = $this->applicationGetterService->getAtvDocument($application_number);
      $build['#submissionObject'] = $submissionObject;
    }
    catch (\Throwable $e) {
      throw new NotFoundHttpException('Submission not found');
    }

    // The completion javascript should work as before.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $build['#attached']['drupalSettings']['grants_handler']['site_url'] = "$base_url/$langcode/";
    $build['#attached']['library'][] = 'grants_handler/application-status-check';
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
  public function getTitle(string $application_number): TranslatableMarkup {
    $tOpts = ['context' => 'grants_handler'];
    return $this->t('Completion page for @submissionId', ['@submissionId' => $application_number], $tOpts);
  }

}
