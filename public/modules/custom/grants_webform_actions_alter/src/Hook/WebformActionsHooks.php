<?php

declare(strict_types=1);

namespace Drupal\grants_webform_actions_alter\Hook;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\grants_handler\FormLockService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Webform actions hooks.
 */
final class WebformActionsHooks {

  use AutowireTrait;
  use StringTranslationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'grants_handler.form_lock_service')]
    private readonly FormLockService $formLockService,
  ) {
  }

  /**
   * Implements hook_webform_element_ELEMENT_TYPE_alter().
   *
   * @param array<string, mixed> $element
   *   The webform actions element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $context
   *   The context.
   */
  #[Hook('webform_element_webform_actions_alter')]
  public function webformActionsAlter(array &$element, FormStateInterface $form_state, array $context): void {
    // Skip the webform admin dialog libraries on the delete action.
    $element['#delete__dialog'] = FALSE;
  }

  /**
   * Implements hook_preprocess_webform_actions().
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   *
   * @see template_preprocess_webform_actions()
   */
  #[Hook('preprocess_webform_actions')]
  public function preprocessWebformActions(array &$variables): void {
    $sid = $variables['element']['#webform_submission'] ?? FALSE;
    if (!$sid) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->entityTypeManager->getStorage('webform_submission')->load($sid);

    $data = $webform_submission->getData();

    if (isset($data['application_number'])) {
      $variables['applicationNumber'] = $data['application_number'];
    }

    $deleteDraftLinkText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'trash',
      '#text_label' => $this->t('Delete draft', [], ['context' => 'grants_handler']),
    ];
    $deleteDraftUrl = Url::fromRoute('grants_handler.clear-navigations', ['submission_id' => $variables['applicationNumber']]);
    $deleteDraftLink = [
      '#type' => 'link',
      '#title' => $deleteDraftLinkText,
      '#url' => $deleteDraftUrl,
      '#id' => 'webform-button--delete-draft',
      '#attributes' => [
        'class' => [
          'hds-button',
          'hds-button--supplementary',
          'js-delete-draft-link',
        ],
      ],
      '#attached' => [
        'library' => [
          'grants_handler/application-delete-draft-dialog',
        ],
      ],
    ];
    $lockedStatus = FALSE;
    if (!empty($data['application_number'])) {
      $lockedStatus = $this->formLockService->isApplicationFormLocked($data['application_number']);
    }
    $draft_variables = [];
    if ($data['status'] == 'DRAFT' && !$lockedStatus) {
      $variables['delete_draft'] = $deleteDraftLink;
      $draft_variables['delete_draft'] = $deleteDraftLink;
    }

    $temp_element = $variables['element'];

    unset($variables['element']);

    $variables['element'] = array_merge($draft_variables, $temp_element);

    $variables['draft']['#attributes']['class'][] = 'hds-button--supplementary';
    $variables['element']['draft']['#attributes']['class'][] = 'hds-button--supplementary';
  }

}
