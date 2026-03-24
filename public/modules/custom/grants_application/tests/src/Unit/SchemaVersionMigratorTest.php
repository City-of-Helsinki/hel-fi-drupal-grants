<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\grants_application\Migrator\MutatorInterface;
use Drupal\grants_application\Migrator\SchemaVersionMigrator;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\grants_application\Migrator\SchemaVersionMigrator
 *
 * @group grants_application
 */
final class SchemaVersionMigratorTest extends UnitTestCase {

  /**
   * Same version returns content unchanged without calling getMutators.
   */
  public function testSameVersionReturnsContentUnchanged(): void {
    $migrator = new SchemaVersionMigrator();
    $content = ['key' => 'value'];
    $this->assertSame($content, $migrator->migrate($content, '1', '1'));
  }

  /**
   * Different version with no mutators returns content unchanged.
   */
  public function testDifferentVersionNoMutatorsReturnsContentUnchanged(): void {
    $migrator = new SchemaVersionMigrator();
    $content = ['key' => 'value'];
    $this->assertSame($content, $migrator->migrate($content, '1', '2'));
  }

  /**
   * Mutators are applied in sequence when versions differ.
   */
  public function testMutatorsAreAppliedInSequence(): void {
    $mutatorA = $this->createMock(MutatorInterface::class);
    $mutatorA->method('mutate')->willReturnCallback(function (array $c) {
      $c['a'] = TRUE;
      return $c;
    });

    $mutatorB = $this->createMock(MutatorInterface::class);
    $mutatorB->method('mutate')->willReturnCallback(function (array $c) {
      $c['b'] = TRUE;
      return $c;
    });

    $migrator = new class([$mutatorA, $mutatorB]) extends SchemaVersionMigrator {

      public function __construct(private array $mockMutators) {}

      protected function getMutators(string $fromVersion, string $toVersion): array {
        return $this->mockMutators;
      }

    };

    $result = $migrator->migrate(['key' => 'value'], '1', '3');
    $this->assertTrue($result['a']);
    $this->assertTrue($result['b']);
  }

}
