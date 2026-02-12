<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Test base class for the canonical filters.
 *
 * @group grants_application_search
 */
class CanonicalUnitTestBase extends UnitTestCase {

  /**
   * Invokes a protected or private method on an object for testing purposes.
   *
   * @param object $object
   *   The object instance on which to invoke the method.
   * @param string $methodName
   *   The name of the protected or private method to invoke.
   * @param array $arguments
   *   An array of arguments to pass to the method. Defaults to empty array.
   *
   * @return mixed
   *   The return value of the invoked method.
   *
   * @throws \ReflectionException
   *   Thrown when the specified method does not exist or cannot be accessed.
   */
  protected function invokeProtected(object $object, string $methodName, array $arguments = []): mixed {
    $reflectionObject = new \ReflectionObject($object);

    while (!$reflectionObject->hasMethod($methodName) && ($reflectionObject = $reflectionObject->getParentClass())) {
    }

    $reflectionMethod = $reflectionObject->getMethod($methodName);
    $reflectionMethod->setAccessible(TRUE);

    return $reflectionMethod->invokeArgs($object, $arguments);
  }

  /**
   * Sets a protected or private property on an object for testing purposes.
   *
   * @param object $object
   *   The object instance on which to set the property.
   * @param string $property
   *   The name of the protected or private property to set.
   * @param mixed $value
   *   The value to assign to the property.
   *
   * @throws \ReflectionException
   *   Thrown when the specified property does not exist or cannot be accessed.
   */
  protected function setProtectedProperty(object $object, string $property, mixed $value): void {
    $reflectionClass = new \ReflectionObject($object);
    while (!$reflectionClass->hasProperty($property) && ($reflectionClass = $reflectionClass->getParentClass())) {
    }
    $reflectionProperty = $reflectionClass->getProperty($property);
    $reflectionProperty->setAccessible(TRUE);
    $reflectionProperty->setValue($object, $value);
  }

}
