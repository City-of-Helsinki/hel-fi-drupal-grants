<?php

declare(strict_types=1);

namespace Drupal\grants_react_form\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\grants_react_form\ReactDataMapper;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

/**
 * Returns responses for Webform Printify routes.
 */
class ReactFormController extends ControllerBase {

  public function __construct(
    private readonly ReactDataMapper $reactDataMapper,
  ) {
  }

  /**
   * @todo
   */
  public function access() {
    return AccessResult::allowed();
  }

  /**
   * Builds the response.
   *
   * @param string $form
   *   Identifier for form to render.
   * @param string $applicationNumber
   *   Application number of the submission.
   *
   * @return array
   *   Render array.
   */
  public function build(string $form, string $application_number = ''): array {
    return [
      '#theme' => 'grants_react_form',
      '#attached' => [
        'library' => ['hdbt/grants-form'],
        'drupalSettings' => [
          'grants_react_form' => [
              'form' => $form,
              'application_number' => $application_number,
            ],
          ],
      ],
    ];
  }

  /**
   * @todo implement
   */
  public function submit(HttpFoundationRequest $request): JsonResponse {
    $data = $request->request->all();
    $documentToSend = $this->reactDataMapper->getEmptyDataArray();

    foreach($data as $key => $value) {
      // TODO datatype needs separate mapping too.
      // The whole data should be created elsewhere.
      $data = ['ID' => $key, 'value' => $value, 'datatype' => 'string'];
      $this->reactDataMapper->mapReactFieldToAvusDocument($key, $data, $documentToSend);
    }

    return new JsonResponse([
      'data_you_sent' => $documentToSend
    ]);
  }

  /**
   * Handle form data mock function.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform.
   * @param string $submission
   *   Application number.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Response.
   */
  public function save(Webform $webform, string $submission): JsonResponse {
    sleep(3);
    return new JsonResponse(['webform' => $webform->id(), 'applicationNumber' => $submission, 'code' => 200]);
  }

  /**
   * Upload an attachment mock function.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Response.
   */
  public function uploadAttachment(): JsonResponse {
    sleep(3);
    return new JsonResponse(['code' => 201]);
  }

  /**
   * Page title callback.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   Title to show.
   */
  public function title() {
    // @toodo implement
    return 'Liikunta, suunnistuskartta-avustushakemus';
  }

  /**
   * Return a translated description.
   *
   * @param array $element
   *   Element.
   *
   * @return string
   *   Translated string.
   */
  public function getTranslatedDescription(array $element): string {
    return $element['#description'];
  }

  /**
   * Checks if a translated title field exists and returns it.
   *
   * @param array $element
   *   Element to check.
   * @param array $translatedFields
   *   Translated fields.
   *
   * @return array
   *   Selected translated field.
   */
  public function getTranslatedOptions(array $element, array $translatedFields): array {
    if (!empty($translatedFields[$element['#id']]) && isset($translatedFields[$element['#id']]['#options']) && is_array($translatedFields[$element['#id']]['#options'])) {
      return $translatedFields[$element['#id']]['#options'];
    }
    return $element['#options'];
  }

}
