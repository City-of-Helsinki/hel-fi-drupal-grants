<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Kernel;

use Drupal\KernelTests\KernelTestBase as CoreKernelTestBase;

/**
 * Base class for kernel tests.
 */
class KernelTestBase extends CoreKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'user',
    'externalauth',
    'openid_connect',
    'helfi_helsinki_profiili',
    'helfi_api_base',
  ];

}
