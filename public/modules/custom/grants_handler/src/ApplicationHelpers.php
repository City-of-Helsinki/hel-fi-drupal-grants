<?php

namespace Drupal\grants_handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Handle all things related to applications & submission objects themselves.
 */
abstract class ApplicationHelpers {

  /**
   * Name of the table where log entries are stored.
   */
  const TABLE = 'grants_handler_saveids';

  /**
   * Name of the navigation handler.
   */
  const HANDLER_ID = 'application_handler';

  /**
   * Generate application number from submission id.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   Webform data.
   * @param bool $useOldFormat
   *   Generate application number in old format.
   *
   * @return string
   *   Generated number.
   */
  public static function createApplicationNumber(WebformSubmission &$submission, $useOldFormat = FALSE): string {
    $appParam = Helpers::getAppEnv();

    $serial = $submission->serial();

    $webform = $submission->getWebform();
    $third_party_settings = $webform->getThirdPartySettings('grants_metadata');

    $applicationType = $third_party_settings['applicationType'] ?? NULL;
    $applicationTypeId = $third_party_settings['applicationTypeID'] ?? NULL;

    if ($useOldFormat) {
      return self::getApplicationNumberInEnvFormatOldFormat($appParam, $applicationType, $serial);
    }

    return self::getApplicationNumberInEnvFormat($appParam, $applicationTypeId, $serial);
  }

  /**
   * Generate next available application number for the submission.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   Webform data.
   *
   * @return string
   *   Generated number.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_atv\AtvUnexpectedResponseException
   */
  public static function getAvailableApplicationNumber(WebformSubmission &$submission): string {
    $appParam = Helpers::getAppEnv();
    $serial = $submission->serial();
    $webform_id = $submission->getWebform()->id();

    $thirdPartySettings = $submission->getWebform()
      ->getThirdPartySettings('grants_metadata');

    $applicationTypeId = $thirdPartySettings['applicationTypeID'] ?? NULL;

    $lastSerialKey = $applicationTypeId . '_' . $appParam;
    $kvService = \Drupal::service('keyvalue.database');
    $kvStorage = $kvService->get('application_numbers');
    $savedSerial = $kvStorage->get($lastSerialKey);

    if (!empty($submission->getData())) {
      return self::createApplicationNumber($submission);
    }

    if ($savedSerial && $savedSerial > $serial) {
      $serial = $savedSerial;
    }

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    $check = TRUE;

    while ($check) {
      $applicationNumber = self::getApplicationNumberInEnvFormat($appParam, $applicationTypeId, $serial);
      $applNumberIsAvailable = $atvService->checkDocumentExistsByTransactionId($applicationNumber);
      if ($applNumberIsAvailable) {
        // Check that there is no local submission with given serial.
        $query = \Drupal::entityQuery('webform_submission')
          ->condition('webform_id', $webform_id)
          ->condition('serial', $serial)
          ->accessCheck(FALSE);
        $results = $query->execute();
        if (empty($results)) {
          $check = FALSE;
        }
        else {
          // Increase serial because we found local a submission.
          $serial++;
        }
      }
      else {
        // No luck, let's check another one.
        $serial++;
      }
    }

    $submission->set('serial', $serial);
    $kvStorage->set($lastSerialKey, $serial);
    return $applicationNumber;
  }

  /**
   * Format application number based by the enviroment.
   */
  protected static function getApplicationNumberInEnvFormat($appParam, $typeId, $serial): string {
    $applicationNumber = $appParam . '-' .
      str_pad($typeId, 3, '0', STR_PAD_LEFT) . '-' .
      str_pad($serial, 7, '0', STR_PAD_LEFT);

    if ($appParam == 'PROD') {
      $applicationNumber = str_pad($typeId, 3, '0', STR_PAD_LEFT) . '-' .
        str_pad($serial, 7, '0', STR_PAD_LEFT);
    }

    return $applicationNumber;
  }

