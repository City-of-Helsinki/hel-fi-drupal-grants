<?php

declare(strict_types=1);

namespace Drupal\grants_webform_print\Controller;

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
class GrantsWebformPrintController extends ControllerBase {

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
   * @return GrantsWebformPrintController
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
    // Pass decoded array & translations to traversing.
    $webformArray = $this->traverseWebform($webformArray, $elementTranslations);

    unset($webformArray['actions']);

    // Webform.
    return [
      '#theme' => 'grants_webform_print_webform',
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
   * Traverse through a webform to make changes to fit the print styles.
   *
   * @param array $webformArray
   *   The Webform in question.
   * @param array $elementTranslations
   *   Translations for elements.
   *
   * @return array
   *   If there is translated value for given field, they're here.
   */
  private function traverseWebform(array $webformArray, array $elementTranslations): array {
    $transfromed = [];
    foreach ($webformArray as $key => $item) {
      $transfromed[$key] = $this->fixWebformElement($item, $key, $elementTranslations);
    }
    return $transfromed;
  }

  /**
   * Clean out unwanted things from form elements.
   *
   * @param array $element
   *   Element to fix.
   * @param string $key
   *   Key on the form.
   * @param array $translatedFields
   *   If there is translated value for given field, they're here.
   */
  private function fixWebformElement(array $element, string $key, array $translatedFields): array {

    // Remove states from printing.
    unset($element["#states"]);
    // In case of custom component, the element parts are in #element
    // so we need to sprad those out for printing.
    if (isset($element['#element'])) {
      $elements = $element['#element'];
      unset($element['#element']);
      $element = [
        ...$element,
        ...$elements,
      ];
    }
    // Look for non render array parts from element.
    $children = array_filter(array_keys($element), function ($key) {
      return !str_contains($key, '#');
    });

    // If there is some, then loop as long as there is som.
    if ($children) {
      $translations = $translatedFields[$key]['#element'] ?? $translatedFields;
      foreach ($children as $childKey) {
        $element[$childKey] = $this->fixWebformElement($element[$childKey], $childKey, $translations);
      }

    }

    // Apply translations to the element itself.
    if (!empty($translatedFields[$key])) {
      // Unset type since we do not want to override that from trans.
      unset($translatedFields[$key]['#type']);
      if (isset($translatedFields[$key]['#description']) && !is_array($translatedFields[$key]['#description'])) {
        $element['#description'] = $translatedFields[$key]['#description'] . '<br>';
      }
      else {
        $element['#description'] = '';
      }
      if (isset($translatedFields[$key]['#help']) && !is_array($translatedFields[$key]['#help'])) {
        $element['#description'] = $element['#description'] . $translatedFields[$key]['#help'];
      }
      foreach ($translatedFields[$key] as $fieldName => $translatedValue) {
        // Just because.
        if ($fieldName == '#help' || $fieldName == '#description') {
          continue;
        }
        // Replace with translated text. only if it's an string.
        if (isset($element[$fieldName]) && !is_array($translatedValue)) {
          $element[$fieldName] = $translatedValue;
        }
      }
    }
    // If there are no translations, just manipulate description.
    else {
      if (isset($element['#help'])) {
        if (isset($element['#description'])) {
          $element['#description'] = $element['#description'] . '<br>' . $element['#help'];
        }
        else {
          $element['#description'] = $element['#help'];
        }
      }
    }
    unset($element['#help']);
    // If no id for the field, we get warnigns.
    $element['#id'] = $key;

    // Force description display after element.
    $element['#description_display'] = 'after';
    unset($element['#attributes']['class']);
    // Field type specific alters.
    if (isset($element['#type'])) {
      // Make wizard pages show as containers.
      if ($element['#type'] === 'webform_wizard_page') {
        $element['#type'] = 'container';
      }
      else {
        $element['#attributes']['readonly'] = 'readonly';
      }
      // Custom components as select.
      if ($element['#type'] === 'community_address_composite') {
        $element['#type'] = 'textarea';
      }
      if ($element['#type'] === 'community_officials_composite') {
        $element['#type'] = 'textarea';
      }
      if ($element['#type'] === 'bank_account_composite') {
        $element['#type'] = 'textfield';
      }
      if ($element['#type'] === 'email') {
        $element['#type'] = 'textfield';
      }
      if ($element['#type'] === 'number') {
        $element['#type'] = 'textfield';
      }
      if ($element['#type'] === 'date') {
        $element['#type'] = 'textfield';
      }
      if ($element['#type'] === 'datetime') {
        $element['#type'] = 'textfield';
      }
      // Subventions as hidden textfield.
      if ($element['#type'] === 'grants_compensations') {
        $element['#type'] = 'textfield';
        $element["#attributes"]["class"][] = 'hide-input';
      }

      // Premises as hidden textfield.
      if ($element['#type'] === 'premises_composite') {
        $element['#type'] = 'premises_composite_print';
      }
      // Get attachment descriptions from subfields.
      if ($element['#type'] === 'grants_attachments') {
        $element['#type'] = 'textfield';
        $element["#attributes"]["class"][] = 'hide-input';
        $element["#description__access"] = TRUE;
        if (!empty($element["#attachment__description"])) {
          $element['#description'] = $element["#attachment__description"];
        }
      }
      // Show no radios, hidden textfields.
      if ($element['#type'] === 'textarea' || $element['#type'] === 'textfield') {
        $element['#value'] = '';
      }
      if ($element['#type'] === 'hidden') {
        $element['#type'] = 'markup';
      }
      if ($element['#type'] === 'textarea') {
        $element['#type'] = 'markup';
        $element['#markup'] = '<p><strong>' . $this->getTranslatedTitle($element) . '</strong><br>';
        $element['#markup'] .= '<div class="hds-text-input__input-wrapper"><div class="hide-input form-text hds-text-input__input hds-text-input__textarea webform_large" type="text">&nbsp;</div></div>';
        if (isset($element['#description'])) {
          $element['#markup'] .= '<div>
 <div id="talousarvio--description" class="webform-element-description"><span>' . $this->getTranslatedDescription($element, $translatedFields) . '</span></div>
    </div>';
          unset($element['#description']);

        }
      }
      if ($element['#type'] === 'textfield') {
        $element['#type'] = 'markup';
        $element['#markup'] = '<p><strong>' . $this->getTranslatedTitle($element) . '</strong><br>';
        $element['#markup'] .= '<div class="hds-text-input__input-wrapper"><div class="hide-input form-text hds-text-input__input webform_large" type="text">&nbsp;</div></div>';
        if (isset($element['#description'])) {
          $element['#markup'] .= '<div>
 <div id="talousarvio--description" class="webform-element-description"><span>' . $this->getTranslatedDescription($element) . '</span></div>
    </div>';
          unset($element['#description']);
        }
      }
      if ($element['#type'] === 'webform_section') {
        $element['#title_tag'] = 'h3';
      }
      if ($element['#type'] === 'select' || $element['#type'] === 'checkboxes' || $element['#type'] === 'radios') {
        $element['#type'] = 'markup';
        $element['#markup'] = '<p><strong>' . $this->getTranslatedTitle($element) . '</strong><br>';
        foreach ($this->getTranslatedOptions($element, $translatedFields) as $key => $value) {
          $element['#markup'] .= '▢ ' . $value . '<br>';
        }
        $element['#markup'] .= '<br></p>';
      }
    }
    if ($element['#type'] === 'grants_budget_income_static') {
      $element['#type'] = 'markup';
      $element['#markup'] = '';
      $fields = GrantsBudgetIncomeStatic::getFieldNames();
      foreach ($fields as $name => $title) {
        // This is fast and dirty way to filter fields.
        if (isset($element['#' . $name . '__access']) && $element['#' . $name . '__access'] === FALSE) {
          continue;
        }
        $element['#markup'] .= '<p><strong>' . $title . '</strong><br>';
        $element['#markup'] .= '<div class="hds-text-input__input-wrapper"><div class="hide-input form-text hds-text-input__input webform_large" type="text">&nbsp;</div></div>';
      }
    }
    if ($element['#type'] === 'grants_budget_cost_static') {
      $element['#type'] = 'markup';
      $element['#markup'] = '';
      $fields = GrantsBudgetCostStatic::getFieldNames();
      foreach ($fields as $name => $title) {
        // This is fast and dirty way to filter fields.
        if (isset($element['#' . $name . '__access']) && $element['#' . $name . '__access'] === FALSE) {
          continue;
        }
        $element['#markup'] .= '<p><strong>' . $title . '</strong><br>';
        $element['#markup'] .= '<div class="hds-text-input__input-wrapper"><div class="hide-input form-text hds-text-input__input webform_large" type="text">&nbsp;</div></div>';
      }
    }
    if ($element['#type'] == 'grants_budget_other_cost' || $element['#type'] == 'grants_budget_other_income') {
      $explanation = $element['#type'] == 'grants_budget_other_cost' ? $this->t('Cost explanation', [], ['context' => 'grants_budget_components']) : $this->t('Income explanation', [], ['context' => 'grants_budget_components']);
      $element['#type'] = 'markup';
      $element['#markup'] = '<p><strong>' . $this->getTranslatedTitle($element) . '</strong><br>';
      $element['#markup'] .= $explanation;
      $element['#markup'] .= '<div class="hds-text-input__input-wrapper"><div class="hide-input form-text hds-text-input__input webform_large" type="text">&nbsp;</div></div>';
      $element['#markup'] .= $this->t('Amount (€)', [], ['context' => 'grants_budget_components']);
      $element['#markup'] .= '<div class="hds-text-input__input-wrapper"><div class="hide-input form-text hds-text-input__input webform_large" type="text">&nbsp;</div></div>';
    }
    return $element;
  }

  /**
   * Return a translated title.
   *
   * @param array $element
   *   Element to check.
   *
   * @return string
   *   Selected translated field.
   */
  public function getTranslatedTitle(array $element): string {
    return $element['#title'];
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
