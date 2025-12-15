<?php

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for application completion & thankyou page.
 */
class CompletionController extends ControllerBase {

  /**
   * Getter service for applications.
   *
   * @var \Drupal\grants_application\Atv\HelfiAtvService
   */
  protected HelfiAtvService $helfiAtvService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\grants_application\Controller\CompletionController
   *   Controller object
   */
  public static function create(ContainerInterface $container): CompletionController {
    $instance = parent::create($container);
    $instance->helfiAtvService = $container->get(HelfiAtvService::class);
    $instance->requestStack = $container->get('request_stack');
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
  public function build(string $application_number): array|RedirectResponse {
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
      return new RedirectResponse(Url::fromRoute('grants_oma_asiointi.front')->toString());
    }

    if (!$entities) {
      return new RedirectResponse(Url::fromRoute('grants_oma_asiointi.front')->toString());
    }

    $entity = ApplicationSubmission::load(reset($entities));

    try {
      $document = $this->helfiAtvService->getDocument($entity->get('application_number')->value);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Unable to fetch the application. Please try again in a moment.'));
      return new RedirectResponse(Url::fromRoute('grants_oma_asiointi.front')->toString());
    }

    $status = $document->getStatus();

    $langCode = $this->languageManager()->getCurrentLanguage()->getId();
    $config = $this->config('grants_handler.settings');
    $statusStrings = $config->get('statusStrings');
    $humanReadableStatus = $statusStrings[$langCode][$status] ?? '';

    $build = [
      '#theme' => 'grants_application_completion',
      '#applicationTimestamp' => date('Y-m-d h:i:s', (int) $entity->get('created')->value),
      '#submissionId' => $application_number,
      '#langcode' => $langcode,
      '#applicationID' => $application_number,
      '#applicationNumber' => $application_number,
      '#statusString' => $status,
      '#statusStringHumanReadable' => $humanReadableStatus,
      '#ownApplicationsLink' => Url::fromRoute('grants_oma_asiointi.front'),
      '#viewApplicationLink' => $entity->getViewApplicationUrl(),
      '#printApplicationLink' => $entity->getPrintApplicationUrl(),
      '#submissionObject' => $document,
    ];

    $base_url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
    $build['#attached']['drupalSettings']['grants_handler']['site_url'] = "$base_url/$langcode/";
    $build['#attached']['library'][] = 'grants_handler/application-status-check';
    return $build;
  }

  /**
   * Get the title for the completion page.
   *
   * @param string $application_number
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
