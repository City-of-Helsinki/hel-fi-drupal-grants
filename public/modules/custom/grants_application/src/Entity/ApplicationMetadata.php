<?php

declare(strict_types=1);

namespace Drupal\grants_application\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Application metadata entity.
 *
 * @ContentEntityType(
 *   id = "application_metadata",
 *   label = @Translation("Application metadata"),
 *   label_collection = @Translation("Application metadata"),
 *   label_singular = @Translation("Application metadata item"),
 *   label_plural = @Translation("Application metadata items"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\grants_application\Form\ApplicationMetadataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "revision" = "Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *     "local_action_provider" = {
 *       "collection" = "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *   },
 *   base_table = "application_metadata",
 *   data_table = "application_metadata_field_data",
 *   revision_table = "application_metadata_revision",
 *   revision_data_table = "application_metadata_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = FALSE,
 *   admin_permission = "administer application_metadata",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "revision" = "vid",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "canonical" = "/admin/tools/application-metadata/{application_metadata}",
 *     "add-form" = "/admin/tools/application-metadata/add",
 *     "edit-form" = "/admin/tools/application-metadata/{application_metadata}/edit",
 *     "collection" = "/admin/tools/application-metadata",
 *     "version-history" = "/admin/tools/application-metadata/{application_metadata}/revisions",
 *     "revision" = "/admin/tools/application-metadata/{application_metadata}/revisions/{application_metadata_revision}/view",
 *     "delete-form" = "/admin/tools/application-metadata/{application_metadata}/delete",
 *   },
 *   field_ui_base_route = "entity.application_metadata.collection",
 * )
 */
final class ApplicationMetadata extends ContentEntityBase implements RevisionableInterface, RevisionLogInterface, EntityPublishedInterface, EntityOwnerInterface {

  use RevisionLogEntityTrait;
  use EntityPublishedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    $fields['application_type_select'] = static::listStringField(
      new TranslatableMarkup('Application type'),
      ['allowed_values_function', 'grants_application_application_type_allowed_values']
    );

    $fields['label'] = static::stringField(
      new TranslatableMarkup('Application name'),
      ['max_length' => 255],
    );

    $fields['application_type'] = static::stringField(
      new TranslatableMarkup('Application type code'),
      ['max_length' => 255],
    );

    $fields['application_type_id'] = static::stringField(
      new TranslatableMarkup('Application type ID'),
      ['max_length' => 255],
    );

    $fields['form_identifier'] = static::stringField(
      new TranslatableMarkup('Form identifier'),
      ['max_length' => 255],
    );

    $fields['application_industry'] = static::listStringField(
      new TranslatableMarkup('Grants industry'),
      ['allowed_values_function', 'grants_application_application_industry_allowed_values']
    );

    $fields['applicant_types'] = static::listStringField(
      new TranslatableMarkup('Applicant types'),
      ['allowed_values_function', 'grants_application_applicant_types_allowed_values'],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );

    $fields['application_subvention_type'] = static::listStringField(
      new TranslatableMarkup('Subvention type'),
      ['allowed_values_function', 'grants_application_application_subvention_types_allowed_values'],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );

