<?php

declare(strict_types=1);

namespace Drupal\helfi_av;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Helfi antivirus service.
 */
class AntivirusService {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly ClientInterface $client,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Scan given blob.
   *
   * @param array $files
   *   File to check.
   *
   * @throws AntivirusException
   *   If the antivirus check fails.
   */
  public function scan(array $files): bool {
    $baseUrl = $this->configFactory->get('helfi_av.settings')->get('base_url');

    $multipart = [];

    foreach ($files as $key => $file) {
      $multipart[] = [
        'name' => 'FILES',
        'contents' => $file,
        'filename' => is_string($key) ? basename($key) : hash('sha256', $file),
      ];
    }

    try {
      $response = $this->client->request('POST', "$baseUrl/api/v1/scan", [
        'multipart' => $multipart,
      ]);

      $response = json_decode($response->getBody()->getContents(), TRUE);

      if (empty($response['success'])) {
        throw new AntivirusException("Scan failed");
      }

      foreach ($response['data']['result'] as $result) {
        if ($result['is_infected']) {
          throw new AntivirusException("Infected file {$result['name']} found");
        }
      }

      return TRUE;
    }
    catch (GuzzleException $e) {
      throw new AntivirusException($e->getMessage(), previous: $e);
    }
  }

}
