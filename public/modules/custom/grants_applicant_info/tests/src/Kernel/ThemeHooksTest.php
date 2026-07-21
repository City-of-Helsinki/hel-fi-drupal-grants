<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_applicant_info\Kernel;

use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the grants_applicant_info theme hooks.
 */
#[Group('grants_applicant_info')]
class ThemeHooksTest extends KernelTestBase {

  /**
   * The renderer.
   */
  private RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Renders the given build in isolation.
   *
   * @param array<string, mixed> $build
   *   The render array.
   *
   * @return string
   *   The rendered markup.
   */
  private function renderBuild(array $build): string {
    return (string) $this->renderer->renderInIsolation($build);
  }

  /**
   * Test that the applicant_info theme hook renders its children.
   */
  public function testThemeHookRendersChildren(): void {
    $markup = $this->renderBuild([
      '#theme' => 'applicant_info',
      'child' => [
        '#markup' => 'Applicant name',
      ],
    ]);

    $this->assertStringContainsString('Applicant name', $markup);
  }

}
