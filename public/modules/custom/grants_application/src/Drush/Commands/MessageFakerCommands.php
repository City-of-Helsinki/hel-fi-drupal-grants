<?php

declare(strict_types=1);

namespace Drupal\grants_application\Drush\Commands;

use Consolidation\AnnotatedCommand\Attributes;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Opens the React form application periods.
 */
final class MessageFakerCommands extends DrushCommands {

  use AutowireTrait;
  use EnvironmentRestrictionTrait;

  public function __construct(
    private HelfiAtvService $atvService,
    private UuidInterface $uuid,
  ) {
    parent::__construct();
  }

  /**
   * Add a faked Avus2-message to an application.
   *
   * You should only use this on submitted applications.
   *
   * @param array<string, mixed> $options
   *   The command options.
   *
   * @return int
   *   The exit code.
   */
  #[Attributes\Command(name: 'grants-application:add-avus2-message', aliases: ['gaam'])]
  #[Attributes\Option(name: 'application-number', description: 'which application to update')]
  #[Attributes\Usage(name: 'drush grants-application:add-avus2-message --application-number=MYENV-999-00000001')]
  public function addFakedAvus2Message(array $options = ['application-number' => '']): int {
    if (!$this->isEnvironmentAllowed()) {
      $this->io()->error('Refusing to run in the environment.');
      return self::EXIT_FAILURE;
    }

    $applicationNumber = $options['application-number'];
    if (!$applicationNumber) {
      $this->io()->error('No application number.');
      return self::EXIT_FAILURE;
    }

    try {
      $document = $this->atvService->getDocument($applicationNumber);
    }
    catch (\Exception $e) {
      $this->io()->error('Unable to fetch the application: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }

    if ($document->getStatus() === 'DRAFT') {
      $this->io()->error('Avus2 does not know about draft applications.');
      return self::EXIT_FAILURE;
    }

    $content = $document->getContent();
    if (!isset($content['messages'])) {
      $content['messages'] = [];
    }

    $content['messages'][] = [
      'caseId' => $applicationNumber,
      'messageId' => $this->uuid->generate(),
      'body' => 'Faked a message from Avus2.',
      'sentBy' => 'Avustusten kasittelyjarjestelma',
      'sendDateTime' => new \DateTime()->format('Y-m-d\TH:i:s.v'),
    ];

    $document->setContent($content);
    try {
      $this->atvService->updateExistingDocument($document);
    }
    catch (\Exception $e) {
      $this->io()->error('Unable to update: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }

    return self::EXIT_SUCCESS;
  }

}
