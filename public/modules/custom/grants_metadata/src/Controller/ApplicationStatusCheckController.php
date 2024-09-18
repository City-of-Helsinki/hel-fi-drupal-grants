<?php

namespace Drupal\grants_metadata\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Grant Applications: Form Metadata routes.
 *
 * @phpstan-consistent-constructor
 */
class ApplicationStatusCheckController extends ControllerBase {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * The controller constructor.
   *
   * @param \Drupal\helfi_atv\AtvService $helfiAtv
   *   The helfi_atv service.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   The helfi_atv service.
   */
  public function __construct(
    protected AtvService $helfiAtv,
    protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {
    $this->config = $this->config('grants_handler.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container):static {
    return new static(
      $container->get('helfi_atv.atv_service'),
      $container->get('helfi_helsinki_profiili.userdata'),
    );
  }

  /**
   * Builds the response.
   *
   * @param string $submission_id
   *   The submission id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json representation of data.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build(string $submission_id = ''): JsonResponse {
    return new JsonResponse([
      'data' => $this->getData($submission_id),
      'method' => 'GET',
      'status' => 200,
    ]);
  }

  /**
   * Run query to ATV rest endpoint & return metadata for document.
   *
   * @param string $submission_id
   *   Id of submission.
   *
   * @return array
   *   Data from ATV.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getData(string $submission_id): array {

    $userData = $this->helsinkiProfiiliUserData->getUserData();

    if (empty($userData) || !isset($userData['sub'])) {
      $this->getLogger('status_check_controller')
        ->error('Status check, submission %application_number not found',
        [
          'application_number' => $submission_id,
        ]
      );
      return [];
    }

    $userDocuments = $this->helfiAtv->getUserDocuments($userData['sub'], $submission_id);

    if (empty($userDocuments)) {
      return [];
    }
    $selectedDocument = reset($userDocuments);

    $statusArray = $selectedDocument->getStatusArray();

    if (empty($statusArray)) {
      return [];
    }

    $statusStrings = $this->config->get('statusStrings');
    $langCode = $this->languageManager()->getCurrentLanguage()->getId();
    $statusArray['statusStringHumanReadable'] = $statusStrings[$langCode][$statusArray['value']];

    return $statusArray;
  }

}
