<?php

namespace Drupal\grants_attachments\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_attachments\Element\GrantsAttachments as ElementGrantsAttachments;
use Drupal\grants_handler\EventsService;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'grants_attachments' element.
 *
 * @WebformElement(
 *   id = "grants_attachments",
 *   label = @Translation("Grants attachments"),
 *   description = @Translation("Provides a grants attachment element."),
 *   category = @Translation("Hel.fi elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\grants_attachments\Element\GrantsAttachments
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
final class GrantsAttachments extends WebformCompositeBase {

  /**
   * Avustus2 file types.
   *
   * @var string[]
   *  Array with file types.
   */
  public static array $fileTypes = [
    0 => 'Muu hakemusliite',
    1 => 'Toimintasuunnitelma (Vuodelle, jota hakemus koskee)',
    2 => 'Talousarvio (Vuodelle, jota hakemus koskee)',
    3 => 'Vahvistettu tuloslaskelma ja tase (edelliseltä tilikaudelta)',
    4 => 'Toimintakertomus (edelliseltä tilikaudelta)',
    5 => 'Tilintarkastuskertomus / toiminnantarkastuskertomus edelliseltä tilikaudelta allekirjoitettuna',
    6 => 'Pankin ilmoitus tilinomistajasta tai tiliotekopio (uusilta hakija tai pankkiyhteystiedot muuttuneet)',
    7 => 'Yhteisön säännöt (uusi hakija tai säännöt muuttuneet)',
    8 => 'Vuosikokouksen pöytäkirja allekirjoitettuna',
    10 => 'Vuokrasopimus (haettaessa vuokra-avustusta)',
    12 => 'Yhdistykseen kuuluvat paikallisosastot',
    13 => 'Ote yhdistysrekisteristä (uudet seurat)',
    14 => 'Ammattilaisproduktioilta työryhmän jäsenten ansioluettelot',
    15 => 'Kopio vuokrasopimuksesta (uusi hakija tai muuttunut sopimus)',
    16 => 'Laskukopiot (ajalta, jolta kompensaatiota haetaan)',
    17 => 'Myyntireskontra',
    19 => 'Projektisuunnitelma',
    22 => 'Talousarvio',
    23 => 'Arviointisuunnitelma',
    25 => 'Toimintaryhmien yhteystiedot-lomake / nuorten toimintaryhmät',
    26 => 'Rekisteriote',
    28 => 'Talousarvio ja toimintasuunnitelma',
    29 => 'Suunnistuskartat, joille avustusta haetaan',
    30 => 'Karttojen valmistukseen liittyvät laskut ja kuitit',
    31 => 'Kuitit kuljetuskustannuksista',
    32 => 'Ennakkotiedot leireistä (Excel liite)',
    36 => 'Tiedot toteutuneista leireistä (excel-liite)',
    37 => 'Tilankäyttöliite',
    38 => 'Tapahtumasuunnitelma',
    39 => 'Palvelusuunnitelma',
    40 => 'Hankesuunnitelma',
    41 => 'Selvitys avustuksen käytöstä',
    42 => 'Seuran toimintatiedot',
    43 => 'Tilinpäätös',
    44 => 'Hakemusliite',
    45 => 'Pankkitilivahvistus',
  ];

  /**
   * Events service.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * Constructs a new GrantsAttachments element.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\grants_handler\EventsService $eventsService
   *   The events service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EventsService $eventsService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventsService = $eventsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('grants_handler.events_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    // Here you define your webform element's default properties,
    // which can be inherited.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultProperties
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultBaseProperties
    return [
      'multiple' => '',
      'size' => '',
      'minlength' => '',
      'maxlength' => '',
      'placeholder' => '',
      'filetype' => '',
      'allowed_filetypes' => ElementGrantsAttachments::DEFAULT_ALLOWED_FILE_TYPES,
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $tOpts = ['context' => 'grants_attachments'];

    $form = parent::form($form, $form_state);
    // Here you can define and alter a webform element's properties UI.
    // Form element property visibility and default values are defined via
    // ::defaultProperties.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::form
    // @see \Drupal\webform\Plugin\WebformElement\TextBase::form
    $form['element']['filetype'] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment filetype', [], $tOpts),
      '#options' => self::$fileTypes,
    ];

    $form['element']['allowed_filetypes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed filetypes', [], $tOpts),
      '#description' => $this->t('Comma separated list of allowed filetypes.', [], $tOpts),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {

    if (!isset($element['#webform_key']) && isset($element['#value'])) {
      return $element['#value'];
    }

    $webform_key = $element['#webform_key'];

    $data = $webform_submission->getData();
    $value = NULL;

    if (isset($data[$webform_key])) {
      $value = $data[$webform_key];
    }
    else {
      foreach (AttachmentHandler::getAttachmentFieldNames($data["application_number"]) as $fieldName) {
        if (!isset($data[$fieldName])) {
          continue;
        }
        $fieldData = $data[$fieldName];

        // $element["#webform_parents"][2]
        if (in_array($fieldName, $element["#webform_parents"])) {
          $value = $fieldData;
        }

      }
    }

    // Is value is NULL and there is a #default_value, then use it.
    if ($value === NULL && isset($element['#default_value'])) {
      $value = $element['#default_value'];
    }

    $this->handleMultiDeltaValue($value, $options);
    return $value;

  }

  /**
   * Handle values for multivalue and composite elements.
   *
   * @param mixed $value
   *   Element value.
   * @param mixed $options
   *   An array of options.
   */
  private function handleMultiDeltaValue(&$value, $options) {
    if (is_array($value)) {
      // Return $options['delta'] which is used by tokens.
      // @see _webform_token_get_submission_value()
      if (isset($options['delta'])) {
        $value = $value[$options['delta']] ?? NULL;
      }

      // Return $options['composite_key'] which is used by composite elements.
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::formatTableColumn
      if ($value && isset($options['composite_key'])) {
        $value = $value[$options['composite_key']] ?? NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    $tOpts = ['context' => 'grants_attachments'];

    $submissionData = $webform_submission->getData();
    $attachmentEvents = $this->eventsService->filterEvents($submissionData['events'] ?? [], 'HANDLER_ATT_OK');

    if (!is_array($value)) {
      return [];
    }

    // Prevent old account confirmation files from rendering
    // if the user changed bank accounts.
    if (isset($value['fileType']) && $value['fileType'] == 45) {
      $accountNumber = $submissionData['bank_account']['account_number'] ?? NULL;
      $description = $value['description'] ?? NULL;
      if (is_string($accountNumber) &&
        is_string($description) &&
        !str_contains($description, $accountNumber)) {
        return [];
      }
    }

    // Hide value, if only description field is filled.
    // We filter all attachment fields without an attachment
    // during ATV save.
    if (
      (isset($value['description']) && !empty($value['description'])) &&
      (!isset($value['attachmentName']) || empty($value['attachmentName'])) &&
      $value['fileType'] == '0'
    ) {
      return [];
    }

    // This notes that we have uploaded file in process.
    if (isset($value['attachment']) && $value['attachment'] !== NULL) {
      // Load file.
      /** @var \Drupal\file\FileInterface|null $file */
      $file = $this->entityTypeManager
        ->getStorage('file')
        ->load($value['attachment']);
      // File is found, then show filename.
      if ($file) {
        $lines[] = ($file !== NULL) ? $file->get('filename')->value : '';
      }
    }

    if (isset($value["integrationID"]) && !empty($value["integrationID"])) {
      // Add filename if it has been uploaded earlier.
      if (isset($value["fileName"]) && !empty($value["fileName"]) && !in_array($value["fileName"], $lines)) {
        $lines[] = '<strong>' . $value["fileName"] . '</strong>';
      }
      elseif (isset($value["attachmentName"]) && !empty($value["attachmentName"]) && !in_array($value["attachmentName"], $lines)) {
        $lines[] = '<strong>' . $value["attachmentName"] . '</strong>';
      }
    }

    // And if not, then show other fields, which cannot be selected
    // while attachment file exists.
    if (isset($value["isDeliveredLater"]) && ($value["isDeliveredLater"] === 'true' ||
        $value["isDeliveredLater"] === '1')) {
      if (is_string($element["#webform_composite_elements"]["isDeliveredLater"]["#title"])) {
        $lines[] = $element["#webform_composite_elements"]["isDeliveredLater"]["#title"];
      }
      else {
        $lines[] = $element["#webform_composite_elements"]["isDeliveredLater"]["#title"]->render();
      }

    }
    if (isset($value["isIncludedInOtherFile"]) && ($value["isIncludedInOtherFile"] === 'true' ||
        $value["isIncludedInOtherFile"] === '1')) {
      if (is_string($element["#webform_composite_elements"]["isIncludedInOtherFile"]["#title"])) {
        $lines[] = $element["#webform_composite_elements"]["isIncludedInOtherFile"]["#title"];
      }
      else {
        $lines[] = $element["#webform_composite_elements"]["isIncludedInOtherFile"]["#title"]->render();
      }
    }

    if (isset($value["description"]) && (isset($element["#webform_key"]) &&
        $element["#webform_key"] == 'muu_liite')) {
      $lines[] = $value["description"];
    }

    // If filename or attachmentname is set, print out upload
    // status from events.
    if ((isset($value["fileName"]) && !empty($value["fileName"])) || (isset($value["attachmentName"]) &&
        !empty($value["attachmentName"]))) {
      if (isset($value["attachmentName"]) && in_array($value["attachmentName"], $attachmentEvents["event_targets"])) {
        $lines[] = '<span class="upload-ok-icon">' . $this->t('Upload OK', [], $tOpts) . '</span>';
      }
      elseif (isset($value["fileName"]) && in_array($value["fileName"], $attachmentEvents["event_targets"])) {
        $lines[] = '<span class="upload-ok-icon">' . $this->t('Upload OK', [], $tOpts) . '</span>';
      }
      // If we have integrationID & status is justuploaded then we know
      // upload was fine.
      elseif (isset($value["integrationID"]) && $value['fileStatus'] == 'justUploaded') {
        $lines[] = '<span class="upload-ok-icon">' . $this->t('Upload OK', [], $tOpts) . '</span>';
      }
      else {
        $lines[] = '<span class="upload-fail-icon">' . $this->t('Upload pending / File missing', [], $tOpts) . '</span>';
      }
    }

    return $lines;
  }

}
