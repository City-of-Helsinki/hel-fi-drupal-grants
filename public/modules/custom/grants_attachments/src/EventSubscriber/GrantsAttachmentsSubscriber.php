<?php

namespace Drupal\grants_attachments\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber;
use Drupal\Core\Form\Exception\BrokenPostRequestException;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Grants_attachments event subscriber.
 */
class GrantsAttachmentsSubscriber extends FormAjaxSubscriber {

  const SIZE_LIMIT_IN_BYTES = 20971520;

  /**
   * Catches a form AJAX exception and build a response from it.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function onException(ExceptionEvent $event): void {
    $exception = $event->getThrowable();
    $request = $event->getRequest();

    if ($exception instanceof BrokenPostRequestException && $request->query->has(FormBuilderInterface::AJAX_FORM_REQUEST)) {
      // Forcefully set 20 MB limit to the error message.
      $size = ByteSizeMarkup::create(self::SIZE_LIMIT_IN_BYTES);
      $this->messenger->addError($this->t('An unrecoverable error occurred. The uploaded file likely exceeded the maximum file size (@size) that this server supports.', ['@size' => $size]));
      $response = new AjaxResponse(NULL, 200);
      $status_messages = ['#type' => 'status_messages'];
      $response->addCommand(new PrependCommand(NULL, $status_messages));
      $event->allowCustomResponseCode();
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before exception.logger.
    $events[KernelEvents::EXCEPTION] = ['onException', 52];
    // Run before main_content_view_subscriber.
    return $events;
  }

}
