<?php

namespace Drupal\grants_attachments;

/**
 * Helper class for static helper functions.
 */
class AttachmentHandlerHelper {

  /**
   * Get attachment upload time from events.
   *
   * @param array $events
   *   Events of the submission.
   * @param string $fileName
   *   Attachment file from submission data.
   *
   * @return string
   *   File upload time.
   *
   * @throws \Exception
   */
  public static function getAttachmentUploadTime(array $events, string $fileName): string {
    $dtString = '';
    $event = array_filter(
      $events,
      function ($item) use ($fileName) {
        if ($item['eventTarget'] == $fileName) {
          return TRUE;
        }
        return FALSE;
      }
    );
    $event = reset($event);
    if ($event) {
      $dt = new \DateTime($event['timeCreated']);
      $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));
      $dtString = $dt->format('d.m.Y H:i');
    }
    return $dtString;
  }

  /**
   * Get attachment file type.
   */
  public static function getFiletypeFromFieldElement($form, $fieldElement, $attachmentFieldName) {
    if (isset($fieldElement["fileType"]) && $fieldElement["fileType"] !== "") {
      $fileType = $fieldElement["fileType"];
    }
    else {
      // @todo Is this really necessary. Please, please try to debug so that this can be removed.
      if (isset($form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#filetype"])) {
        $fileType = $form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#filetype"];
      }
      else {
        $fileType = '0';
      }
    }
    return $fileType;
  }

  /**
   * Remove environment things from integration ID. Most things will not work.
   *
   * @param mixed $integrationID
   *   File integration id.
   *
   * @return mixed|string
   *   Cleaned id.
   */
  public static function cleanIntegrationId(mixed $integrationID): mixed {
    $atvVersion = getenv('ATV_VERSION');
    $removeBeforeThis = '/' . $atvVersion;

    return strstr($integrationID, $removeBeforeThis);
  }

  /**
   * Clean domains from integration IDs.
   *
   * @param string $href
   *   Attachment url in ATV.
   *
   * @return string
   *   Cleaned url
   */
  public static function getIntegrationIdFromFileHref(string $href): string {
    $atvService = \Drupal::service('helfi_atv.atv_service');
    $baseUrl = $atvService->getBaseUrl();
    $baseUrlApps = str_replace('agw', 'apps', $baseUrl);
    // Remove server url from integrationID.
    $integrationId = str_replace($baseUrl, '', $href);
    return str_replace($baseUrlApps, '', $integrationId);
  }

  /**
   * Adds current environment to file integration id.
   *
   * @param mixed $integrationID
   *   File integrqtion ID.
   *
   * @return mixed|string
   *   Updated integration ID.
   */
  public static function addEnvToIntegrationId(mixed $integrationID): mixed {

    $appParam = ApplicationHandler::getAppEnv();

    $atvVersion = getenv('ATV_VERSION');
    $removeBeforeThis = '/' . $atvVersion;

    $integrationID = strstr($integrationID, $removeBeforeThis);

    if ($appParam === 'PROD') {
      return $integrationID;
    }

    $addThis = '/' . $appParam;
    return $addThis . $integrationID;
  }

}