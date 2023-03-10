<?php

namespace Drupal\grants_attachments\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\grants_handler\ApplicationHandler;
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

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'grants_attachments'];
  }

  // @codingStandardsIgnoreStart

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

    $element['#tree'] = TRUE;
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    $submission = $form_state->getFormObject()->getEntity();
    $submissionData = $submission->getData();

    if (isset($submissionData[$element['#webform_key']]) && is_array($submissionData[$element['#webform_key']])) {

      $dataForElement = $element['#value'];

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
        if ($element["isDeliveredLater"]["#default_value"] == TRUE) {
          $element["fileStatus"]["#value"] = 'deliveredLater';
        }
        if ($dataForElement['isDeliveredLater'] == '1') {
          $element["isDeliveredLater"]['#default_value'] = TRUE;
        }
      }
      if (isset($dataForElement['isIncludedInOtherFile'])) {
        $element["isIncludedInOtherFile"]["#default_value"] = ($dataForElement['isIncludedInOtherFile'] == 'true' || $dataForElement['isIncludedInOtherFile'] == '1');
        if ($element["isIncludedInOtherFile"]["#default_value"] == TRUE) {
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

        $element["attachment"]["#access"] = FALSE;
        $element["attachment"]["#readonly"] = TRUE;
        $element["attachment"]["#attributes"] = ['readonly' => 'readonly'];

        if (isset($element["isNewAttachment"])) {
          $element["isNewAttachment"]["#value"] = FALSE;
        }

        $element["fileStatus"]["#value"] = 'uploaded';

        // $element["description"]["#disabled"] = TRUE;
        $element["description"]["#readonly"] = TRUE;
        $element["description"]["#attributes"] = ['readonly' => 'readonly'];
      }
      if (isset($dataForElement['description'])) {
        $element["description"]["#default_value"] = $dataForElement['description'];
      }

      if (isset($dataForElement['fileType']) && $dataForElement['fileType'] == '45') {
        if (isset($dataForElement['attachmentName']) && $dataForElement['attachmentName'] !== "") {
          $element["fileStatus"]["#value"] = 'uploaded';
        }
      }
    }

    return $element;
  }

  // @codingStandardsIgnoreEnd

  /**
   * Form elements for attachments.
   *
   * @todo Use description field always and poplate contents from field title.
   * @todo Allowed file extensions for attachments??
   *
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $sessionHash = sha1(\Drupal::service('session')->getId());
    $upload_location = 'private://grants_attachments/' . $sessionHash;

    $elements = [];
    $elements['attachment'] = [
      '#type' => 'managed_file',
      '#title' => t('Attachment'),
      '#multiple' => FALSE,
      '#uri_scheme' => 'private',
      '#file_extensions' => 'doc,docx,gif,jpg,jpeg,pdf,png,ppt,pptx,rtf,txt,xls,xlsx,zip',
      '#upload_validators' => [
        'file_validate_extensions' => 'doc,docx,gif,jpg,jpeg,pdf,png,ppt,pptx,rtf,txt,xls,xlsx,zip',
      ],
      '#upload_location' => $upload_location,
      '#sanitize' => TRUE,
      '#element_validate' => ['\Drupal\grants_attachments\Element\GrantsAttachments::validateUpload'],
    ];

    $elements['attachmentName'] = [
      '#type' => 'textfield',
      '#readonly' => TRUE,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $elements['description'] = [
      '#type' => 'textfield',
      '#title' => t('Attachment description'),
    ];
    $elements['isDeliveredLater'] = [
      '#type' => 'checkbox',
      '#title' => t('Attachment will delivered at later time'),
      '#element_validate' => ['\Drupal\grants_attachments\Element\GrantsAttachments::validateDeliveredLaterCheckbox'],
    ];
    $elements['isIncludedInOtherFile'] = [
      '#type' => 'checkbox',
      '#title' => t('Attachment already delivered'),
      '#element_validate' => ['\Drupal\grants_attachments\Element\GrantsAttachments::validateIncludedOtherFileCheckbox'],
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

    return $elements;
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
  public static function recursiveFind(array $haystack, string $needle) {
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
   * Validate & upload file attachment.
   *
   * @param array $element
   *   Element tobe validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateUpload(array &$element, FormStateInterface $form_state, array &$form) {

    $webformKey = $element["#parents"][0];
    $triggeringElement = $form_state->getTriggeringElement();

    // Work only on uploaded files.
    if (isset($element["#files"]) && !empty($element["#files"])) {
      // Reset index.
      $index = 0;

      /** @var \Drupal\webform\WebformSubmissionForm $form_object */
      $form_object = $form_state->getFormObject();
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webformSubmission = $form_object->getEntity();
      // Get data from webform.
      $webformData = $webformSubmission->getData();

      // Figure out paths on form & element.
      $valueParents = $element["#parents"];
      array_pop($valueParents);

      $arrayParents = $element["#array_parents"];
      array_splice($arrayParents, -4);

      // Get webform data element from submitted data.
      if (in_array('items', $valueParents)) {
        end($valueParents);
        $index = prev($valueParents);
        $webformDataElement = $webformData[$webformKey][$index];
      }
      else {
        $webformDataElement = $webformData[$webformKey];
      }

      // If we already have uploaded this file now, lets not do it again.
      if (isset($webformDataElement["fileStatus"]) && $webformDataElement["fileStatus"] == 'justUploaded') {
        // It seems that this is only place where we have description field in
        // form values. Somehow this is not available in handler anymore.
        // it's not even available, when initially processing the upload
        // because then the $element is file upload.
        $formValue = $form_state->getValue($webformKey);
        // So we set the description here after cleaning.
        $webformDataElement['description'] = Xss::filter($formValue[$index]['description']);
        // And set webform element back to form state.
        $form_state->setValue([...$valueParents], $webformDataElement);
      }

      // If no application number, we cannot validate.
      // We should ALWAYS have it though at this point.
      if (!isset($webformData['application_number'])) {
        return;
      }
      // Get application number from data.
      $application_number = $webformData['application_number'];

      /** @var \Drupal\grants_handler\ApplicationHandler $applicationHandler */
      $applicationHandler = \Drupal::service('grants_handler.application_handler');
      /** @var \Drupal\helfi_atv\AtvService $atvService */
      $atvService = \Drupal::service('helfi_atv.atv_service');

      // If upload button is clicked.
      if (str_contains($triggeringElement["#name"], 'attachment_upload_button')) {

        // Try to find filetype via array parents.
        $formFiletype = NestedArray::getValue($form, [
          ...$arrayParents,
          '#filetype',
        ]);
        // If not, then brute force value from form.
        if (!$formFiletype) {
          foreach (self::recursiveFind($form, $webformKey) as $value) {
            if ($value != NULL) {
              $formFiletype = $value['#filetype'];
            }
          }
        }

        foreach ($element["#files"] as $file) {
          try {
            // Get Document for this application.
            $atvDocument = $applicationHandler->getAtvDocument($application_number);

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

            $appParam = ApplicationHandler::getAppEnv();
            if ($appParam !== 'PROD') {
              $integrationId = $appParam . $integrationId;
              // '[LOCAL* / DEV / TEST / STAGE]/v1/documents/dab1e85f-fffa-4a9f-965c-c2720f961119/attachments/4761/';
            }

            // Set values to form.
            $form_state->setValue([
              ...$valueParents,
              'integrationID',
            ], $integrationId);

            $form_state->setValue([
              ...$valueParents,
              'fileStatus',
            ], 'justUploaded');

            $form_state->setValue([
              ...$valueParents,
              'isDeliveredLater',
            ], '0');

            $form_state->setValue([
              ...$valueParents,
              'isIncludedInOtherFile',
            ], '0');

            $form_state->setValue([
              ...$valueParents,
              'fileName',
            ], $file->getFilename());

            $form_state->setValue([
              ...$valueParents,
              'attachmentName',
            ], $file->getFilename());

            $form_state->setValue([
              ...$valueParents,
              'attachmentIsNew',
            ], TRUE);

            $form_state->setValue([
              ...$valueParents,
              'fileType',
            ], $formFiletype);

          }
          catch (\Exception $e) {
            // Set error to form.
            $form_state->setError($element, 'File upload failed, error has been logged.');
            // Log error.
            \Drupal::logger('grants_attachments')->error($e->getMessage());
            // And set webform element back to form state.
            $form_state->setValue([...$valueParents], []);
          }
          catch (GuzzleException $e) {
            // Set error to form.
            $form_state->setError($element, 'File upload failed, error has been logged.');
            // Log error.
            \Drupal::logger('grants_attachments')->error($e->getMessage());
            // And set webform element back to form state.
            $form_state->setValue([...$valueParents], []);
          }

        }
      }
      elseif (str_contains($triggeringElement["#name"], 'attachment_remove_button')) {
        try {
          // Delete attachment via integration id.
          $atvService->deleteAttachmentViaIntegrationId($webformDataElement["integrationID"]);
          // And set webform element back to form state.
          $form_state->setValue([...$valueParents], []);
        }
        catch (\Throwable $t) {
          \Drupal::logger('grants_attachments')
            ->error('Attachment deleting failed. Error: @error', ['@error' => $t->getMessage()]);
        }
      }
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
    array &$complete_form) {

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

    if ($file !== NULL && $isDeliveredLaterCheckboxValue === '1') {
      if (empty($integrationID)) {
        $form_state->setError($element, t('You cannot send file and have it delivered later'));
      }
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
    array &$complete_form) {

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

    if ($file !== NULL && $checkboxValue === '1') {
      if (empty($integrationID)) {
        $form_state->setError($element, t('You cannot send file and have it in another file'));
      }
    }
  }

}
