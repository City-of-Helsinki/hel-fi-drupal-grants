<?php

namespace Drupal\grants_handler;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;


/**
 * Twig extension.
 */
class GrantsHandlerTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getTests(): array {
    return [
      new TwigTest('numeric', function ($value) {
        return is_numeric($value);
      }),
    ];
  }

}
