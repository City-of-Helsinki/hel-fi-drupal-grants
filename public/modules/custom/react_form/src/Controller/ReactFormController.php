<?php

declare(strict_types=1);

namespace Drupal\react_form\Controller;

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
   * Builds the response.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform to print.
   * @param string $applicationNumber
   *   Application number of the submission.
   *
   * @return array
   *   Render array.
   */
  public function build(Webform $webform, string $applicationNumber = ''): array {

    /** @var \Drupal\webform\WebformTranslationManager $wftm */
    $wftm = $this->translationManager;

    // Load all translations for this webform.
    $currentLanguage = $this->languageManager->getCurrentLanguage();
    $elementTranslations = $wftm->getElements($webform, $currentLanguage->getId());

    $webformArray = $webform->getElementsDecoded();
    /*
     * Implement logic to create a new submission if there is no application
     * number or load the submission data with the number. Then attach data
     * to the response.
     */

    // Webform.
    return [
      '#theme' => 'react_form',
      '#attached' => [
        'library' => ['react_form/react_app_dev'],
        'drupalSettings' => ['reactApp' => ['webform' => $webformArray]],
      ],
      '#webform' => $webformArray,
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
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform to print.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   Title to show.
   */
  public function title(Webform $webform) {
    return $webform->label();
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
