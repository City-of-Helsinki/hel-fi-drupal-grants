<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili;

use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Prevent access to user/register & user/password urls and redirect.
 */
readonly class RegisterPageRedirectMiddleware implements HttpKernelInterface {

  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    $url = $request->getRequestUri();
    $language = $this->languageManager->getCurrentLanguage()->getId();

    if (
      str_contains($url, 'user/register') ||
      str_contains($url, 'user/password')
    ) {
      return new RedirectResponse('/' . $language);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
