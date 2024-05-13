<?php

namespace Drupal\grants_handler\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationHandler;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://git.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://git.drupalcode.org/devel/tree/drush.services.yml
 */
class UpdateCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueStore;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->keyValueStore = $key_value_factory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Corrects a field storage configuration.
   *
   * See https://www.drupal.org/project/drupal/issues/2916266 for more info.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Field name.
   *
   * @command update:correct-field-config-storage
   */
  public function correctFieldStorageConfig($entity_type, $bundle, $field_name) {
    $field_map_kv_store = $this->keyValueStore->get('entity.definitions.bundle_field_map');
    $map = $field_map_kv_store->get($entity_type);
    unset($map[$field_name]['bundles'][$bundle]);
    $field_map_kv_store->set($entity_type, $map);
  }

  /**
   * Updates Service Page webform references to latest one.
   *
   * @command update:webform-references
   */
  public function updateServicepageWebformReferences() {

    $archivedWebForms = $this->entityTypeManager
      ->getStorage('webform')
      ->loadByProperties([
        'third_party_settings.grants_metadata.status' => 'archived',
      ]);

    $webformIds = [];

    foreach ($archivedWebForms as $archivedWebForm) {
      $webformIds[] = $archivedWebForm->id();
    }

    if (empty($archivedWebForm)) {
      $this->output->writeln('No archived webforms.');
      return;
    }

    $entityQuery = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      // Access checks on content are required.
      ->accessCheck(FALSE)
      ->condition('type', 'service')
      ->condition('field_webform', $webformIds);

    $results = $entityQuery->execute();
    $servicePages = $this->entityTypeManager->getStorage('node')->loadMultiple($results);

    foreach ($servicePages as $page) {
      $currentWebform = reset($page->get('field_webform')->getValue());
      $currentWebformObj = $this->entityTypeManager->getStorage('webform')->load($currentWebform['target_id']);
      $applicationType = $currentWebformObj->getThirdPartySetting('grants_metadata', 'applicationType');

      $latestVersion = ApplicationHandler::getLatestApplicationForm($applicationType);
      $thirdPartySettings = $latestVersion->getThirdPartySettings('grants_metadata');

      if ($latestVersion === NULL) {
        $this->output->writeln('No open webform found for: ' . $applicationType);
        continue;
      }

      $page->set('field_webform', $latestVersion->id());

      grants_metadata_set_node_values($page, $thirdPartySettings);

      $this->output->writeLn($this->t('Updated webform reference for service page: @title (@formType: @newId)', [
        '@title'    => $page->getTitle(),
        '@formType' => $thirdPartySettings['applicationType'],
        '@newId'    => $latestVersion->id(),
      ]));

      $page->save();
    }

    $this->output->writeln('== Updated service page webform references ==');
  }

}
