<?php

namespace Drupal\grants_metadata\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Attachment DataType.
 *
 * @DataType(
 * id = "grants_metadata_attachment",
 * label = @Translation("Attachment"),
 * definition_class = "\Drupal\grants_metadata\TypedData\Definition\GrantsAttachmentDefinition"
 * )
 */
class GrantsAttachmentData extends Map {

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

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values['attachmentIsNew'])) {
      unset($values['attachmentIsNew']);
    }
    if (isset($values['isNewAttachment'])) {
      unset($values['isNewAttachment']);
    }

    if ($values["isDeliveredLater"] === 'true') {
      $values["isDeliveredLater"] = TRUE;
    }
    if ($values["isDeliveredLater"] === 'false' || $values["isDeliveredLater"] === '') {
      $values["isDeliveredLater"] = FALSE;
    }

    if ($values["isIncludedInOtherFile"] === 'true') {
      $values["isIncludedInOtherFile"] = TRUE;
    }
    if ($values["isIncludedInOtherFile"] === 'false' || $values["isIncludedInOtherFile"] === '') {
      $values["isIncludedInOtherFile"] = FALSE;
    }

    parent::setValue($values, $notify);
  }

}
