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
    $this->assertEquals('/application/view/KERNELTEST-058-0000001', $viewUrl->getUrl()->toString());

    $deleteUrl = $this->applicationSubmission->getDeleteApplicationUrl();
    $this->assertEquals('/application/KERNELTEST-058-0000001/remove', $deleteUrl->toString());

    $editUrl = $this->applicationSubmission->getEditApplicationLink('Liikuntasuunnistus');
    $this->assertEquals('/application/new/liikunta_suunnistuskartta_avustu/KERNELTEST-058-0000001', $editUrl->getUrl()->toString());

    $printUrl = $this->applicationSubmission->getPrintApplicationUrl();
    $this->assertEquals('/application/KERNELTEST-058-0000001/print', $printUrl->toString());

    $keys = ['application_type_id', 'form_identifier', 'status', 'application_number', 'language'];
    foreach ($keys as $key) {
      $this->assertArrayHasKey($key, $this->applicationSubmission->getData());
    }

    $this->assertEquals('KERNELTEST-058-0000001', $this->applicationSubmission->getData()['application_number']);

    $this->applicationSubmission->set('draft', FALSE);
    $editUrl = $this->applicationSubmission->getEditApplicationLink('Liikuntasuunnistus');
    $this->assertEquals('/application/new/liikunta_suunnistuskartta_avustu/KERNELTEST-058-0000001/edit', $editUrl->getUrl()->toString());
  }

}
