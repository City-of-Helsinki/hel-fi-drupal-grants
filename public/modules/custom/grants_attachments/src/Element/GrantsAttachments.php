<?php

namespace Drupal\grants_attachments\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\file\Entity\File;
use Drupal\grants_attachments\AttachmentHandlerHelper;
use Drupal\grants_handler\GrantsErrorStorage;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Utility\WebformElementHelper;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a 'grants_attachments'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * @FormElement("grants_attachments")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\grants_attachments\Element\GrantsAttachments
 */
class GrantsAttachments extends WebformCompositeBase {

  const DEFAULT_ALLOWED_FILE_TYPES = 'doc,docx,gif,jpg,jpeg,pdf,png,ppt,pptx,rtf,txt,xls,xlsx,zip';
  const DEFAULT_FILENAME_LENGTH = 100;

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'grants_attachments'];
  }

  /**
   * Build webform element based on data in ATV document.
   *
   * @param array $element
   *   Element that is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Full form.
   *
   * @return array[]
   *   Form API element for webform element.
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    $tOpts = ['context' => 'grants_attachments'];

    $element['#tree'] = TRUE;
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    /** @var \Drupal\webform\WebformSubmissionForm $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $formObject->getEntity();
    $submissionData = $submission->getData();

    $storage = $form_state->getStorage();
    $errors = GrantsErrorStorage::getErrors();

    $arrayKey = $element['#webform_key'];
    if (isset($element['#parents'][1]) && $element['#parents'][1] == 'items') {
      $arrayKey .= '_' . $element['#parents'][2];
    }

    if (isset($errors[$arrayKey])) {
      $errors = $errors[$arrayKey];
      $element['#attributes']['class'][] = $errors['class'];
      $element['#attributes']['error_label'] = $errors['label'];
    }

    // Attachment has been deleted, show default component state.
    if (isset($storage['deleted_attachments'][$arrayKey]) && $storage['deleted_attachments'][$arrayKey]) {
      unset($element['attachmentName']);
      return $element;
    }

    if (isset($submissionData[$element['#webform_key']]) && is_array($submissionData[$element['#webform_key']])) {
      $dataForElement = $element['#value'];

      // When navigating back in a multistep form, we need to restore the file
      // from storage since it might be lost during form rebuilds.
      if ($dataForElement['integrationID'] && isset($storage['fids_info']) && $dataForElement) {
        foreach ($storage['fids_info'] as $finfo) {
          if ($dataForElement['integrationID'] == $finfo['integrationID']) {
            $dataForElement = $finfo;
            break;
          }
        }
      }

      $uploadStatus = $dataForElement['fileStatus'] ?? NULL;

      if ($uploadStatus === NULL && !empty($dataForElement['integrationID'])) {
        $uploadStatus = 'uploaded';
      }

      if (isset($dataForElement["fileType"])) {
        $element["fileType"]["#value"] = $dataForElement["fileType"];
      }
      elseif (isset($element["#filetype"])) {
        $element["fileType"]["#value"] = $element["#filetype"];
      }

      if (isset($dataForElement["integrationID"]) && !empty($dataForElement["integrationID"])) {
        $element["integrationID"]["#value"] = $dataForElement["integrationID"];
        $element["fileStatus"]["#value"] = 'uploaded';
      }

      if (isset($dataForElement['isDeliveredLater'])) {
        $element["isDeliveredLater"]["#default_value"] = $dataForElement['isDeliveredLater'] == 'true';
        if ($element["isDeliveredLater"]["#default_value"]) {
          $element["fileStatus"]["#value"] = 'deliveredLater';
        }
        if ($dataForElement['isDeliveredLater'] == '1') {
          $element["isDeliveredLater"]['#default_value'] = TRUE;
        }
      }
      if (isset($dataForElement['isIncludedInOtherFile'])) {
        $value = $dataForElement['isIncludedInOtherFile'] == 'true' || $dataForElement['isIncludedInOtherFile'] == '1';
        $element["isIncludedInOtherFile"]["#default_value"] = $value;
        if ($element["isIncludedInOtherFile"]["#default_value"]) {
          $element["fileStatus"]["#value"] = 'otherFile';
        }
      }
      if (!empty($dataForElement['fileName']) || !empty($dataForElement['attachmentName'])) {
        $element['attachmentName'] = [
          '#type' => 'textfield',
          '#default_value' => $dataForElement['fileName'] ?? $dataForElement['attachmentName'],
          '#value' => $dataForElement['fileName'] ?? $dataForElement['attachmentName'],
          '#readonly' => TRUE,
          '#attributes' => ['readonly' => 'readonly'],
        ];

        $element["isIncludedInOtherFile"]["#disabled"] = TRUE;
        $element["isDeliveredLater"]["#disabled"] = TRUE;

        unset($element['isDeliveredLater']['#states']);
        unset($element['isIncludedInOtherFile']['#states']);

        $element["attachment"]["#access"] = FALSE;
        $element["attachment"]["#readonly"] = TRUE;
        $element["attachment"]["#attributes"] = ['readonly' => 'readonly'];

        if (isset($element["isNewAttachment"])) {
          $element["isNewAttachment"]["#value"] = FALSE;
        }

        $element["fileStatus"]["#value"] = 'uploaded';

        if ($uploadStatus !== 'justUploaded') {
          $element["description"]["#readonly"] = TRUE;
          $element["description"]["#attributes"] = ['readonly' => 'readonly'];
        }

        /** @var \Drupal\grants_handler\ApplicationStatusService $applicationStatusService */
        $applicationStatusService = \Drupal::service('grants_handler.application_status_service');

        if (
          isset($dataForElement['fileType'])
          && $dataForElement['fileType'] != '45'
          && $applicationStatusService->isSubmissionEditable($submission)
        ) {
          // By default we allow deletion of the attachment if submission is
          // editable AND the file type is not 45 (account confirmation).
          $showDeleteButton = TRUE;

          // But since the attachments currently work differently than the other
          // fields regarding to editing, we need to do additional check for
          // explicitly application status and upload status.
          if ($submissionData['status'] === 'RECEIVED' && $uploadStatus !== 'justUploaded') {
            // We allow deletion of the attachment only if it has been just
            // uploaded. Just meaning this editing session.
            $showDeleteButton = FALSE;
          }

          if ($showDeleteButton === TRUE) {
            $element['deleteItem'] = [
              '#type' => 'submit',
              '#name' => 'delete_' . $arrayKey,
              '#value' => t('Delete attachment', [], $tOpts),
              '#submit' => [
                [
                  '\Drupal\grants_attachments\Element\GrantsAttachments',
                  'deleteAttachmentSubmit',
                ],
              ],
              '#limit_validation_errors' => [[$element['#webform_key']]],
              '#ajax' => [
                'callback' => [
                  '\Drupal\grants_attachments\Element\GrantsAttachments',
                  'deleteAttachment',
                ],
                'wrapper' => $element["#webform_id"],
              ],
              '#attributes' => [
                'class' => ['button--delete-attachment'],
              ],
            ];
          }
        }
      }
      if (isset($dataForElement['description'])) {
        $element["description"]["#default_value"] = $dataForElement['description'];
      }

      if (
        isset($dataForElement['fileType']) &&
        $dataForElement['fileType'] == '45' &&
        isset($dataForElement['attachmentName']) &&
        $dataForElement['attachmentName'] !== "") {
        $element["fileStatus"]["#value"] = 'uploaded';
      }

      // Final override to rule them all.
      if ($uploadStatus === 'justUploaded') {
        $element["fileStatus"]["#value"] = 'justUploaded';
      }
    }

    $element['#prefix'] = '<div class="' . $element["#webform_id"] . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Form elements for attachments.
   *
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $tOpts = ['context' => 'grants_attachments'];

    $sessionHash = Crypt::hashBase64(\Drupal::service('session')->getId());
    $uploadLocation = 'private://grants_attachments/' . $sessionHash;
    $maxFileSizeInBytes = (1024 * 1024) * 20;

    $elements = [];

    $uniqId = Html::getUniqueId('composite-attachment');

    $allowedFileTypes = $element['#allowed_filetypes'] ?? self::DEFAULT_ALLOWED_FILE_TYPES;
    $allowedFileTypesArray = self::getAllowedFileTypesInArrayFormat($allowedFileTypes);

    $elements['attachment'] = [
      '#type' => 'managed_file',
      '#title' => t('Attachment', [], $tOpts),
      '#multiple' => FALSE,
      '#uri_scheme' => 'private',
      '#file_extensions' => $allowedFileTypes,
      // Managed file assumes that this is always in MB.
      '#max_filesize' => 20,
      '#upload_validators' => [
        'grants_attachments_validate_name_length' => self::DEFAULT_FILENAME_LENGTH,
        'file_validate_extensions' => $allowedFileTypesArray,
        'file_validate_size' => [$maxFileSizeInBytes],
      ],
      '#upload_location' => $uploadLocation,
      '#sanitize' => TRUE,
      '#states' => [
        'disabled' => [
          '[data-webform-composite-attachment-checkbox="' . $uniqId . '"]' => ['checked' => TRUE],
        ],
      ],
      // '#error_no_message' => TRUE,
      '#element_validate' => [
        '\Drupal\grants_attachments\Element\GrantsAttachments::validateUpload',
        [self::class, 'validateAttachmentRequired'],
        [self::class, 'validateAttachmentRemoval'],
      ],
    ];

    $elements['attachmentName'] = [
      '#type' => 'textfield',
      '#readonly' => TRUE,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $elements['description'] = [
      '#type' => 'textfield',
      '#title' => t('Attachment description', [], $tOpts),
    ];
    $elements['isDeliveredLater'] = [
      '#type' => 'checkbox',
      '#title' => t('Attachment will be delivered at later time', [], $tOpts),
      '#element_validate' => ['\Drupal\grants_attachments\Element\GrantsAttachments::validateDeliveredLaterCheckbox'],
      '#attributes' => [
        'data-webform-composite-attachment-isDeliveredLater' => $uniqId,
        'data-webform-composite-attachment-checkbox' => $uniqId,
      ],
      '#states' => [
        'enabled' => [
          '[data-webform-composite-attachment-inOtherFile="' . $uniqId . '"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['isIncludedInOtherFile'] = [
      '#type' => 'checkbox',
      '#title' => t('Attachment already delivered', [], $tOpts),
      '#attributes' => [
        'data-webform-composite-attachment-inOtherFile' => $uniqId,
        'data-webform-composite-attachment-checkbox' => $uniqId,
      ],
      '#states' => [
        'enabled' => [
          '[data-webform-composite-attachment-isDeliveredLater="' . $uniqId . '"]' => ['checked' => FALSE],
        ],
      ],
      '#element_validate' => [
        '\Drupal\grants_attachments\Element\GrantsAttachments::validateIncludedOtherFileCheckbox',
        '\Drupal\grants_attachments\Element\GrantsAttachments::validateElements',
      ],
    ];
    $elements['fileStatus'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];
    $elements['fileType'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];
    $elements['integrationID'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];
    $elements['isAttachmentNew'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];

    return $elements;
  }

  /**
   * Submit handler for deleting attachments.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function deleteAttachmentSubmit(array $form, FormStateInterface $form_state): array {
    $triggeringElement = $form_state->getTriggeringElement();

    $form_state->setRebuild(TRUE);
    $attachmentField = $triggeringElement['#parents'];
    $attachmentField[count($attachmentField) - 1] = 'attachment';

    $multiValue = FALSE;
    $multiValueKey = NULL;
    if (isset($attachmentField[1]) && $attachmentField[1] == 'items') {
      $multiValue = TRUE;
      $multiValueKey = $attachmentField[2];
    }

    array_pop($attachmentField);
    if ($multiValue) {
      $attachmentField = [$attachmentField[0], $multiValueKey];
    }

    // Get attachment field info.
    $attachmentParent = $form_state->getValue($attachmentField);
    $form_state->setValue($attachmentField, []);
    $storage = $form_state->getStorage();

    // Array key depending if multi-value or single attachment.
    $arrayKey = $multiValue ? $attachmentField[0] . '_' . $multiValueKey : reset($attachmentField);

    $storage['deleted_attachments'][$arrayKey] = $attachmentParent;
    $form_state->setStorage($storage);

    return $form;
  }

  /**
   * Ajax callback for attachment deletion.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function deleteAttachment(array $form, FormStateInterface $form_state): AjaxResponse {
    $triggeringElement = $form_state->getTriggeringElement();

    $parent = reset($triggeringElement['#parents']);
    $elem = $form['elements']['lisatiedot_ja_liitteet']['liitteet'][$parent];
    $selector = '.' . $elem['#webform_id'];

    if (isset($triggeringElement['#parents'][1]) && $triggeringElement['#parents'][1] == 'items') {
      $selector = '#muu_liite_table';
    }

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, $elem));
    return $response;
  }

  /**
   * Find items recursively from an array.
   *
   * @param array $haystack
   *   Search from.
   * @param string $needle
   *   What to search.
   *
   * @return \Generator
   *   Added value.
   */
  public static function recursiveFind(array $haystack, string $needle): \Generator {
    $iterator = new \RecursiveArrayIterator($haystack);
    $recursive = new \RecursiveIteratorIterator(
      $iterator,
      \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($recursive as $key => $value) {
      if ($key === $needle) {
        yield $value;
      }
    }
  }

  /**
   * Validate attachment required requirement.
   *
   * @param array $element
   *   Element to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateAttachmentRequired(array &$element, FormStateInterface $form_state, array &$form): void {
    $triggeringElement = $form_state->getTriggeringElement();

    if (str_contains($triggeringElement['#name'], 'button')) {
      return;
    }

    if ($triggeringElement['#type'] === 'submit' && $element['#required'] === TRUE) {
      $fids = $element['#value']['fids'] ?? [];

      $formErrors = $form_state->getErrors();
      $parent = reset($element['#parents']);
      $parentAttachment = $parent . '][attachment';

      if (empty($fids) && !isset($formErrors[$parentAttachment])) {
        $form_state->setErrorByName(
          $parent,
          t(
            '@fieldname field is required',
            ['@fieldname' => $element['#title']],
            ['context' => 'grants_attachments'])
        );
      }
    }
  }

  /**
   * Delete file attachment from ATV when removing it from form.
   *
   * @param array $element
   *   Element to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateAttachmentRemoval(array &$element, FormStateInterface $form_state, array &$form): void {
    $triggeringElement = $form_state->getTriggeringElement();

    // Check if the triggering element is the "Remove" button.
    if (str_contains($triggeringElement['#name'], '_attachment_remove_button')) {
      $ename = $element['#name'];
      $ename_exp = explode('[', $ename);

      // Ok validation functions are run for every fileupload field on the form
      // so we need to make sure that we are actually currently trying to delete
      // a field which triggered the action.
      if (!str_contains($triggeringElement['#name'], $ename_exp[0])) {
        return;
      }

      $file_fid = $form_state->getValue($ename_exp[0])['attachment'];

      if ($file_fid) {
        // Get stored files.
        $storage = $form_state->getStorage();

        // Check if we have stored information about this file.
        if (isset($storage['fids_info'][$file_fid])) {
          // Get integrationID from stored information.
          $integrationID = $storage['fids_info'][$file_fid]['integrationID'];
          // Clean integrationID for deletion.
          $cleanIntegrationId = AttachmentHandlerHelper::cleanIntegrationId(
            $integrationID
          );

          /** @var \Drupal\helfi_atv\AtvService $atvService */
          $atvService = \Drupal::service('helfi_atv.atv_service');
          $logger = \Drupal::logger('grants_attachments');
          try {
            // Try to remove attachment from ATV.
            $atvService->deleteAttachmentViaIntegrationId($cleanIntegrationId);
          }
          catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
            // Log error.
            $logger
              ->error('Deletion failed for integrationID: @integrationID, @error',
                [
                  '@integrationID' => $cleanIntegrationId,
                  '@error' => $e->getMessage(),
                ]);
          }
        }
      }
    }
  }

  /**
   * Validate & upload file attachment.
   *
   * @param array $element
   *   Element to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   The form.
   *
   * @return bool|null
   *   Success or not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public static function validateUpload(
    array &$element,
    FormStateInterface $form_state,
    array &$form,
  ): bool|null {
    $webformKey = $element["#parents"][0];
    $triggeringElement = $form_state->getTriggeringElement();
    $isRemoveAction = str_contains($triggeringElement["#name"], 'attachment_remove_button');

    // Work only on uploaded files.
    if (!isset($element["#files"]) || empty($element["#files"])) {
      return NULL;
    }
    $multiValueField = FALSE;
    $validatingTriggeringElementParent = FALSE;
    $hasSameRootElement = reset($triggeringElement['#parents']) === reset($element['#parents']);

    // Reset index.
    $index = 0;

    /** @var \Drupal\webform\WebformSubmissionForm $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
    $webformSubmission = $formObject->getEntity();
    // Get data from webform.
    $webformData = $webformSubmission->getData();

    // Figure out paths on form & element.
    $valueParents = $element["#parents"];
    array_pop($valueParents);

    $arrayParents = $element["#array_parents"];
    array_splice($arrayParents, -4);

    // Get webform data element from submitted data.
    $webformDataElement = $webformData[$webformKey];
    if (in_array('items', $valueParents)) {
      end($valueParents);
      $index = prev($valueParents);
      $webformDataElement = $webformData[$webformKey][$index] ?? NULL;
      // ...
      $fid = array_key_first($element["#files"]);
      $validID = $webformDataElement['attachment'] == $fid;

      if (!$validID) {
        foreach ($webformData[$webformKey] as $item) {
          if ($item['attachment'] == $fid) {
            $webformDataElement = $item;
            break;
          }
        }
      }
      $validatingTriggeringElementParent = in_array($index, $triggeringElement['#parents']);
      $multiValueField = TRUE;
    }
    $shouldNotValidate = !$hasSameRootElement || ($multiValueField && !$validatingTriggeringElementParent);
    // If we already have uploaded this file now, lets not do it again.
    if (!$isRemoveAction && isset($webformDataElement["fileStatus"]) && $webformDataElement["fileStatus"] == 'justUploaded') {
      // It seems that this is only place where we have description field in
      // form values. Somehow this is not available in handler anymore.
      // it's not even available, when initially processing the upload
      // because then the $element is file upload.
      $formValue = $form_state->getValue($webformKey);
      // So we set the description here after cleaning.
      // Also check if this is multivalue form array or not.
      $webformDataElement['description'] = Xss::filter($formValue['description'] ?? $formValue[$index]['description']);
      // And set webform element back to form state.
      $form_state->setValue([...$valueParents], $webformDataElement);
    }

    // If no application number, we cannot validate.
    // We should ALWAYS have it though at this point.
    if (!isset($webformData['application_number'])) {
      return NULL;
    }
    // Get application number from data.
    $applicationNumber = $webformData['application_number'];

    // If upload button is clicked.
    if (str_contains($triggeringElement["#name"], 'attachment_upload_button')) {
      if ($shouldNotValidate) {
        return NULL;
      }

      // Try to find filetype via array parents.
      $formFiletype = NestedArray::getValue($form, [
        ...$arrayParents,
        '#filetype',
      ]);
      // If not, then brute force value from form.
      if (empty($formFiletype) && $formFiletype !== '0') {
        foreach (self::recursiveFind($form, $webformKey) as $value) {
          // If user has removed file and then readded it to element, there's
          // one empty element in the value array, and that resulted in missing
          // integrationID in document json. So we need to check if the
          // #filetype actually exists and use it only in the case it does.
          // But since this is always the same file element, the filetype is
          // same in every iteration of the fields' values.
          if ($value != NULL && $value['#filetype'] != '') {
            $formFiletype = $value['#filetype'];
          }
        }
      }

      foreach ($element["#files"] as $file) {
        $success = self::uploadFile(
          $form_state,
          $element,
          $file,
          $valueParents,
          $applicationNumber,
          $multiValueField,
          $index,
          $formFiletype
        );
        if (!$success) {
          break;
        }
      }
    }
    elseif ($isRemoveAction && isset($fid)) {
      // Validate function is looping all file fields.
      // Check if we are actually currently trying to delete a
      // field which triggered the action.
      if ($shouldNotValidate) {
        $form_state->setValue([...$valueParents], $webformDataElement);
        return NULL;
      }
      self::handleRemoveAction($element, $form_state, $webformDataElement, $fid);
      $form_state->setValue([...$valueParents], []);
    }
    return NULL;
  }

  /**
   * Upload file.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $element
   *   Element.
   * @param \Drupal\file\Entity\File $file
   *   File.
   * @param array $valueParents
   *   Value parents.
   * @param string $applicationNumber
   *   Application number.
   * @param bool $multiValueField
   *   Multi value field.
   * @param int $index
   *   Index.
   * @param string $formFiletype
   *   Form filetype.
   *
   * @return bool
   *   Success or not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected static function uploadFile(
    FormStateInterface $formState,
    array $element,
    File $file,
    array $valueParents,
    string $applicationNumber,
    bool $multiValueField,
    int $index,
    string $formFiletype,
  ): bool {
    try {
      /** @var \Drupal\helfi_atv\AtvService $atvService */
      $atvService = \Drupal::service('helfi_atv.atv_service');

      /** @var \Drupal\grants_handler\ApplicationGetterService $applicationGetterService */
      $applicationGetterService = \Drupal::service('grants_handler.application_getter_service');

      // Get Document for this application.
      $atvDocument = $applicationGetterService->getAtvDocument($applicationNumber);

      // Upload attachment to document.
      $attachmentResponse = $atvService->uploadAttachment($atvDocument->getId(), $file->getFilename(), $file);

      // Remove server url from integrationID.
      $baseUrl = $atvService->getBaseUrl();
      $baseUrlApps = str_replace('agw', 'apps', $baseUrl);
      // Remove server url from integrationID.
      // We need to make sure that the integrationID gets removed inside &
      // outside the azure environment.
      $integrationId = str_replace($baseUrl, '', $attachmentResponse['href']);
      $integrationId = str_replace($baseUrlApps, '', $integrationId);

      $appParam = Helpers::getAppEnv();
      if ($appParam !== 'PROD') {
        $integrationId = '/' . $appParam . $integrationId;
      }

      // Set values to form.
      $formState->setValue([
        ...$valueParents,
        'integrationID',
      ], $integrationId);

      $formState->setValue([
        ...$valueParents,
        'fileStatus',
      ], 'justUploaded');

      $formState->setValue([
        ...$valueParents,
        'isDeliveredLater',
      ], '0');

      $formState->setValue([
        ...$valueParents,
        'isIncludedInOtherFile',
      ], '0');

      $formState->setValue([
        ...$valueParents,
        'fileName',
      ], $file->getFilename());

      $formState->setValue([
        ...$valueParents,
        'attachmentName',
      ], $file->getFilename());

      $formState->setValue([
        ...$valueParents,
        'attachmentIsNew',
      ], TRUE);

      $formState->setValue([
        ...$valueParents,
        'fileType',
      ], $formFiletype);

      $storage = $formState->getStorage();
      $storage['fids_info'][$file->id()] = [
        'integrationID' => $integrationId,
        'fileStatus' => 'justUploaded',
        'isDeliveredLater' => '0',
        'isIncludedInOtherFile' => '0',
        'fileName' => $file->getFileName(),
        'attachmentIsNew' => TRUE,
        'attachmentName' => $file->getFileName(),
        'fileType' => $formFiletype,
        'attachment' => $file->id(),
      ];

      $formState->setStorage($storage);
      return TRUE;
    }
    catch (\Exception $e) {
      // Set error to form.
      $tOpts = ['context' => 'grants_attachments'];
      $formState->setError($element, t('File upload failed, error has been logged.', [], $tOpts));
      // Log error.
      \Drupal::logger('grants_attachments')->error($e->getMessage());
      // And set webform element back to form state.
      $formState->unsetValue($valueParents);
      $formState->setValue([...$valueParents], []);
      if ($multiValueField) {
        $tempKey = [reset($valueParents), 'items', $index];
        $formState->unsetValue($tempKey);
        $formState->setValue($tempKey, []);
      }

      $element['#value'] = NULL;
      $element['#default_value'] = NULL;

      if (isset($element['#files'])) {
        foreach ($element['#files'] as $delta => $file) {
          unset($element['file_' . $delta]);
        }
      }

      unset($element['#label_for']);
      $file->delete();
      return FALSE;
    }
  }

  /**
   * Remove file from ATV.
   */
  protected static function handleRemoveAction(
    array &$element,
    FormStateInterface $formState,
    $webformDataElement,
    $fid,
  ) {
    try {
      // Delete attachment via integration id.
      $cleanIntegrationId = AttachmentHandlerHelper::cleanIntegrationId($webformDataElement["integrationID"]);
      if (!$cleanIntegrationId && reset($element["#files"])) {
        $storage = $formState->getStorage();

        $valueToCheck = $storage['fids_info'][$fid]['integrationID'] ?? NULL;
        unset($storage['fids_info'][$fid]['integrationID']);
        $formState->setStorage($storage);
        $cleanIntegrationId = AttachmentHandlerHelper::cleanIntegrationId($valueToCheck);
      }
      if ($cleanIntegrationId) {
        /** @var \Drupal\helfi_atv\AtvService $atvService */
        $atvService = \Drupal::service('helfi_atv.atv_service');
        $atvService->deleteAttachmentViaIntegrationId($cleanIntegrationId);
      }
    }
    catch (\Throwable $t) {
      \Drupal::logger('grants_attachments')
        ->error('Attachment deleting failed. Error: @error', ['@error' => $t->getMessage()]);
    }
  }

  /**
   * Validates a composite element.
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    // IMPORTANT: Must get values from the $form_states since sub-elements
    // may call $form_state->setValueForElement() via their validation hook.
    // @see \Drupal\webform\Element\WebformEmailConfirm::validateWebformEmailConfirm
    // @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    if (in_array('items', $element['#parents'])) {
      return;
    }

    // Only validate composite elements that are visible.
    if (Element::isVisibleElement($element)) {
      // Validate required composite elements.
      $composite_elements = static::getCompositeElements($element);
      $composite_elements = WebformElementHelper::getFlattened($composite_elements);
      foreach ($composite_elements as $composite_key => $composite_element) {
        $is_required = !empty($element[$composite_key]['#required']);
        $is_empty = (isset($value[$composite_key]) && $value[$composite_key] === '');
        if ($is_required && $is_empty) {
          WebformElementHelper::setRequiredError($element[$composite_key], $form_state);
        }
      }
    }

    // Clear empty composites value.
    if (is_array($value) && empty(array_filter($value))) {
      $element['#value'] = NULL;
      $form_state->setValueForElement($element, NULL);
    }
  }

  /**
   * Validate Checkbox.
   *
   * @param array $element
   *   Validated element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Form itself.
   */
  public static function validateDeliveredLaterCheckbox(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form,
  ) {
    $tOpts = ['context' => 'grants_attachments'];

    $file = $form_state->getValue([
      $element["#parents"][0],
      'attachment',
    ]);
    $isDeliveredLaterCheckboxValue = $form_state->getValue([
      $element["#parents"][0],
      'isDeliveredLater',
    ]);
    $integrationID = $form_state->getValue([
      $element["#parents"][0],
      'integrationID',
    ]);

    if ($file !== NULL && $isDeliveredLaterCheckboxValue === '1' && empty($integrationID)) {
      $form_state->setError($element, t('You cannot send file and have it delivered later', [], $tOpts));
    }
  }

  /**
   * Validate checkbox.
   *
   * @param array $element
   *   Validated element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Form itself.
   */
  public static function validateIncludedOtherFileCheckbox(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form,
  ) {
    $tOpts = ['context' => 'grants_attachments'];

    $file = $form_state->getValue([
      $element["#parents"][0],
      'attachment',
    ]);
    $checkboxValue = $form_state->getValue([
      $element["#parents"][0],
      'isIncludedInOtherFile',
    ]);

    $integrationID = $form_state->getValue([
      $element["#parents"][0],
      'integrationID',
    ]);

    if ($file !== NULL && $checkboxValue === '1' && empty($integrationID)) {
      $form_state->setError($element, t('You cannot send file and have it in another file', [], $tOpts));
    }
  }

  /**
   * Validate composite elements valid state.
   *
   * @param array $element
   *   Validated element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Form itself.
   */
  public static function validateElements(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form,
  ) {
    $tOpts = ['context' => 'grants_attachments'];

    $triggerngElement = $form_state->getTriggeringElement();

    if (str_contains($triggerngElement['#name'], 'delete_')) {
      return;
    }

    // These are not required for muut liitteet as it's optional.
    $rootParent = reset($element['#parents']);
    if ($rootParent === 'muu_liite') {
      return;
    }

    $value = NestedArray::getValue($form_state->getValues(), [reset($element['#parents'])]);
    $arrayParents = $element['#array_parents'];
    array_pop($arrayParents);
    $parent = NestedArray::getValue($complete_form, $arrayParents);
    // Custom validation logic.
    if (empty($value)) {
      return;
    }
    // If attachment is uploaded, make sure no other field is selected.
    if (isset($value['attachment']) && is_int($value['attachment'])) {
      if ($value['isDeliveredLater'] === "1") {
        $form_state->setError($element, t('@fieldname has file added, it cannot be added later.', [
          '@fieldname' => $parent['#title'],
        ], $tOpts));
      }
      if ($value['isIncludedInOtherFile'] === "1") {
        $form_state->setError($element, t('@fieldname has file added, it cannot belong to other file.', [
          '@fieldname' => $parent['#title'],
        ], $tOpts));
      }
    }
    // If there is no attachment one of the checkboxes must be on.
    $noAttachment = !isset($value['attachment']) && $value['attachmentName'] === '';
    $noCheckboxes = empty($value['isDeliveredLater']) && empty($value['isIncludedInOtherFile']);
    if ($noAttachment && $noCheckboxes) {
      $form_state->setError(
        $element,
        t('@fieldname has no file uploaded, it must be either delivered later or be included in other file.', [
          '@fieldname' => $parent['#title'],
        ],
          $tOpts
        )
      );
    }
    // Both checkboxes cannot be selected.
    if ($value['isDeliveredLater'] === "1" && $value['isIncludedInOtherFile'] === "1") {
      $form_state->setError($element, t("@fieldname you can't select both checkboxes.", [
        '@fieldname' => $parent['#title'],
      ], $tOpts));
    }
  }

  /**
   * Return allowed files in an array format.
   *
   * @param string $allowedFileTypes
   *   Allowed filetypes in a string format.
   *
   * @return array
   *   Allowed files in an array format.
   */
  private static function getAllowedFileTypesInArrayFormat(string $allowedFileTypes) {
    $filetypeArray = explode(',', $allowedFileTypes);
    return array_map('trim', $filetypeArray);
  }

}
