<?php

declare(strict_types=1);

namespace Drupal\grants_audit_log\EventSubscriber;

use Drupal\helfi_api_base\AuditLog\Event\AuditLogEvent;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\ProfiiliException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds grants specific data to audit log events.
 */
class GrantsAuditLogEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AuditLogEvent::class => 'addProfiiliUser',
    ];
  }

  /**
   * Sets the Helsinki profiili user id on the event.
   *
   * The base actor is added by helfi_api_base AuditLogActorSubscriber.
   * here we append the session id from profiili jwt token.
   *
   * @param \Drupal\helfi_api_base\AuditLog\Event\AuditLogEvent $event
   *   Event to handle.
   *
   * @see \Drupal\helfi_api_base\AuditLog\EventSubscriber\AuditLogActorSubscriber
   */
  public function addProfiiliUser(AuditLogEvent $event): void {
    try {
      $data = $this->helsinkiProfiiliUserData->getUserData();

      if ($data->sid !== NULL) {
        // Add sid field from tunnistamo jwt token. Only users authenticated
        // via Helsinki profiili have a session, so admins keep
        // the user id set by the generic actor subscriber.
        $event->setActor(array_merge($event->getActor(), [
          'user_id' => $data->sid,
        ]));
      }
    }
    catch (ProfiiliException) {
      // HelsinkiProfiiliUserData::getUserData throws if the helsinki profiili
      // token is not available. In that case, something has probably gone very
      // wrong already, but that should not prevent logging here.
    }
  }

}
