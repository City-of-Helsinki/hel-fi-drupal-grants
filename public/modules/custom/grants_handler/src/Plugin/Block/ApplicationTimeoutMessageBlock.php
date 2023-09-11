<?php

namespace Drupal\grants_handler\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides an ApplicationTimeoutMessageBlock block.
 *
 * This block displays a dynamic message on a Webform
 * if the application period of a form is passed when a
 * user is filling out the form.
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

    $applicationCloseTime = $webform->getThirdPartySetting('grants_metadata', 'applicationClose');
    $applicationCloseTimestamp = strtotime($applicationCloseTime);
    $currentTimestamp = strtotime('now');

    // Do not render this message if the form is already closed.
    if (!$applicationCloseTimestamp || $currentTimestamp > $applicationCloseTimestamp) {
      return [];
    }

    return [
      '#theme' => 'application_timeout_message',
      '#message_heading' => $this->t('The application period for this grant has closed.', [], ['context' => 'grants_handler']),
      '#message_body' => $this->t('You can no longer submit an application because the application period for this grant has closed.', [], ['context' => 'grants_handler']),
      '#attached' => [
        'library' => [
          'grants_handler/application-timeout-message',
        ],
        'drupalSettings' => [
          'grants_handler' => [
            'settings' => [
              'applicationCloseTimestamp' => $applicationCloseTimestamp,
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
