<?php

declare(strict_types=1);

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\Plugin\WebformElement\CompensationsComposite;
use Drupal\grants_metadata\InputmaskHandler;
use Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Grants Handler routes.
 */
final class AtvPrintViewController extends ControllerBase {

  const ISO8601 = "/^(?:[1-9]\d{3}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8])" .
  "|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)" .
  "|(?:[1-9]\d(?:0[48]|[2468][048]|[13579][26])" .
  "|(?:[2468][048]|[13579][26])00)-02-29)(T(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:\.\d{1,9})" .
  "?(?:Z|[+-][01]\d:[0-5]\d))?$/";

  use StringTranslationTrait;

  /**
   * Constructs a new AtvPrintViewController object.
   *
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   Application getter.
   */
  public function __construct(
    private readonly ApplicationGetterService $applicationGetterService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AtvPrintViewController {
    return new self(
      $container->get('grants_handler.application_getter_service')
    );
  }

  /**
   * Builds the response.
   *
   * @param string $submission_id
   *   The submission id.
   *
   * @return array
   *   The response.Render array.
   */
  public function __invoke(string $submission_id): array {
    $isSubventionType = FALSE;
    $subventionType = '';
    try {
      /** @var \Drupal\helfi_atv\AtvDocument $atv_document */
      $atv_document = $this->applicationGetterService->getAtvDocument($submission_id);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException('Application ' . $submission_id . ' not found.');
    }
    $langcode = $atv_document->getMetadata()['language'];

    $newPages = [];
    // Iterate over regular fields.
    $compensation = $atv_document->jsonSerialize()['content']['compensation'];

    foreach ($compensation as $page) {
      if (!is_array($page)) {
        continue;
      }
      foreach ($page as $field) {
        $this->transformField($field, $newPages, $isSubventionType, $subventionType, $langcode);
      }
    }
    $attachments = $atv_document->jsonSerialize()['content']['attachmentsInfo'];
    foreach ($attachments as $page) {
      if (!is_array($page)) {
        continue;
      }
      foreach ($page as $field) {
        $this->transformField($field, $newPages, $isSubventionType, $subventionType, $langcode);
      }
    }

    // Sort the fields based on weight.
    foreach ($newPages as $pageKey => $page) {
      foreach ($page['sections'] as $sectionKey => $section) {
        usort($newPages[$pageKey]['sections'][$sectionKey]['fields'], function ($fieldA, $fieldB) {
          return $fieldA['weight'] - $fieldB['weight'];
        });
      }
    }

    if (isset($compensation['additionalInformation'])) {
      $tOpts = [
        'context' => 'grants_handler',
        'langcode' => $langcode,
      ];
      $field = [
        'ID' => 'additionalInformationField',
        'value' => $compensation['additionalInformation'],
        'valueType' => 'string',
        'label' => $this->t('Additional Information', [], $tOpts),
        'weight' => 1,
      ];
      $sections = [];
      $sections['section'] = [
        'label' => $this->t('Additional information concerning the application', [], $tOpts),
        'id' => 'additionalInformationPageSection',
        'weight' => 1,
        'fields' => [$field],
      ];
      $newPages['additionalInformation'] = [
        'label' => $this->t('Additional Information', [], $tOpts),
        'id' => 'additionalInformationPage',
        'sections' => $sections,
      ];
    }

    // Reorder the first section of the form in weight order.
    if (isset($newPages[1]['sections'])) {
      usort(
        $newPages[1]['sections'],
        fn($a, $b) => $a['weight'] > $b['weight']
      );
    }

    // Set correct template.
    return [
      '#theme' => 'grants_handler_print_atv_document',
      '#atv_document' => $atv_document->jsonSerialize(),
      '#pages' => $newPages,
      '#document_langcode' => $atv_document->getMetadata()['language'],
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];
  }

  /**
   * Helper funtion to transform ATV data for print view.
   *
   * @param mixed $field
   *   Field.
   * @param array $pages
   *   Form pages.
   * @param bool $isSubventionType
   *   Is subvention type.
   * @param string $subventionType
   *   Subvention type.
   * @param string $langcode
   *   Language code.
   */
  private function transformField(mixed $field, array &$pages, bool &$isSubventionType, string &$subventionType, string $langcode): void {
    if (isset($field['ID'])) {
      $labelData = json_decode($field['meta'], TRUE);
      if (!$labelData || $labelData['element']['hidden']) {
        return;
      }

      $this->handleContent($field, $labelData, $langcode, $isSubventionType, $subventionType);

      if ($field['value'] === '') {
        $field['value'] = '-';
      }

      $newField = [
        'ID' => $field['ID'],
        'value' => $labelData['element']['valueTranslation'] ?? $field['value'],
        'valueType' => $field['valueType'],
        'label' => $labelData['element']['label'],
        'weight' => $labelData['element']['weight'],
      ];
      $pageNumber = $labelData['page']['number'];
      if (!isset($pages[$pageNumber])) {
        $pages[$pageNumber] = [
          'label' => $labelData['page']['label'],
          'id' => $labelData['page']['id'],
          'sections' => [],
        ];
      }
      $sectionId = $labelData['section']['id'];
      if (!isset($pages[$pageNumber]['sections'][$sectionId])) {
        $pages[$pageNumber]['sections'][$sectionId] = [
          'label' => $labelData['section']['label'],
          'id' => $labelData['section']['id'],
          'weight' => $labelData['section']['weight'],
          'fields' => [],
        ];
      }
      $pages[$pageNumber]['sections'][$sectionId]['fields'][] = $newField;
      return;
    }
    $isSubventionType = FALSE;
    $subventionType = '';

    if (is_array($field)) {
      foreach ($field as $subField) {
        $this->transformField($subField, $pages, $isSubventionType, $subventionType, $langcode);
      }
    }
  }

  /**
   * Handle application content fields.
   *
   * @param array $field
   *   Field.
   * @param array $labelData
   *   Label data.
   * @param string $langcode
   *   Language code.
   * @param bool $isSubventionType
   *   Is subvention type.
   * @param string $subventionType
   *   Subvention type.
   */
  private function handleContent(
    array &$field,
    array $labelData,
    string $langcode,
    bool &$isSubventionType,
    string &$subventionType,
  ): void {
    $this->handleApplicantType($field, $langcode);
    $this->handleDates($field);
    $this->handleInputMasks($field, $labelData);
    $this->handleIssuer($field, $langcode);
    $this->handleSection($field, $labelData);
    $this->handleLiitteetSection($field);
    $this->handleSubventionType($field, $labelData, $langcode, $isSubventionType, $subventionType);
    $this->handleRole($field);
    $this->handleBooleanValues($field, $langcode);
  }

  /**
   * Handle applicant type.
   *
   * @param array $field
   *   Field.
   * @param string $langcode
   *   Language code.
   */
  private function handleApplicantType(array &$field, string $langcode): void {
    if ($field['ID'] === 'applicantType' && $field['value'] === 'registered_community') {
      $field['value'] = '' . $this->t('Registered community', [], ['langcode' => $langcode]);
    }
  }

  /**
   * Handle dates.
   *
   * @param array $field
   *   Field.
   */
  private function handleDates(array &$field): void {
    if (preg_match(self::ISO8601, $field['value'])) {
      $field['value'] = date_format(date_create($field['value']), 'd.m.Y');
    }
  }

  /**
   * Handle input masks.
   *
   * @param array $field
   *   Field.
   * @param array $labelData
   *   Label data.
   */
  private function handleInputMasks(array &$field, array $labelData): void {
    if (isset($labelData['element']['input_mask'])) {
      $field['value'] = InputmaskHandler::convertPossibleInputmaskValue($field['value'], $labelData);
    }
  }

  /**
   * Handle issuer.
   *
   * @param array $field
   *   Field.
   * @param string $langcode
   *   Language code.
   */
  private function handleIssuer(array &$field, string $langcode): void {
    if ($field['ID'] === 'issuer') {
      $issuerLanguageOptions = [
        'context' => 'Grant Issuers',
        'langcode' => $langcode,
      ];
      $issuerArray = [
        "1" => $this->t('State', [], $issuerLanguageOptions),
        "3" => $this->t('EU', [], $issuerLanguageOptions),
        "4" => $this->t('Other', [], $issuerLanguageOptions),
        "5" => $this->t('Foundation', [], $issuerLanguageOptions),
        "6" => $this->t("STEA", [], $issuerLanguageOptions),
      ];
      $field['value'] = $issuerArray[$field['value']];
    }
  }

  /**
   * Handle section.
   *
   * @param array $field
   *   Field.
   * @param array $labelData
   *   Label data.
   */
  private function handleSection(array &$field, array &$labelData): void {
    if ($labelData['section']['id'] === 'application_number' || $labelData['section']['id'] === 'status') {
      unset($field);
      unset($labelData['section']);
    }
  }

  /**
   * Handle liitteet section.
   *
   * @param array $field
   *   Field.
   */
  private function handleLiitteetSection(array &$field): void {
    if ($field['ID'] === 'integrationID' || $field['ID'] === 'isNewAttachment' || $field['ID'] === 'fileType') {
      unset($field);
      return;
    }
    if ($field['ID'] === 'isDeliveredLater' || $field['ID'] === 'isIncludedInOtherFile') {
      if ($field['value'] === 'false') {
        unset($field);
        return;
      }
      else {
        $field['value'] = Markup::create('<br>');
      }
    }
    if ($field['ID'] === 'fileName') {
      $field['value'] = Markup::create($field['value'] . '<br><br>');
    }
  }

  /**
   * Handle subventiontypes.
   *
   * @param array $field
   *   Field.
   * @param array $labelData
   *   Label data.
   * @param string $langcode
   *   Language code.
   * @param bool $isSubventionType
   *   Is subvention type.
   * @param string $subventionType
   *   Subvention type.
   */
  private function handleSubventionType(
    array &$field,
    array &$labelData,
    string $langcode,
    bool &$isSubventionType,
    string &$subventionType,
  ): void {
    if ($field['ID'] === 'subventionType') {
      $typeNames = CompensationsComposite::getOptionsForTypes($langcode);
      $subventionType = $typeNames[$field['value']];
      $isSubventionType = TRUE;
    }
    elseif ($isSubventionType) {
      $labelData['element']['label'] = $subventionType;
      $isSubventionType = FALSE;
    }
  }

  /**
   * Handle role.
   *
   * @param array $field
   *   Field.
   */
  private function handleRole(array &$field): void {
    if ($field['ID'] == 'role') {
      $roles = GrantsProfileFormRegisteredCommunity::getOfficialRoles();
      $role = $roles[$field['value']];
      if ($role) {
        $field['value'] = $role;
      }
    }
  }

  /**
   * Handle boolean values.
   *
   * @param array $field
   *   Field.
   * @param string $langcode
   *   Language code.
   */
  private function handleBooleanValues(array &$field, string $langcode): void {
    if (array_key_exists('value', $field)) {
      if ($field['value'] === 'true') {
        $field['value'] = $this->t('Yes', [], [
          'context' => 'grants_handler',
          'langcode' => $langcode,
        ]);
      }
      elseif ($field['value'] === 'false') {
        $field['value'] = $this->t('No', [], [
          'context' => 'grants_handler',
          'langcode' => $langcode,
        ]);
      }
    }
  }

}