    $fields['application_target_group'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Application target group'))
      ->setCardinality(1)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => ['target_group' => 'target_group'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['application_open'] = static::applyCommonDisplay(
      BaseFieldDefinition::create('datetime')
        ->setLabel(new TranslatableMarkup('Application opens'))
        ->setSetting('datetime_type', 'datetime'),
      'datetime_default',
    );

    $fields['application_close'] = static::applyCommonDisplay(
      BaseFieldDefinition::create('datetime')
        ->setLabel(new TranslatableMarkup('Application closes'))
        ->setSetting('datetime_type', 'datetime'),
      'datetime_default',
    );

    $fields['application_acting_years'] = static::listStringField(
      new TranslatableMarkup('Application acting years'),
      ['allowed_values_function', 'grants_application_application_acting_years_allowed_values'],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );

    $fields['application_continuous'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Application continuous'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['disable_copying'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Disable copying'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslation($langcode) {
    // The entity is not translatable, always return the base object.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUntranslated() {
    // The entity is not translatable, always return the base object.
    return $this;
  }

  /**
   * Get application metadata.
   *
   * @return array
   *   Returns array of application metadata field values.
   */
  public function getMetadata(): array {
    // Get the target group ID if it exists.
    $targetGroupId = NULL;
    if (!$this->get('application_target_group')->isEmpty()) {
      $targetGroupId = (int) $this->get('application_target_group')->target_id;
    }

    // Get the raw values from the fields.
    return [
      'title' => $this->label(),
      'description' => $this->application_type->value,
      'application_type' => $this->application_type->value,
      'application_type_id' => (int) $this->application_type_id->value,
      'form_identifier' => $this->form_identifier->value,
      'application_industry' => $this->application_industry->value,
      'application_open' => $this->application_open->value,
      'application_close' => $this->application_close->value,
      'applicant_types' => $this->getMultivalueFieldValues('applicant_types'),
      'subvention_type' => array_map('intval', $this->getMultivalueFieldValues('application_subvention_type')),
      'target_group' => $targetGroupId,
      'acting_years' => array_map('intval', $this->getMultivalueFieldValues('application_acting_years')),
      'continuous' => (bool) $this->application_continuous->value,
      'disable_copy' => (bool) $this->disable_copying->value,
    ];
  }

  /**
   * Get values from a multivalue field.
   *
   * @param string $field
   *   The field name.
   *
   * @return array
   *   Returns an array of field values.
   */
  protected function getMultivalueFieldValues(string $field): array {
    if (!$this->$field || $this->$field->isEmpty()) {
      return [];
    }

    return array_map(function ($item) {
      return is_array($item) && isset($item['value'])
        ? $item['value']
        : $item;
    }, $this->$field->getValue());
  }

  /**
   * Apply common display settings for fields.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $field
   *   The base field definition.
   * @param string $formWidget
   *   The form widget.
   * @param int $weight
   *   The weight.
   * @param bool $viewConfigurable
   *   Should the view be configurable.
   * @param array $formOptions
   *   The form options.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Returns the field definition.
   */
  private static function applyCommonDisplay(
    BaseFieldDefinition $field,
    string $formWidget,
    int $weight = 10,
    bool $viewConfigurable = TRUE,
    array $formOptions = [],
  ): BaseFieldDefinition {
    $field->setDisplayOptions('form', ['type' => $formWidget, 'weight' => $weight] + $formOptions)
      ->setDisplayConfigurable('form', TRUE);

    if ($viewConfigurable) {
      $field->setDisplayConfigurable('view', TRUE);
    }

    return $field;
  }

  /**
   * Create a list string field with common settings.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The field label.
   * @param array $settings
   *   The field settings.
   * @param int $cardinality
   *   The cardinality.
   * @param bool $required
   *   Should the field be required.
   * @param string $widget
   *   The field widget.
   * @param int $weight
   *   The weight.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Returns the field definition.
   */
  private static function listStringField(
    TranslatableMarkup $label,
    array $settings = [],
    int $cardinality = 1,
    bool $required = TRUE,
    string $widget = 'options_select',
    int $weight = 10,
  ): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('list_string')
      ->setLabel($label)
      ->setCardinality($cardinality)
      ->setRequired($required);

    if ($settings !== []) {
      $field->setSettings($settings);
    }

    return static::applyCommonDisplay($field, $widget, $weight);
  }

  /**
   * Create a string field with common settings.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The field label.
   * @param array $settings
   *   The field settings.
   * @param bool $required
   *   Should the field be required.
   * @param string $widget
   *   The field widget.
   * @param int $weight
   *   The weight.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Returns the field definition.
   */
  private static function stringField(
    TranslatableMarkup $label,
    array $settings = [],
    bool $required = TRUE,
    string $widget = 'string_textfield',
    int $weight = 10,
  ): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('string')
      ->setLabel($label)
      ->setRequired($required);

    if ($settings !== []) {
      $field->setSettings($settings);
    }

    return static::applyCommonDisplay($field, $widget, $weight);
  }

}
