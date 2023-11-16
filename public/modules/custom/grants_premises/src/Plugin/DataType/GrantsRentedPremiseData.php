  <?php

  namespace Drupal\grants_premises\Plugin\DataType;

  use Drupal\Core\TypedData\Plugin\DataType\Map;
  use Drupal\grants_metadata\Plugin\DataType\DataFormatTrait;

  /**
   * Grants Rented Premise Data.
   *
   * @DataType(
   * id = "grants_rented_premise",
   * label = @Translation("Rented premise"),
   * definition_class =
   *   "\Drupal\grants_premises\TypedData\Definition\GrantsRentedPremiseDefinition"
   * )
   */
  class GrantsRentedPremiseData extends Map {

    use DataFormatTrait;

    /**
     * {@inheritdoc}
     */
    public function getValue() {
      $retval = parent::getValue();
      return $retval;
    }

    /**
     * Get values from parent.
     *
     * @return array
     *   The values.
     */
    public function getValues(): array {
      return $this->values;
    }

  }
