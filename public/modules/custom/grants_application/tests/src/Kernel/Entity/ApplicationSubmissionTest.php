<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Entity;

use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\grants_application\Entity\ApplicationSubmission
 *
 * @group grants_application
 */
final class ApplicationSubmissionTest extends KernelTestBase {

  /**
   * The application submission.
   *
   * @var \Drupal\grants_application\Entity\ApplicationSubmission
   */
  private ApplicationSubmission $applicationSubmission;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('application_submission');

    $this->applicationSubmission = ApplicationSubmission::create([
      'id' => 1,
      'uuid' => 'aaaaaaaa-1111-2222-3333-bbbcccdddeeee',
      'document_id' => 'bbbbbbbb-4444-5555-6666-fffggghhhiiijjj',
      'sub' => 'abcdefg-1234-5678-9012-hijklmnopqro',
      'business_id' => 'qwertyui-1234-1234-1234-qweasdzxcrty',
      'draft' => TRUE,
      'langcode' => 'fi',
      'application_type_id' => 58,
      'form_identifier' => 'liikunta_suunnistuskartta_avustu',
      'application_number' => 'KERNELTEST-058-0000001',
      'created' => '1765430954',
      'changed' => '1765430954',
    ]);
    $this->applicationSubmission->save();
  }

  /**
   * Test application submission entity.
   */
  public function testApplicationSubmissionEntity(): void {
    $viewUrl = $this->applicationSubmission->getViewApplicationLink('Liikuntasuunnistus');
    $this->assertTrue($viewUrl->getUrl()->toString() === '/hakemus/KERNELTEST-058-0000001/katso');

    $deleteUrl = $this->applicationSubmission->getDeleteApplicationUrl();
    $this->assertTrue($deleteUrl->toString() === '/application/KERNELTEST-058-0000001/remove');

    $editUrl = $this->applicationSubmission->getEditApplicationLink('Liikuntasuunnistus');
    $this->assertTrue($editUrl->getUrl()->toString() === '/application/new/58/KERNELTEST-058-0000001');

    $printUrl = $this->applicationSubmission->getPrintApplicationUrl();
    $this->assertTrue($printUrl->toString() === '/application/KERNELTEST-058-0000001/print');

    $keys = ['application_type_id', 'form_identifier', 'status', 'application_number', 'language'];
    foreach ($keys as $key) {
      $this->assertArrayHasKey($key, $this->applicationSubmission->getData());
    }

    $this->assertTrue($this->applicationSubmission->getData()['application_number'] === 'KERNELTEST-058-0000001');
  }

}
