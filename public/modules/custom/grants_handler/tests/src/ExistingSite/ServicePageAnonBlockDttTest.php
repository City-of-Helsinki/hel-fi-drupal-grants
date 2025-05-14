<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\ExistingSite;

use Drupal\Core\Url;
use Drupal\grants_handler\Entity\Node\ServicePage;
use Drupal\node\NodeInterface;
use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests service page anon block.
 *
 * @group grants_handler
 */
class ServicePageAnonBlockDttTest extends ExistingSiteTestBase {

  /**
   * Webform entity.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected Webform $webform;

  /**
   * Service page node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $servicePage;

  /**
   * Tests service page block for anonymous user.
   */
  public function testServicePageAnonBlock(): void {
    // Create a test webform.
    $this->webform = Webform::create([
      'id' => 'test_webform_anonymous_block',
      'title' => 'Webform for testing service page block',
    ]);
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationOpen', date('Y-m-d H:i:s', strtotime('-1 hour')));
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationClose', date('Y-m-d H:i:s', strtotime('+1 hour')));
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationContinuous', 1);
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationActingYears', date('Y'));
    $this->webform->save();
    $this->markEntityForCleanup($this->webform);

    // Create a service page.
    $this->servicePage = $this->createNode([
      'type' => 'service',
      'title' => 'Test service page',
      'status' => 1,
    ]);
    $this->servicePage->save();
    $this->markEntityForCleanup($this->servicePage);
    $this->assertTrue($this->servicePage instanceof ServicePage);

    // Create a user with permissions to edit the service page.
    $user = $this->createUser([
      'access content',
      'edit any service content',
    ]);

    // Log in and edit the service page.
    $this->drupalLogin($user);
    $edit_url = $this->servicePage->toUrl('edit-form');
    $this->drupalGet($edit_url);

    // Select the webform and save. Note. This has to be done via the edit form.
    // Otherwise, the webform reference is not saved to the node correctly.
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('field_webform[0][target_id]', 'Webform for testing service page block');
    $page->pressButton('Save');

    // Check for the block's theme output and check that the content
    // reflects the webform open state.
    $this->drupalGet($this->servicePage->toUrl());
    $this->assertSession()->elementExists('css', '.grants-service-page-block--anon');
    $this->assertSession()->pageTextContains('Change your role');

    // Log out and visit the service page. The block should
    // reflect closed state.
    $this->drupalGet(Url::fromRoute('user.logout.confirm'));
    $this->submitForm([], 'op', 'user-logout-confirm');
    $this->drupalResetSession();
    $this->drupalGet($this->servicePage->toUrl());
    $this->assertSession()->elementExists('css', '.grants-service-page-block--anon');
    $this->assertSession()->pageTextContains('Log in');

    // Simulate the webform closed state.
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationOpen', date('Y-m-d H:i:s', strtotime('-2 hours')));
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationClose', date('Y-m-d H:i:s', strtotime('-1 hour')));
    $this->webform->setThirdPartySetting('grants_metadata', 'applicationContinuous', 0);
    $this->webform->save();

    // Now assert that block content reflects "closed".
    $this->drupalGet($this->servicePage->toUrl());
    $this->assertSession()->pageTextContains('This application is not open');
  }

}
