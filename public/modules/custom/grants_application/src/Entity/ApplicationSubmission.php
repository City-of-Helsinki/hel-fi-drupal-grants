<?php

declare(strict_types=1);

namespace Drupal\grants_application\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Application submission entity.
 *
 * @ContentEntityType(
 *   id = "application_submission",
 *   label = @Translation("Grants application submission"),
 *   base_table = "application_submission",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "published" = "status",
 *     "created" = "created",
 *     "changed" = "changed",
 *   },
 *   fieldable = FALSE,
 *   admin_permission = "administer content",
 *   handlers = {
 *     "access" = "Drupal\node\NodeAccessControlHandler",
 *   }
 * )
 */
class ApplicationSubmission extends ContentEntityBase implements ContentEntityInterface {
  use EntityChangedTrait;

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The user uuid coming from profiili.
    $fields['sub'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('External user id'))
      ->setReadOnly(TRUE);

    // Saved as a draft or sent to backend.
    $fields['draft'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Draft'))
      ->setDefaultValue(TRUE);

    // Not translatable, which language was used to fill the application.
    $fields['langcode'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Language code'))
      ->setReadOnly(TRUE);

    // {Env-name}-{application-id}-0000000{number-of-submission}.
    $fields['application_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Application number'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
