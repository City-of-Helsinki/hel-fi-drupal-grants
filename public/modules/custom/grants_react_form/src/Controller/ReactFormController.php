<?php

declare(strict_types=1);

namespace Drupal\grants_react_form\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformTranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Webform Printify routes.
 */
class ReactFormController extends ControllerBase {

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   * @param \Drupal\webform\Entity\WebformTranslationManager $translationManager
   *   Translation manager.
   */
  public function __construct(LanguageManagerInterface $languageManager, WebformTranslationManager $translationManager) {
    $this->languageManager = $languageManager;
    $this->translationManager = $translationManager;

  }

  /**
   * Static factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Containers.
   *
   * @return ReactFormController
   *   Controller object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('webform.translation_manager'),
    );
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
              'application_number' => $application_number
            ]
          ],
      ],
    ];
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