  /**
   * Format application number based by the enviroment in old format.
   */
  protected static function getApplicationNumberInEnvFormatOldFormat($appParam, $typeId, $serial): string {
    $applicationNumber = 'GRANTS-' . $appParam . '-' . $typeId . '-' . sprintf('%08d', $serial);

    if ($appParam == 'PROD') {
      $applicationNumber = 'GRANTS-' . $typeId . '-' . sprintf('%08d', $serial);
    }

    return $applicationNumber;
  }

  /**
   * Extract serial numbor from application number string.
   *
   * @param string $applicationNumber
   *   Application number.
   *
   * @return string
   *   Webform submission serial.
   */
  public static function getSerialFromApplicationNumber(string $applicationNumber): string {
    $exploded = explode('-', $applicationNumber);
    $number = end($exploded);
    return ltrim($number, '0');
  }

  /**
   * Check for breaking changes in newer webform versions.
   *
   * In this context, breaking changes means all Avus2 changes that
   * will cause the submission of the older webform to fail.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform id.
   *
   * @return bool
   *   If there is any breaking changes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function hasBreakingChangesInNewerVersion(Webform $webform): bool {
    static $map = [];

    $uuid = $webform->uuid();

    if (isset($map[$uuid])) {
      return $map[$uuid];
    }

    $thirdPartySettingsWebform = $webform->getThirdPartySettings('grants_metadata');

    $applicationType = $thirdPartySettingsWebform['applicationType'] ?? NULL;

    $latestApplicationForm = self::getLatestApplicationForm($applicationType);

    // If no latest form, then no breaking changes.
    if (!$latestApplicationForm) {
      return FALSE;
    }

    $thirdPartySettingsLatest = $webform->getThirdPartySettings('grants_metadata');
    $parent = $thirdPartySettingsLatest['parent'] ?? NULL;
    $hasBreakingChanges = $thirdPartySettingsLatest['avus2BreakingChange'] ?? NULL;

    while (!empty($parent)) {
      $map[$parent] = $hasBreakingChanges;

      $loaded_webform = \Drupal::entityTypeManager()
        ->getStorage('webform')
        ->loadByProperties([
          'uuid' => $parent,
        ]);

      $wf = reset($loaded_webform);

      $thirdPartySettingsLatest = $wf->getThirdPartySettings('grants_metadata');
      $parent = $thirdPartySettingsLatest['parent'] ?? NULL;

      // No need to check the flag,
      // if we already have a newer version with breaking changes.
      if (!$hasBreakingChanges) {
        $hasBreakingChanges = $thirdPartySettingsLatest['avus2BreakingChange'] ?? NULL;
      }
    }

    return $map[$uuid] ?? FALSE;
  }

  /**
   * Extract webform id from application number string.
   *
   * @param string $applicationNumber
   *   Application number.
   * @param bool $all
   *   Should all matching webforms be returned?
   *
   * @return \Drupal\webform\Entity\Webform
   *   Webform object.
   */
  public static function getWebformFromApplicationNumber(string $applicationNumber, $all = FALSE): bool|Webform|array {
    $isOldFormat = FALSE;
    if (strpos($applicationNumber, 'GRANTS') !== FALSE) {
      $isOldFormat = TRUE;
    }

    $fieldToCheck = $isOldFormat ? 'code' : 'applicationTypeId';

    // Explode number.
    $exploded = explode('-', $applicationNumber);
    // Get serial.
    array_pop($exploded);
    // Get application id.
    $webformTypeId = array_pop($exploded);
    // Load webforms.
    $wids = \Drupal::entityQuery('webform')
      ->execute();
    $webforms = Webform::loadMultiple(array_keys($wids));

    $applicationTypes = Helpers::getApplicationTypes();

    // Look for for application type and return if found.
    $webform = array_filter($webforms, function ($wf) use ($webformTypeId, $applicationTypes, $fieldToCheck) {
      $thirdPartySettings = $wf->getThirdPartySettings('grants_metadata');
      $thisApplicationTypeConfig = array_filter($applicationTypes, function ($appType) use ($thirdPartySettings) {
        if (isset($thirdPartySettings["applicationTypeID"]) &&
          $thirdPartySettings["applicationTypeID"] ===
          (string) $appType["applicationTypeId"]) {
          return TRUE;
        }
        return FALSE;
      });
      $thisApplicationTypeConfig = reset($thisApplicationTypeConfig);
      if (isset($thisApplicationTypeConfig[$fieldToCheck]) && $thisApplicationTypeConfig[$fieldToCheck] == $webformTypeId) {
        return TRUE;
      }
      return FALSE;
    });

    if (!$webform) {
      return FALSE;
    }

    if ($all) {
      return $webform;
    }

    return reset($webform);
  }

