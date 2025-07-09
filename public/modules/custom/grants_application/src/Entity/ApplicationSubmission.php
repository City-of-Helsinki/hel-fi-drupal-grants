<?php

declare(strict_types=1);

namespace Drupal\grants_application\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
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
 *     "access" = "Drupal\grants_application\ApplicationSubmissionAccessControlHandler",
 *   }
 * )
 */
class ApplicationSubmission extends ContentEntityBase implements ContentEntityInterface, EntityChangedInterface {
  use EntityChangedTrait;

  /**
   * {@inheritDoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The ATV-document uuid.
    $fields['document_id'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setLabel(new TranslatableMarkup('ATV-document uuid'))
      ->setReadOnly(TRUE);

    // The user uuid coming from profiili.
    $fields['sub'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setLabel(new TranslatableMarkup('External user id'))
      ->setReadOnly(TRUE);

    // In case of registered community, multiple people might have access.
    $fields['business_id'] = BaseFieldDefinition::create('string')
      ->setRequired(FALSE)
      ->setLabel(new TranslatableMarkup('External business id'))
      ->setReadOnly(TRUE);

    // Saved as a draft or sent to backend.
    $fields['draft'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Draft'))
      ->setDefaultValue(TRUE);

    // Not translatable, which language was used to fill the application.
    $fields['langcode'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Language code'))
      ->setReadOnly(TRUE);

    $fields['application_type_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Application type id'))
      ->setReadOnly(TRUE);

    // {Env-name}-{application-id}-0000000{number-of-submission}.
    $fields['application_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Application number'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the node was last edited.'));

    // We might want to use the same delete after value here as atv uses.
    $fields['delete_after'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Delete after'))
      ->setDescription(new TranslatableMarkup('The time that the entity may be deleted.'));

    return $fields;
  }

  /**
   * Is the submission only a draft.
   *
   * @return bool
   *   Is draft.
   */
  public function isDraft(): bool {
    return $this->get('draft')->getValue();
  }

}
