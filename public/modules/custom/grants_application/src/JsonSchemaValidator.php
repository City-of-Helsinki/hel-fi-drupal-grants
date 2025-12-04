<?php

namespace Drupal\grants_application;


use JsonSchema\Validator;

/**
 * Wrapper for json-schema -library.
 */
final readonly class JsonSchemaValidator {

  public function __construct(private readonly Validator $validator){
  }

  /**
   * Validate the submission against schema
   * 
   * @param $value
   *   The value to validate, a result of json_decode function call.
   * @param $schema
   *   The schema to validate against, a result of json_decode function call.

   * @return array|bool
   *   Return TRUE or array of errors.
   */
  public function validate(object $value, object $schema): array|bool {
    $this->validator->validate($value, $schema);

    if ($this->validator->getErrors()) {
      return $this->validator->getErrors();
    }

    return true;
  }

}
