<?php

namespace Drupal\grants_attachments\Element;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\file\Element\ManagedFile;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Grants Managed File Element.
 *
 * @FormElement("grants_managed_file")
 */
class GrantsManagedFile extends ManagedFile {

 /**
 * {@inheritdoc}
 */
 public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {

    $triggeringElement = $form_state->getTriggeringElement();

    // if ($triggeringElement) {
    //   $errors = $form_state->getErrors();
    //   // We got some error during file upload - Send NULL response so we can get element without state.
    //   if (!empty($errors)) {
    //     foreach ($errors as $error) {
    //       if (strpos($error, 'File upload failed, error has been logged') !== FALSE) {
    //         \Drupal::service('messenger')->deleteAll();
    //       }
    //     }
    //   }
    // }

    $response = parent::uploadAjaxCallback($form, $form_state, $request);
    return $response;
  }
}