  /**
   * Tries to find latest webform for given application ID.
   *
   * @param mixed $id
   *   Application id (eg. KASKOIPLISA)
   *
   * @return \Drupal\webform\Entity\Webform|null
   *   Return webform object if found, else null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getLatestApplicationForm($id): Webform|null {
    $webforms = \Drupal::entityTypeManager()
      ->getStorage('webform')
      ->loadByProperties([
        'third_party_settings.grants_metadata.applicationType' => $id,
        'archive' => FALSE,
        'third_party_settings.grants_metadata.status' => 'released',
      ]);

    $webform = reset($webforms);
    if ($webform) {
      return $webform;
    }

    return NULL;
  }

  /**
   * Get all Webform objects for given application id.
   *
   * @param string $applicationTypeId
   *   Application ID.
   * @param null $formId
   *   Webform ID.
   *
   * @return array
   *   Active webforms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getActiveApplicationWebforms(string $applicationTypeId, $formId = NULL): array {
    $properties = [
      'third_party_settings.grants_metadata.applicationType' => $applicationTypeId,
      'archive' => FALSE,
    ];

    // If we've given form id, we want to load only that form.
    if ($formId) {
      // Effectevely this limits results to single form.
      // But since we now can have multiple forms with same application type,
      // we need to check that the form id is correct.
      $properties['id'] = $formId;
    }

    $webforms = \Drupal::entityTypeManager()
      ->getStorage('webform')
      ->loadByProperties($properties);

    $result = [
      'released' => [],
      'development' => [],
    ];

    foreach ($webforms as $webform) {
      $webformStatus = $webform->getThirdPartySetting('grants_metadata', 'status');
      if (empty($webformStatus)) {
        $webformStatus = 'released';
      }
      $result[$webformStatus][] = $webform;
    }

    return $result;
  }

  /**
   * Checks if webform configuration can duplicated with given Application ID.
   *
   * General rule is that one application type ID can have maximum number of 1
   * Production & In development versions.
   *
   * @param string $id
   *   Application ID.
   * @param string|null $formId
   *   Webform ID. Or null if all is wanted.
   *
   * @return bool
   *   Can the webform be duplicated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function isApplicationWebformDuplicatable(string $id, string $formId = NULL): bool {
    $applicationForms = self::getActiveApplicationWebforms($id, $formId);
    return count($applicationForms['released']) <= 1 && count($applicationForms['development']) === 0;
  }

  /**
   * Update field options in a form array.
   *
   * This method is used to update the options of a field in a form array.
   *
   * @param array $form
   *   The form array.
   * @param array $newOptions
   *   The new options to set.
   * @param array $fieldStructure
   *   The structure of the field.
   */
  public static function updateFieldOptions(array &$form, array $newOptions, array $fieldStructure): void {
    $currentField = &$form;

    // Traverse the form array based on the field structure.
    foreach ($fieldStructure as $fieldName) {
      if (isset($currentField[$fieldName])) {
        $currentField = &$currentField[$fieldName];
      }
      elseif (isset($currentField['#element'][$fieldName])) {
        $currentField = &$currentField['#element'][$fieldName];
      }
      else {
        // If we don't have current field array, we can't update the options.
        if (!is_iterable($currentField)) {
          return;
        }
        // If the field is not found, continue searching recursively.
        foreach ($currentField as &$subField) {
          if (is_array($subField)) {
            self::updateFieldOptions($subField, $newOptions, $fieldStructure);
          }
        }
        return;
      }
    }

    // Update the #options if the field with '#options' is found.
    if (isset($currentField['#options'])) {
      $currentField['#options'] = $newOptions;
    }
  }

}
