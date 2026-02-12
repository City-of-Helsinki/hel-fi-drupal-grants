<?php

declare(strict_types=1);

namespace Drupal\grants_application\Entity;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

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
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/application/{id}/render"
 *   }
 * )
 */
class ApplicationSubmission extends ContentEntityBase implements ContentEntityInterface, EntityChangedInterface {
  use EntityChangedTrait;
  use StringTranslationTrait;

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

    // Due to Application ID70 being used by multiple applications,
    // we can't use application type id to identify form submissions.
    $fields['form_identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Form identifier'))
      ->setReadOnly(TRUE);

    // {Env-name}-{application-id}-0000000{number-of-submission} if not prod.
    // On production, {application-id}-0000000{number-of-submission}.
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
      ->setDescription(new TranslatableMarkup('The time that the entity must be deleted.'));

    return $fields;
  }

  /**
   * Is the submission only a draft.
   *
   * @return bool
   *   Is draft.
   */
  public function isDraft(): bool {
    return (bool) $this->get('draft')->value;
  }

  /**
   * Submitted date time.
   *
   * @param string $format
   *   The formatting format for datetime object.
   *
   * @return string
   *   The date time string.
   */
  public function getSubmittedDateTime(string $format = 'd.m.Y H:i'): string {
    $changed = $this->get('changed')->value;
    $timezone = new \DateTimeZone('Europe/Helsinki');
    return (new \DateTime("@$changed"))
      ->setTimezone($timezone)
      ->format($format);
  }

  /**
   * Create view -link for oma asiointi.
   *
   * @param string $application_form_name
   *   Application name.
   *
   * @return \Drupal\Core\Link
   *   The link.
   */
  public function getViewApplicationLink(string $application_form_name): Link {
    $markup = $this->createMarkup('View application', $application_form_name);
    $url = Url::fromRoute(
      'grants_handler.view_application',
      ['submission_id' => $this->get('application_number')->value],
    );

    return Link::fromTextAndUrl($markup, $url);
  }

  /**
   * Create view -URL for asiointi.
   *
   * @return \Drupal\Core\Url
   *   The url.
   */
  public function getViewApplicationUrl(): Url {
    return Url::fromRoute(
      'grants_handler.view_application',
      ['submission_id' => $this->get('application_number')->value],
    );
  }

  /**
   * Create edit link for oma-asiointi.
   *
   * @param string $application_form_name
   *   Application name.
   *
   * @return \Drupal\Core\Link
   *   The link.
   */
  public function getEditApplicationLink(string $application_form_name): Link {
    $markup = $this->createMarkup('Edit application', $application_form_name);
    return Link::fromTextAndUrl($markup, $this->toUrl());
  }

  /**
   * Get the delete url.
   *
   * @return \Drupal\Core\Url
   *   The delete url.
   */
  public function getDeleteApplicationUrl(): Url {
    return Url::fromRoute(
      'helfi_grants.forms_app_remove',
      [
        'id' => $this->get('application_number')->value,
      ],
      [
        'attributes' => [
          'data-drupal-selector' => 'application-delete-link',
          'class' => [
            'application-delete-link-' . $this->get('application_number')->value,
          ],
        ],
      ]
    );
  }

  /**
   * Create markup for links.
   *
   * @param string $link_text
   *   The visible text to the link.
   * @param string $application_name
   *   Application name for visually hidden.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The markup.
   */
  private function createMarkup(string $link_text, string $application_name): MarkupInterface|string {
    // phpcs:disable
    return Markup::create(
      sprintf('%s %s %s %s',
        $this->t($link_text, [], ['context' => 'grants_handler']),
        '<span class="visually-hidden">',
        $application_name,
        '<span>',
      )
    );
    // phpcs:enable
  }

  /**
   * {@inheritDoc}
   */
  public function toUrl($rel = NULL, array $options = []): Url {
    $parameters = [
      'id' => $this->get('application_type_id')->value,
      'application_number' => $this->get('application_number')->value,
    ];

    return Url::fromRoute(
      'helfi_grants.forms_app',
      $parameters,
    );
  }

  /**
   * Get the print url.
   *
   * @return \Drupal\Core\Url
   *   The url.
   */
  public function getPrintApplicationUrl(): Url {
    $parameters = ['id' => $this->get('application_number')->value];
    $attributes = [
      'attributes' => [
        'data-drupal-selector' => 'application-print-link',
        'class' => ['hds-button', 'hds-button--supplementary'],
      ],
    ];

    return Url::fromRoute(
      'helfi_grants.print_view',
      $parameters,
      $attributes,
    );
  }

  /**
   * Get data used in list rendering.
   *
   * @return array
   *   Data required by "oma-asiointi" -listing page.
   */
  public function getData(): array {
    // Values are changed in ApplicationGetterService::getCompanyApplications.
    return [
      'application_type_id' => $this->get('application_type_id')->value,
      'form_identifier' => $this->get('form_identifier')->value,
      'form_timestamp_created' => date('Y-m-d h:i:s', (int) $this->get('created')->value),
      'form_timestamp' => $this->get('changed')->value,
      'form_timestamp_submitted' => $this->get('created')->value,
      'status' => $this->get('draft')->value ? 'DRAFT' : '',
      'application_number' => $this->get('application_number')->value,
      'language' => $this->get('langcode')->value,
      'messages' => [],
    ];
  }

  /**
   * Backward compatibility for oma-asiointi list.
   *
   * @return null
   *   Null value.
   */
  public function getWebform(): NULL {
    return NULL;
  }

}
