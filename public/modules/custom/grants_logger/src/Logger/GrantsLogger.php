<?php

namespace Drupal\grants_logger\Logger;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\dblog\Logger\DbLog;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Psr\Log\LoggerInterface;

/**
 * Override DbLog to include custom data.
 */
class GrantsLogger extends DbLog {

  use RfcLoggerTrait;
  use DependencySerializationTrait;


  /**
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs a GrantsLogger object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata
   *   The helfi_helsinki_profiili.userdata service.
   */
  public function __construct(
    Connection $connection,
    LogMessageParserInterface $parser,
    HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata
  ) {
    $this->connection = $connection;
    $this->parser = $parser;
    $this->helfiHelsinkiProfiiliUserdata = $helfiHelsinkiProfiiliUserdata;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {

    if ($this->helfiHelsinkiProfiiliUserdata->isAuthenticatedExternally()) {
      $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();
      if ($userData) {
        $message = $message . (' (HP UUID: @helfi_hp_uid)');
        $context['@helfi_hp_uid'] = $userData["sub"];
      }
    }

    parent::log($level, $message, $context);
  }

}
