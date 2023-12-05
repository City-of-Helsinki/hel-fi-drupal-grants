<?php

declare(strict_types=1);

namespace Drupal\react_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_budget_components\Element\GrantsBudgetCostStatic;
use Drupal\grants_budget_components\Element\GrantsBudgetIncomeStatic;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformTranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   *
   * @return array
   *   Render array.
   */
  public function build(Webform $webform): array {

    /** @var \Drupal\webform\WebformTranslationManager $wftm */
    $wftm = $this->translationManager;

    // Load all translations for this webform.
    $currentLanguage = $this->languageManager->getCurrentLanguage();
    $elementTranslations = $wftm->getElements($webform, $currentLanguage->getId());

    $webformArray = $webform->getElementsDecoded();


    // Webform.
    return [
      '#theme' => 'react_form',
      '#webform' => $webformArray,
    ];
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

