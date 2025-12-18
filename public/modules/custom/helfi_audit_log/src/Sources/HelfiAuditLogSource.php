<?php

declare(strict_types=1);

namespace Drupal\helfi_audit_log\Sources;

use Drupal\Core\Database\Database;
use ResilientLogger\Sources\AbstractLogSource;
use ResilientLogger\Sources\Types;
use ResilientLogger\Utils\Helpers;

/**
 * @phpstan-import-type LogSourceConfig from Types
 * @phpstan-import-type AuditLogDocument from Types
 */
class HelfiAuditLogSource implements AbstractLogSource {
    private const TABLE_NAME = 'helfi_audit_logs';
    private const KNOWN_KEYS = [
        'actor',
        'date_time',
        'operation',
        'target',
        'status',
        'origin'
    ];

    private int $id;

    /** @var LogSourceConfig $config */
    private static array $config;

    public function __construct(int $id) {
        $this->id = $id;
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * @return AuditLogDocument
     */
    public function getDocument(): array {
        $result = Database::getConnection()
            ->select(self::TABLE_NAME, 'h')
            ->fields('h')
            ->condition('id', $this->id)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        $timestamp = strtotime($result['created_at']);
        $createdAt = (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');

        $message = json_decode($result['message'], TRUE);
        $data = array_intersect_key(
            $message["audit_event"],
            array_flip(self::KNOWN_KEYS)
        );

        $extra = array_diff_key(
            $message["audit_event"],
            array_flip(self::KNOWN_KEYS)
        );

        return [
            "@timestamp" => $createdAt,
            "audit_event" => [
                "actor" => Helpers::valueAsArray($data["actor"]),
                "date_time" => $data["date_time"],
                "operation" => $data["operation"],
                "origin" => self::$config["origin"],
                "target" => Helpers::valueAsArray($data["target"]),
                "environment" => self::$config["environment"],
                "message" => $data["status"],
                "level" => 0,
                "extra" => array_merge($extra, [
                    "status" => $data["status"],
                    "source_pk" => $this->id,
                    "original_origin" => $data["origin"]
                ]),
            ],
        ];
    }

    public function isSent(): bool {
        $result = Database::getConnection()
            ->select(self::TABLE_NAME, 'h')
            ->fields('h', ['is_sent'])
            ->condition('id', $this->id)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return (bool) $result['is_sent'];
    }

    public function markSent(): void {
        Database::getConnection()
            ->update(self::TABLE_NAME)
            ->fields(['is_sent' => 1])
            ->condition('id', $this->id)
            ->execute();
    }

    public static function configure(mixed $config): void {
        self::$config = $config;
    }

    public static function create(int $level, mixed $message, array $context = []): AbstractLogSource {
        throw new \LogicException(sprintf('%s does not support create().', static::class));
    }
    
    /** @return \Generator<AbstractLogSource> */
    public static function getUnsentEntries(int $chunkSize): \Generator {
      $results = Database::getConnection()
          ->select(self::TABLE_NAME, 'h')
          ->fields('h', ['id'])
          ->condition('is_sent', 0)
          ->range(0, $chunkSize)
          ->orderBy('id', 'ASC')
          ->execute()
          ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            yield new HelfiAuditLogSource(intval($result['id']));
        }
    }

    public static function clearSentEntries(int $daysToKeep): void {
        $olderThan = gmdate('Y-m-d H:i:s', time() - ($daysToKeep * 86400));
        Database::getConnection()
            ->delete(self::TABLE_NAME)
            ->condition('is_sent', 1)
            ->condition('created_at', $olderThan, '<=')
            ->execute();
    }
}
?>
