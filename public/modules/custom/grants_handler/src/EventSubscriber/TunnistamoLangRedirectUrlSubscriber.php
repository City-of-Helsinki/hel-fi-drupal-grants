<?php

declare(strict_types = 1);

namespace Drupal\grants_handler\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\helfi_tunnistamo\Event\RedirectUrlEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to Tunnistamo redirect events to add language parameter to uri.
 *
 * We want to eventually support all languages in authentication with Tunnistamo
 * so we need to support all language parameters. Helfi_proxy module does this
 * for proxied urls, we cannot use that so here is implementation without proxy.
 */
class TunnistamoLangRedirectUrlSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    private LanguageManagerInterface $languageManager
  ) {
  }

  /**
   * Responds to Tunnistamo redirect url event.
   *
   * @param \Drupal\helfi_tunnistamo\Event\RedirectUrlEvent $event
   *   Response event.
   */
  public function onRedirectUrlEvent(RedirectUrlEvent $event) : void {

    $uriOptions['language'] = $this->languageManager->getCurrentLanguage();

    // Support all languages with tunnistamo urls.
    $returnUrl = sprintf(
      '/%s/openid-connect/%s', $uriOptions['language']->getId(), $event->getClient()->getParentEntityId()
    );

    if (!$returnUrl) {
      return;
    }

    try {
      $event->setRedirectUrl(Url::fromUserInput($returnUrl, $uriOptions)->setAbsolute());
    }
    catch (\InvalidArgumentException $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      RedirectUrlEvent::class => ['onRedirectUrlEvent'],
    ];
  }

}
