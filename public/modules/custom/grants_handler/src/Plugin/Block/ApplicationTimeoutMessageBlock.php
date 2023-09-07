<?php

namespace Drupal\grants_handler\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides an ApplicationTimeoutMessageBlock block.
 *
 * @Block(
 *   id = "application_timeout_message",
 *   admin_label = @Translation("Application Timeout Message"),
 *   category = @Translation("Custom"),
 *   context_definitions = {
 *     "webform_submission" = @ContextDefinition("entity:webform_submission", label = @Translation("Webform submission"), required = FALSE),
 *   }
 * )
 */
class ApplicationTimeoutMessageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *    Exception on ContextException.
   */
  public function build(): array {

    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    if (!$submission = $this->getContextValue('webform_submission')) {
      return [];
    }

    /** @var \Drupal\webform\Entity\Webform $webform */
    if (!$webform = $submission->getWebform()) {
      return [];
    }

    $formTimestamp = $webform->getThirdPartySetting('grants_metadata', 'applicationClose');
    $formTimestamp = strtotime($formTimestamp);

    return [
      '#theme' => 'application_timeout_message',
      '#message' => $this->t('The application period is closed, no further editing is allowed.'),
      '#attached' => [
        'library' => [
          'grants_handler/application-timeout-message',
        ],
        'drupalSettings' => [
          'grants_handler' => [
            'settings' => [
              'formTimestamp' => $formTimestamp,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(),
      ['url.path', 'languages:language_content']);
  }

}
