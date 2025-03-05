<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for application actions.
 */
final class ApplicationController extends ControllerBase {

  /**
   * Render the forms react app.
   */
  public function formsApp(string $id): array {
    return [
      '#theme' => 'forms_app',
      '#attached' => [
        'drupalSettings' => [
          'grants_react_form' => [
            'application_number' => $id,
          ],
        ],
      ],
    ];
  }

}
