<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Kernel;

use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the RegisterPageRedirectMiddleware.
 */
#[Group('helfi_helsinki_profiili')]
#[RunTestsInSeparateProcesses]
class RegisterPageRedirectMiddlewareTest extends KernelTestBase {

  use ApiTestTrait;

  /**
   * Tests that request is redirected.
   */
  public function testUserRegisterRedirects(): void {
    $request = $this->getMockedRequest('/user/register');
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    $this->assertEquals('/en', $response->headers->get('Location'));

    $request = $this->getMockedRequest('/user/password');
    $response = $this->processRequest($request);

    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
    $this->assertEquals('/en', $response->headers->get('Location'));
  }

}
