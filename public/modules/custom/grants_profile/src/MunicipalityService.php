<?php

namespace Drupal\grants_profile;

use Drupal\Component\Serialization\Json;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to fetch municipality info and convert IDs to names.
 */
class MunicipalityService {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $loggerChannel;

  /**
   * Constructs the service class.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HttpClient instance.
   * @param \Drupal\Core\KeyValueStore\KeyValueDatabaseFactory $databaseFactory
   *   Keyvalue database factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger factory.
   */
  public function __construct(
    private ClientInterface $httpClient,
    private KeyValueDatabaseFactory $databaseFactory,
    private LoggerChannelFactory $loggerFactory,
    ) {
    $this->loggerChannel = $loggerFactory->get('grants_profile');
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
      return strtoupper($currentData[$id]);
    }

    return NULL;
  }

  /**
   * Get municipality data.
   */
  public function getMunicipalityData(): array {

    $kvStorage = $this->getKeyValueDatabase();
    $storageData = $kvStorage->get('data');
    $updateTimestamp = $this->getUpdatedAt();

    $currentYear = date('Y');
    $updatedAtYear = NULL;

    if ($updateTimestamp) {
      $updatedAtYear = $updateTimestamp->format('Y');
    }

    // Retrieve new data if no existing is found, or year has changed.
    if (!$storageData || (!$updatedAtYear) || $currentYear > $updatedAtYear) {
      try {
        return $this->retrieveDataFromEndpoint();
      }
      catch (\Exception $e) {
        $this->loggerChannel->error(
          'Failed to update municipality data. Error: @message',
          ['@message' => $e->getMessage()]
              );

        // If fetch failed, use old data, or empty string if no data is saved.
        if (!empty($storageData)) {
          return JSON::decode($storageData);
        }

        return [];
      }
    }

    return JSON::decode($storageData);
  }

  /**
   * Retrieves data from the endpoint.
   */
  public function retrieveDataFromEndpoint(string $endpoint = ''): array {

    if ($endpoint === '') {
      $endpoint = $this->getEndpoint();
    }

    // Fetch data from the given endpoint.
    $response = $this->httpClient->request('GET', $endpoint, [
      'query' => [
        'content' => 'data',
        'meta' => 'max',
        'lang' => 'fi',
        'format' => 'json',
      ],
      'timeout' => 5,
    ]);

    $statusCode = $response->getStatusCode();

    // Log and throw error on non 200 status.
    if ($statusCode !== Response::HTTP_OK) {
      $this->loggerChannel->error('The endpoint did not return 200 status. Endpoint: @endpoint, Status: @status', [
        '@endpoint' => $endpoint,
        '@status' => $statusCode,
      ]);
      throw new GrantsProfileException('', $statusCode);
    }

    // Parse body and process the data.
    $body = $response->getBody();
    $json = JSON::decode($body);
    $processedData = $this->processMunicipalityData($json);

    // If data is empty after processing, throw error and log it.
    if (empty($processedData)) {
      $this->loggerChannel->error('Empty data after processing. Endpoint: @endpoint', [
        '@endpoint' => $endpoint,
      ]);
      throw new GrantsProfileException('Empty municipality data response from the endpoint');
    }

    $this->loggerChannel->notice('Updated municipality data from the endpoint: @endpoint', [
      '@endpoint' => $endpoint,
    ]);

    return $processedData;
  }

  /**
   * Get default endpoint for the request.
   *
   * @return string
   *   Endpoint url with current year.
   */
  public function getEndpoint(): string {
    $currentYear = date('Y');
    return "https://data.stat.fi/api/classifications/v2/classifications/kunta_1_{$currentYear}0101/classificationItems";
  }

  /**
   * Processes the data from enpoint and saves to keyvalue store.
   *
   * @param array $data
   *   Data from the endpoint.
   *
   * @return array
   *   Processed data as an array.
   */
  private function processMunicipalityData(array $data): array {
    $processedData = [];

    foreach ($data as $value) {
      // Endpoint is filtered to FI, so we can assume,
      // that the first element is a correct one.
      $valueName = $value['classificationItemNames'][0]['name'] ?? NULL;
      $valueCode = $value['code'];
      if (!$valueName) {
        // Log this, if for some reason there is no name for the code.
        continue;
      }

      $processedData[$valueCode] = $valueName;
    }

    $json = JSON::encode($processedData);

    $valueStore = $this->getKeyValueDatabase();
    $valueStore->set('data', $json);
    $valueStore->set('updatedAt', new \DateTime('now'));

    return $processedData;
  }

  /**
   * Gets UpdatedAt value from keyvalue database.
   *
   * @return mixed
   *   Possible date time or null
   */
  public function getUpdatedAt() {
    $kvStorage = $this->getKeyValueDatabase();
    return $kvStorage->get('updatedAt');
  }

  /**
   * Get key value database.
   */
  private function getKeyValueDatabase(): KeyValueStoreInterface {
    return $this->databaseFactory->get('grants_municipality_data');
  }

}
