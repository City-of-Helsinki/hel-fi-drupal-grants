<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\ExistingSite;

use Drupal\Core\Url;
use Drupal\grants_handler\Entity\Node\ServicePage;
use Drupal\webform\Entity\Webform;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests service page anon block.
 *
 * @group grants_handler
 */
class ServicePageAnonBlockDttTest extends ExistingSiteBase {

  /**
   * Tests service page block for anonymous user when the application is open.
   */
  public function testServicePageOpenAnonBlock(): void {
    $result = $this->createServicePageWithWebformAssigned(
      'test_open_webform_anon_block',
      'Open webform for testing service page block',
      [
        'applicationOpen' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'applicationClose' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'applicationContinuous' => 1,
        'applicationActingYears' => date('Y'),
      ],
    );

    $servicePage = $result['servicePage'];

    // Check for the block's theme output and check that the content
    // reflects the webform open state.
    $this->drupalGet($servicePage->toUrl());
    $this->assertSession()->elementExists('css', '.grants-service-page-block--anon');
    $this->assertSession()->pageTextContains('Change your role');

    // Logout and visit the service page. The block should now show login.
    $this->drupalGet(Url::fromRoute('user.logout.confirm'));
    $this->submitForm([], 'op', 'user-logout-confirm');
    $this->drupalResetSession();
    $this->drupalGet($servicePage->toUrl());
    $this->assertSession()->elementExists('css', '.grants-service-page-block--anon');
    $this->assertSession()->pageTextContains('Log in');
  }

  /**
   * Tests service page block for anonymous user when the application is closed.
   */
  public function testServicePageClosedAnonBlock(): void {
    $result = $this->createServicePageWithWebformAssigned(
      'test_closed_webform_anon_block',
      'Closed webform for testing service page block',
      [
        'applicationOpen' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'applicationClose' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'applicationContinuous' => 0,
        'applicationActingYears' => date('Y'),
      ],
      'Test closed service page',
    );
    $servicePage = $result['servicePage'];

    // The block should reflect closed state.
    $this->drupalGet(Url::fromRoute('user.logout.confirm'));
    $this->submitForm([], 'op', 'user-logout-confirm');
    $this->drupalResetSession();
    $this->drupalGet($servicePage->toUrl());
    $this->assertSession()->pageTextContains('This application is not open');
  }

  /**
   * Creates a webform, service node and assigns the webform to the node via UI.
   *
   * The webform reference must be saved through the edit form, otherwise
   * the reference is not saved correctly to the node.
   *
   * @param string $webform_id
   *   Webform machine name.
   * @param string $webform_title
   *   Webform label as shown in the UI.
   * @param array $third_party_settings
   *   Third-party settings for grants_metadata, keyed by setting name.
   * @param string $service_page_title
   *   Service page node title.
   *
   * @return array
   *   Created entities.
   */
  protected function createServicePageWithWebformAssigned(
    string $webform_id,
    string $webform_title,
    array $third_party_settings,
    string $service_page_title = 'Test service page',
  ): array {
    // Create a test webform.
    $webform = Webform::create([
      'id' => $webform_id,
      'title' => $webform_title,
    ]);
    $webform->setThirdPartySetting('grants_metadata', 'applicationOpen', $third_party_settings['applicationOpen']);
    $webform->setThirdPartySetting('grants_metadata', 'applicationClose', $third_party_settings['applicationClose']);
    $webform->setThirdPartySetting('grants_metadata', 'applicationContinuous', $third_party_settings['applicationContinuous']);
    $webform->setThirdPartySetting('grants_metadata', 'applicationActingYears', $third_party_settings['applicationActingYears']);
    $webform->save();
    $this->markEntityForCleanup($webform);
    $this->assertTrue($webform instanceof Webform);

    // Create a service page.
    $servicePage = $this->createNode([
      'type' => 'service',
      'title' => $service_page_title,
      'status' => 1,
    ]);
    $servicePage->save();
    $this->markEntityForCleanup($servicePage);
    $this->assertTrue($servicePage instanceof ServicePage);

    // Create a user with permissions to edit the service page.
    $user = $this->createUser([
      'access content',
      'edit any service content',
    ]);
    $this->markEntityForCleanup($user);

    // Log in and edit the service page.
    $this->drupalLogin($user);
    $this->drupalGet($servicePage->toUrl('edit-form'));

    // Select the webform and save via UI.
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('field_webform[0][target_id]', $webform_title);
    $page->pressButton('Save');

    return [
      'webform' => $webform,
      'servicePage' => $servicePage,
      'user' => $user,
    ];
  }

}
