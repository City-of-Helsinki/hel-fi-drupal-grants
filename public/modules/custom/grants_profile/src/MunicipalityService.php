<?php

namespace Drupal\grants_profile;

use Drupal\Component\Serialization\Json;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use GuzzleHttp\ClientInterface;

/**
 * MunicipalityService class.
 */
class MunicipalityService {

  private $dummyMapping = [
    '091' => 'VANTAA',
  ];

  /**
   * Constructs the service class.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HttpClient instance.
   * @param \Drupal\Core\KeyValueStore\KeyValueDatabaseFactory $databaseFactory
   *   Keyvalue database factory.
   */
  public function __construct(
    private ClientInterface $httpClient,
    private KeyValueDatabaseFactory $databaseFactory) {
  }

  /**
   * Return an Municipality name for given id.
   *
   * @param string $id
   *   ID of the municipality.
   *
   * @return string|void
   *   Return name if found, otherwise null.
   */
  public function getMunicipalityName(string $id): string|null {

    $currentData = $this->getMunicipalityData();

    if (isset($currentData[$id])) {
      return $currentData[$id];
    }

    return NULL;
  }

  /**
   * Get municipality data.
   */
  private function getMunicipalityData(): array {
    return $this->dummyMapping;
  }

  /**
   * Retrieves data from the endpoint.
   */
  private function retrieveDataFromEndpoint(): array {
    $response = $this->httpClient->request('GET', '');

    $body = $response->getBody();
    $json = JSON::decode($body);

    $this->processMunicipalityData($json);
    return [];
  }

  /**
   * Processes the data from enpoint and saves to keyvalue store.
   *
   * @param array $data
   *
   * @return void
   */
  private function processMunicipalityData(array $data): void {
    $processedData = [];
    $valueStore = $this->getKeyValueDatabase();
    $valueStore->set('data', $processedData);
    $valueStore->set('updatedAt', new \DateTime('now'));
  }

  /**
   * Get key value database.
   */
  private function getKeyValueDatabase(): KeyValueStoreInterface {
    return $this->databaseFactory->get('grants_municipality_data');
  }

}
