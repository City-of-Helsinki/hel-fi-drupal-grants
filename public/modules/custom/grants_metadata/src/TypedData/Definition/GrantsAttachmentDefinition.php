<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application official data.
 */
class GrantsAttachmentDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['description'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'description',
        ]);

      $info['fileName'] = DataDefinition::create('string')
        ->setRequired(FALSE)
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'fileName',
        ])
        ->setSetting('skipEmptyValue', TRUE);

      $info['fileType'] = DataDefinition::create('integer')
        ->setRequired(TRUE)
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'int',
        ])
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'fileType',
        ]);

      $info['integrationID'] = DataDefinition::create('string')
        ->setRequired(FALSE)
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'integrationID',
        ]);

      $info['isDeliveredLater'] = DataDefinition::create('boolean')
        ->setRequired(TRUE)
        ->setSetting('defaultValue', FALSE)
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ])
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'isDeliveredLater',
        ]);

      $info['isIncludedInOtherFile'] = DataDefinition::create('boolean')
        ->setRequired(TRUE)
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ])
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'isIncludedInOtherFile',
        ])
        ->setSetting('defaultValue', FALSE);

      $info['isNewAttachment'] = DataDefinition::create('boolean')
        ->setRequired(FALSE)
        ->setSetting('defaultValue', TRUE)
        ->setSetting('defaultValue', '1')
        ->setSetting('typeOverride', [
          'dataType' => 'string',
          'jsonType' => 'bool',
        ])
        ->setSetting('jsonPath', [
          'attachmentsInfo',
          'attachmentsArray',
          'isNewAttachment',
        ]);

    }
    return $this->propertyDefinitions;
  }

}
