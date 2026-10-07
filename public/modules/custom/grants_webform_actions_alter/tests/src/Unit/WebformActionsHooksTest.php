<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_webform_actions_alter\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\FormLockService;
use Drupal\grants_webform_actions_alter\Hook\WebformActionsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformSubmissionInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the webform actions hooks.
 */
#[Group('grants_webform_actions_alter')]
class WebformActionsHooksTest extends UnitTestCase {

  /**
   * Gets the hooks with a submission and a lock status.
   *
   * @param array<string, mixed> $data
   *   The submission data.
   * @param bool $locked
   *   Whether the application form is locked.
   *
   * @return \Drupal\grants_webform_actions_alter\Hook\WebformActionsHooks
   *   The hooks.
   */
  private function getHooks(array $data = [], bool $locked = FALSE): WebformActionsHooks {
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $submission->method('getData')->willReturn($data);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($submission);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->with('webform_submission')->willReturn($storage);

    $formLockService = $this->createMock(FormLockService::class);
    $formLockService->method('isApplicationFormLocked')->willReturn($locked);

    $hooks = new WebformActionsHooks($entityTypeManager, $formLockService);
    $hooks->setStringTranslation($this->getStringTranslationStub());
    return $hooks;
  }

  /**
   * Gets the template variables for a submission.
   *
   * @return array<string, mixed>
   *   The template variables.
   */
  private function getVariables(): array {
    return [
      'element' => [
        '#webform_submission' => 1,
        'draft' => [],
      ],
    ];
  }

  /**
   * Tests that the delete action dialog is disabled.
   */
  public function testDeleteDialogIsDisabled(): void {
    $element = [
      '#type' => 'webform_actions',
      '#delete__dialog' => TRUE,
    ];

    $this->getHooks()->webformActionsAlter($element, $this->createMock(FormStateInterface::class), []);

    $this->assertFalse($element['#delete__dialog'], 'The delete action dialog is disabled.');
  }

  /**
   * Tests that variables are left untouched without a submission.
   */
  public function testVariablesAreUntouchedWithoutSubmission(): void {
    $variables = ['element' => ['draft' => []]];

    $this->getHooks()->preprocessWebformActions($variables);

    $this->assertSame(['element' => ['draft' => []]], $variables, 'The variables are untouched.');
  }

  /**
   * Tests that an unlocked draft gets the delete draft link.
   */
  public function testUnlockedDraftGetsDeleteDraftLink(): void {
    $variables = $this->getVariables();

    $this->getHooks(['application_number' => 'DEV-064-0010266', 'status' => 'DRAFT'])->preprocessWebformActions($variables);

    $this->assertSame('DEV-064-0010266', $variables['applicationNumber'], 'The application number is set.');
    $this->assertSame('webform-button--delete-draft', $variables['delete_draft']['#id'], 'The delete draft link is set.');
    $this->assertSame('grants_handler.clear-navigations', $variables['delete_draft']['#url']->getRouteName(), 'The delete draft link points to the draft removal route.');
    $this->assertSame(['submission_id' => 'DEV-064-0010266'], $variables['delete_draft']['#url']->getRouteParameters(), 'The delete draft link targets the application.');
    $this->assertSame('delete_draft', array_key_first($variables['element']), 'The delete draft link is the first action.');
    $this->assertContains('hds-button--supplementary', $variables['element']['draft']['#attributes']['class'], 'The draft button is supplementary.');
  }

  /**
   * Tests that a locked draft does not get the delete draft link.
   */
  public function testLockedDraftDoesNotGetDeleteDraftLink(): void {
    $variables = $this->getVariables();

    $this->getHooks(['application_number' => 'DEV-064-0010266', 'status' => 'DRAFT'], TRUE)->preprocessWebformActions($variables);

    $this->assertArrayNotHasKey('delete_draft', $variables, 'The delete draft link is not set.');
    $this->assertArrayNotHasKey('delete_draft', $variables['element'], 'The delete draft action is not added.');
  }

  /**
   * Tests that a sent application does not get the delete draft link.
   */
  public function testSentApplicationDoesNotGetDeleteDraftLink(): void {
    $variables = $this->getVariables();

    $this->getHooks(['application_number' => 'DEV-064-0010266', 'status' => 'SUBMITTED'])->preprocessWebformActions($variables);

    $this->assertArrayNotHasKey('delete_draft', $variables, 'The delete draft link is not set.');
    $this->assertArrayNotHasKey('delete_draft', $variables['element'], 'The delete draft action is not added.');
  }

}
