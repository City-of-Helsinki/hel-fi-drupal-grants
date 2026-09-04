<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_metadata\Unit;

use Drupal\grants_metadata\Validator\EmailValidator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\grants_metadata\Validator\EmailValidator
 *
 * @group grants_metadata
 */
final class EmailValidatorTest extends UnitTestCase {

  /**
   * Applies the pattern the way Drupal applies it to a form element.
   *
   * @param string $address
   *   The address to check.
   *
   * @return bool
   *   TRUE when the address is accepted.
   *
   * @see \Drupal\Core\Render\Element\FormElementBase::validatePattern()
   */
  private function accepts(string $address): bool {
    $pattern = EmailValidator::getPatternWithLengthLimits();

    return (bool) preg_match('{^(?:' . $pattern . ')$}u', $address);
  }

  /**
   * Builds a domain of an exact length out of labels of at most 63 characters.
   *
   * @param int $length
   *   The length the domain should have.
   *
   * @return string
   *   The domain.
   */
  private function domain(int $length): string {
    $tld = '.fi';
    $remaining = $length - strlen($tld);
    $labels = [];

    while ($remaining > 0) {
      $take = min(63, $remaining);
      $labels[] = str_repeat('d', $take);
      $remaining -= $take;
      if ($remaining > 0) {
        // The dot joining this label to the next one.
        $remaining--;
      }
    }

    return substr(implode('.', $labels) . $tld, 0, $length);
  }

  /**
   * Tests the length limits.
   *
   * @param int $localPartLength
   *   The length of the part before the "@".
   * @param int $domainLength
   *   The length of the part after the "@".
   * @param bool $expected
   *   Whether the address should be accepted.
   *
   * @dataProvider lengthProvider
   *
   * @covers ::getPatternWithLengthLimits
   */
  public function testLengthLimits(int $localPartLength, int $domainLength, bool $expected): void {
    $address = str_repeat('a', $localPartLength) . '@' . $this->domain($domainLength);

    $this->assertSame($expected, $this->accepts($address), sprintf(
      'An address of %d characters (local part %d, domain %d)',
      strlen($address),
      $localPartLength,
      $domainLength
    ));
  }

  /**
   * Data provider for testLengthLimits.
   *
   * @return array<string, array{int, int, bool}>
   *   Local part length, domain length, and whether it should be accepted.
   */
  public static function lengthProvider(): array {
    return [
      // The longest local part still within the total limit.
      'longest local part' => [64, 189, TRUE],
      // The longest domain still within the total limit.
      'longest domain' => [1, 252, TRUE],
      // One character over the local part limit.
      'local part too long' => [65, 189, FALSE],
      // Over both the domain limit and the total limit.
      'domain too long' => [1, 254, FALSE],
      // The length reported as breaking submission.
      'a hundred characters' => [30, 70, TRUE],
      'an ordinary address' => [12, 15, TRUE],
    ];
  }

  /**
   * Tests that the shape of an address is still checked.
   *
   * @param string $address
   *   The address to check.
   * @param bool $expected
   *   Whether the address should be accepted.
   *
   * @dataProvider shapeProvider
   *
   * @covers ::getPatternWithLengthLimits
   */
  public function testShape(string $address, bool $expected): void {
    $this->assertSame($expected, $this->accepts($address), $address);
  }

  /**
   * Data provider for testShape.
   *
   * @return array<string, array{string, bool}>
   *   An address and whether it should be accepted.
   */
  public static function shapeProvider(): array {
    return [
      'ordinary address' => ['matti.meikalainen@hel.fi', TRUE],
      'shortest address' => ['a@b.fi', TRUE],
      'no at sign' => ['abc', FALSE],
      'no domain' => ['a@', FALSE],
      'no local part' => ['@example.fi', FALSE],
      'a space' => ['a b@example.fi', FALSE],
      'two at signs' => ['a@b@example.fi', FALSE],
    ];
  }

}
