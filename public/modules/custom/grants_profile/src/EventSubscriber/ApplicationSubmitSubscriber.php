<?php

declare(strict_types=1);

namespace Drupal\grants_profile\EventSubscriber;

use Drupal\grants_handler\Event\ApplicationSubmitEvent;
use Drupal\grants_profile\GrantsProfileService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes grants application submit events.
 */
class ApplicationSubmitSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly GrantsProfileService $grantsProfileService,
  ) {
  }

  /**
   * Handle application submit event.
   */
  public function onApplicationSubmit(ApplicationSubmitEvent $event): void {
    // Whenever user submits grants application,
    // extend profile lifetime by six years.
    $this->grantsProfileService->saveGrantsProfile([], deleteAfter: new \DateTimeImmutable('+6 years'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      ApplicationSubmitEvent::class => ['onApplicationSubmit'],
    ];
  }

}
