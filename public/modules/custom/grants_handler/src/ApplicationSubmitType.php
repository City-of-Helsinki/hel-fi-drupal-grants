<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

/**
 * Types of application submissions.
 */
enum ApplicationSubmitType {

  // Application is saved as a draft.
  case SUBMIT_DRAFT;

  // Application is submitted to avustus2.
  case SUBMIT;

}
