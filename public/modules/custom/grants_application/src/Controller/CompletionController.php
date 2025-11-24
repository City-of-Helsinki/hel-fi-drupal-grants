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
   * @param string $submission_id
   *   The submission id.
   *
   * @return array
   *   The render array.
   */
  public function build(string $submission_id): array {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    /** @var \Drupal\grants_application\Entity\ApplicationSubmission $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('application_submission')
      ->load($submission_id);

    $entity->getEditApplicationLink('asd');
    $entity->getPrintApplicationUrl();
    $entity->getData();

    // @todo viewLink, statustag: statusstring+statushumanreadable+DI
    $build = [
      '#theme' => 'grants_application_completion',
      'variables' => [
        '#submissionId' => $submission_id,
        '#langcode' => $langcode,
        '#statusTag' => [
          '#theme' => 'grants_application_status_tag',
          '#langcode' => $langcode,
          '#applicationID' => $submission_id,
          '#statusString' => 'DRAFT',
          '#statusStringHumanReadable' => 'draft',
        ],
        '#applicationTimestamp' => date('Y-m-d h:i:s', (int) $entity->get('created')->value),
        '#ownApplicationsLink' => Url::fromRoute('grants_oma_asiointi.front'),
        '#viewApplicationLink' => $entity->getViewApplicationLink('TEST'),
        '#printApplicationLink' => $entity->getPrintApplicationUrl(),
      ],
    ];

    try {
      $submissionObject = $this->applicationGetterService->getAtvDocument($submission_id);
      $build['#submissionObject'] = $submissionObject;
    }
    catch (\Throwable $e) {
      throw new NotFoundHttpException('Submission not found');
    }

    // The completion javascript should work as before.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $currentLanguage = \Drupal::languageManager()->getCurrentLanguage();

    $build['#attached']['drupalSettings']['grants_handler']['site_url'] = $base_url . '/' . $currentLanguage->getId() . '/';
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
  public function getTitle(string $submission_id): TranslatableMarkup {
    $tOpts = ['context' => 'grants_handler'];
    return $this->t('Completion page for @submissionId', ['@submissionId' => $submission_id], $tOpts);
  }

}
