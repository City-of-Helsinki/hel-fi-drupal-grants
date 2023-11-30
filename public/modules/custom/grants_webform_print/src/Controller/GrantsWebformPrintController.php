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
    // so we need to spread those out for printing.
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

    // If there is some, then loop as long as there is some.
    foreach ($children as $childKey) {
      $translations = $translatedFields[$key]['#element'] ?? $translatedFields;
      $element[$childKey] = $this->fixWebformElement($element[$childKey], $childKey, $translations);
    }

    // Apply translations to the element itself.
    if (!empty($translatedFields[$key])) {
      // Unset type since we do not want to override that from trans.
      unset($translatedFields[$key]['#type']);
      $element['#description'] = preg_replace('/^(<br>)/',
          '',
          ($translatedFields[$key]['#attachment_desription'] ?? '') . '<br>' .
          ($translatedFields[$key]['#description'] ?? '') . '<br>' .
          ($translatedFields[$key]['#help'] ?? '')) . '<br>';
      unset($translatedFields[$key]['#attachment_desription']);
      unset($translatedFields[$key]['#description']);
      unset($translatedFields[$key]['#help']);
      foreach ($translatedFields[$key] as $fieldName => $translatedValue) {
        // Replace with translated text. only if it's an string.
        if (isset($element[$fieldName]) && !is_array($translatedValue)) {
          $element[$fieldName] = $translatedValue;
        }
      }
    }
    // If there are no translations, just manipulate description.
    else {
      $element['#description'] = preg_replace('/^(<br>)*/',
          '',
        ($element["#attachment__description"] ?? '') . '<br>' .
        ($element['#description'] ?? '') . '<br>' .
        ($element['#help'] ?? ''));
    }
    unset($element['#help']);
    // If no id for the field, we get warnigns.
    $element['#id'] = $key;

    // Force description display after element.
    $element['#description_display'] = 'after';
    unset($element['#attributes']['class']);
    // Field type specific alters.
    $element['#attributes']['readonly'] = 'readonly';
    $element['#title'] = $this->getTranslatedTitle($element) ?? $element['#title'] ?? '';
    $element['#description'] = $this->getTranslatedDescription($element, $translatedFields) ??
      $element['#description'] ?? '';

    switch ($element['#type'] ?? '') {
      case 'webform_wizard_page':
        $element['#type'] = 'container';
        unset($element['#attributes']['readonly']);
        break;

      case 'premises_composite':
        $element['#theme'] = 'premises_composite_print';
        break;

      case 'community_address_composite':
      case 'community_officials_composite':
      case 'textarea':
        $element['#value'] = '';
        $element['#theme'] = 'textarea_print';
        $element['#type'] = 'textarea';
        $element['#title_display'] = FALSE;
        break;

      case 'email':
      case 'number':
      case 'date':
      case 'datetime':
      case 'grants_compensations':
      case 'grants_attachments':
      case 'bank_account_composite':
      case 'textfield':
        $element['#value'] = '';
        $element["#description__access"] = TRUE;
        $element['#theme'] = 'textfield_print';
        $element['#type'] = 'textfield';
        $element['#title_display'] = FALSE;
        break;

      case 'hidden':
        $element['#type'] = 'markup';
        break;

      case 'webform_section':
        $element['#title_tag'] = 'h3';
        break;

      case 'select':
      case 'checkboxes':
      case 'radios':
        $element['#title_display'] = FALSE;
        $element['#type'] = 'select';
        $element['#theme'] = 'radios_print';
        $element['#options'] = $this->getTranslatedOptions($element, $translatedFields);
        break;

      case 'grants_budget_income_static':
        $element['#type'] = 'markup';
        $element[] = $this->renderBudgetFields($element, GrantsBudgetIncomeStatic::getFieldNames());
        break;

      case 'grants_budget_cost_static':
        $element['#type'] = 'markup';
        $element[] = $this->renderBudgetFields($element, GrantsBudgetCostStatic::getFieldNames());
        break;

      case 'grants_budget_other_cost':
      case 'grants_budget_other_income':
        $element['#title'] = $this->getTranslatedTitle($element);
        $element[] = $this->renderOtherBudgetFields($element);
        break;

      default:
        break;
    }

    return $element;
  }

  /**
   * Create Other Budget component print render.
   *
   * @param array $element
   *   The element getting print rendered.
   *
   * @return array
   *   A render array.
   */
  private function renderOtherBudgetFields(array $element) : array {
    $explanation = $element['#type'] == 'grants_budget_other_cost' ?
      $this->t('Cost explanation', [], ['context' => 'grants_budget_components']) :
      $this->t('Income explanation', [], ['context' => 'grants_budget_components']);
    $render[$element['#type'] . '_description'] = [
      '#id' => $element['#type'] . '_description',
      '#type' => 'textfield',
      '#theme' => 'textfield_print',
      '#title' => $explanation,
    ];
    $render[$element['#type'] . '_amount'] = [
      '#id' => $element['#type'] . '_amount',
      '#type' => 'textfield',
      '#theme' => 'textfield_print',
      '#title' => $this->t('Amount (€)', [], ['context' => 'grants_budget_components']),
    ];
    return $render;
  }

  /**
   * Create Budget component print render.
   *
   * @param array $element
   *   The element getting print rendered.
   * @param array $fieldNames
   *   The field names of the budget in question.
   *
   * @return array
   *   A render array.
   */
  private function renderBudgetFields(array $element, array $fieldNames) : array {
    $markup = [];
    foreach ($fieldNames as $name => $title) {
      // This is fast and dirty way to filter fields.
      if (($element['#' . $name . '__access'] ?? TRUE) === TRUE) {
        $markup[$name] = [
          '#id' => $element['#id'] . '_' . $name,
          '#title' => $title,
          '#type' => 'textfield',
          '#theme' => 'textfield_print',
        ];
      }
    }
    return $markup;
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
    return $element['#title'] ?? '';
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
    return $element['#description'] ?? '';
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
    if (!empty($translatedFields[$element['#id']])
      && isset($translatedFields[$element['#id']]['#options'])
      && is_array($translatedFields[$element['#id']]['#options'])) {
      return $translatedFields[$element['#id']]['#options'];
    }
    return $element['#options'];
  }

}
