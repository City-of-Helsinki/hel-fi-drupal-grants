<?php

namespace Drupal\grants_handler\Commands;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\node\Entity\Node;
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

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory) {
    $this->keyValueStore = $key_value_factory;
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

    $archivedWebForms = \Drupal::entityTypeManager()
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

    $entityQuery = \Drupal::entityQuery('node')
      // Access checks on content are required.
      ->accessCheck(FALSE)
      ->condition('type', 'service')
      ->condition('field_webform', $webformIds);

    $results = $entityQuery->execute();
    $servicePages = Node::loadMultiple($results);

    foreach ($servicePages as $page) {
      $currentWebform = reset($page->get('field_webform')->getValue());
      $currentWebformObj = \Drupal::entityTypeManager()->getStorage('webform')->load($currentWebform['target_id']);
      $formId = $currentWebformObj->getThirdPartySetting('grants_metadata', 'applicationType');

      $latestVersion = ApplicationHandler::getLatestApplicationForm($formId);
      $thirdPartySettings = $latestVersion->getThirdPartySettings('grants_metadata');

      if ($latestVersion === NULL) {
        $this->output->writeln('No open webform found for: ' . $formId);
        continue;
      }

      $page->set('field_webform', $latestVersion->id());

      $status = $latestVersion->isOpen();
      grants_metadata_set_node_values($page, $status, $thirdPartySettings);

      $page->save();
    }

    $this->output->writeln('Updating service page webform references');
  }

}
