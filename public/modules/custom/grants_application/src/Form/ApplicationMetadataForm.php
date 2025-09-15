<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Application metadata form.
 */
final class ApplicationMetadataForm extends ContentEntityForm {

  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    protected FormSettingsService $formSettingsService,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get(FormSettingsServiceInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_metadata';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['label']['widget'][0]['value']['#description'] = $this->t('Application label will be auto-filled on submit based on Application type.');

    $application_types = $this->formSettingsService->getFormConfig('form_types');

    // Attach JS/CSS libraries.
    $form['#attached']['library'][] = 'grants_application/application_metadata_form';
    $form['#attached']['drupalSettings']['grants_application']['application_types'] = $application_types;

    // Set new revision to true.
    $form['revision']['#default_value'] = TRUE;

    // Set the following fields to "disabled".
    // The fields will be filled automatically when user selects the
    // application_type_select value.
    $form['label']['widget'][0]['value']['#attributes']['class'][] = 'is-read-only';
    $form['application_type']['widget'][0]['value']['#attributes']['class'][] = 'is-read-only';
    $form['application_type_id']['widget'][0]['value']['#attributes']['class'][] = 'is-read-only';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    $message = $this->t('Saved the application metadata for %label (ID: %id).', [
      '%label' => $entity->get('label')->getString(),
      '%id' => $entity->get('application_type_id')->getString(),
    ]);
    $this->messenger()->addStatus($message);
    $form_state->setRedirect('entity.application_metadata.collection');
    return $status;
  }

}
