<?php

declare(strict_types=1);

namespace Drupal\grants_application\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Views hook implementations for grants_application.
 */
class ViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    return [
      'application_submission' => [
        'content_lock_for_submission' => [
          'title' => $this->t('Content lock'),
          'help' => $this->t('Join content_lock rows for this application submission.'),
          'relationship' => [
            'id' => 'standard',
            'label' => $this->t('Content lock (this submission)'),
            'base' => 'content_lock',
            'base field' => 'entity_id',
            'relationship field' => 'id',
            'extra' => [
              [
                'field' => 'entity_type',
                'value' => 'application_submission',
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
