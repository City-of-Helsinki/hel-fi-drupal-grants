<?php

declare(strict_types=1);

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\Plugin\WebformElement\CompensationsComposite;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\DocumentContentMapper;
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
   *   The application getter service.
   * @param \Drupal\grants_metadata\AtvSchema $atvSchema
   *   The atv schema.
   * @param \Drupal\grants_metadata\ApplicationDataService $applicationDataService
   *   The application data service.
   */
  public function __construct(
    private readonly ApplicationGetterService $applicationGetterService,
    protected AtvSchema $atvSchema,
    protected ApplicationDataService $applicationDataService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AtvPrintViewController {
    return new self(
      $container->get('grants_handler.application_getter_service'),
      $container->get('grants_metadata.atv_schema'),
      $container->get('grants_metadata.application_data_service'),
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

    // Application submission with the old style applicationnumber(GRANTS-...)
    // won't contain the field metadata for some reason and printing breaks.
    $application_number = $atv_document->getMetadata()['applicationnumber'];
    if (str_contains($application_number, 'GRANTS-')) {
      $dataDefinition = $this->applicationGetterService->getDataDefinition($atv_document->getType());

      $sData = DocumentContentMapper::documentContentToTypedData(
        $atv_document->getContent(),
        $dataDefinition,
        $atv_document->getMetadata()
      );

      $typeData = $this->applicationDataService->webformToTypedData($sData);
      $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($application_number);

      $appDocumentContent = $this->atvSchema->typedDataToDocumentContent(
        $typeData,
        $webform_submission,
        $sData,
      );
      $compensation = $appDocumentContent['compensation'];
    }
    else {
      $compensation = $atv_document->jsonSerialize()['content']['compensation'];
    }

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

    // Sort the sections inside a page based on section weight.
    foreach ($newPages as $pageKey => $page) {
      usort($newPages[$pageKey]['sections'], fn($a, $b) => $a['weight'] > $b['weight']);
    }

    // Sort the fields inside the sections based on weight.
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
      $meta = $field['meta'] ?? '{}';
      $labelData = json_decode($meta, TRUE);
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
    array &$labelData,
    string $langcode,
    bool &$isSubventionType,
    string &$subventionType,
  ): void {
    $this->handleApplicantType($field, $langcode);
    $this->handleDates($field);
    $this->handleInputMasks($field, $labelData);
    $this->handleIssuer($field, $langcode);
    $this->handleLiitteetSection($field);
    $this->handleSubventionType($field, $labelData, $langcode, $isSubventionType, $subventionType);
    $this->handleRole($field);
    $this->handleStatus($field, $langcode);
    $this->handleBooleanValues($field, $langcode);
    $this->translateLabels($field, $labelData, $langcode);
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
   * Translate application number and status labels.
   *
   * @param array $field
   *   Field.
   * @param array $labelData
   *   Label data.
   * @param string $langcode
   *   Document language.
   */
  private function translateLabels(array &$field, array &$labelData, string $langcode): void {
    $translatedLabels = [
      'application_number' => [
        'label' => 'Hakemusnumero',
        'translation' => $this->t('Application number', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
      ],
      'application_status' => [
        'label' => 'Hakemuksen tila',
        'translation' => $this->t('Application status', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
      ],
    ];

    foreach ($translatedLabels as $translatedLabel) {
      foreach (['section', 'element'] as $parent) {
        if ($labelData[$parent]['label'] === $translatedLabel['label']) {
          $labelData[$parent]['label'] = $translatedLabel['translation'];
        }
      }
    }

    if ($labelData['section']['id'] === 'application_number' || $labelData['section']['id'] === 'status') {
      unset($field);
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
      $field['value'] = $subventionType;
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
   * Handle status.
   *
   * @param array $field
   *   Field.
   */
  private function handleStatus(array &$field, string $langcode): void {
    if ($field['ID'] == 'status') {
      $statusMap = [
        'DRAFT' => $this->t('Draft', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'SENT' => $this->t('Sent', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'SUBMITTED' => $this->t('Sent - waiting for confirmation', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'RECEIVED' => $this->t('Received', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'PREPARING' => $this->t('In Preparation', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'PENDING' => $this->t('Pending', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'PROCESSING' => $this->t('Processing', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'READY' => $this->t('Ready', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'DONE' => $this->t('Processed', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'REJECTED' => $this->t('Rejected', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'DELETED' => $this->t('Deleted', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'CANCELED' => $this->t('Cancelled', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'CANCELLED' => $this->t('Cancelled', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'CLOSED' => $this->t('Closed', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
        'RESOLVED' => $this->t('Processed', [], ['context' => 'grants_handler', 'langcode' => $langcode]),
      ];

      $field['value'] = $statusMap[$field['value']];
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
