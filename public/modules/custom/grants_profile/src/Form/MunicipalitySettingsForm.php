<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_profile\MunicipalityService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Configure municipality config form.
 *
 * @phpstan-consistent-constructor
 */
class MunicipalitySettingsForm extends ConfigFormBase {

  use AutowireTrait;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'grants_profile.municipality_settings';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    #[Autowire('grants_profile.municipality_service')]
    private MunicipalityService $municipalityService,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_profile_municipality_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $data = $this->municipalityService->getMunicipalityData();
    $updatedAt = $this->municipalityService->getUpdatedAt();

    $endpoint = $this->municipalityService->getEndpoint();

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint for a manual refresh'),
      '#default_value' => $endpoint,
    ];

    $form['refresh_data'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh data'),
      '#submit' => [[$this, 'refreshData']],
    ];

    $form['last_updated'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Last updated'),
      '#default_value' => $updatedAt?->format('Y-m-d H:i:s') ?? '-',
    ];

    $form['items_count'] = [
      '#type' => 'textfield',
      '#value' => count($data),
      '#title' => $this->t('Items'),
      '#disabled' => TRUE,
    ];

    $form['data'] = [
      '#type' => 'textarea',
      '#value' => $this->formatData($data),
      '#title' => 'data',
      '#disabled' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit handler to update municipality data.
   */
  public function refreshData(array &$form, FormStateInterface $form_state) {
    try {
      $endpointUrl = $form_state->getValue('endpoint');
      $result = $this->municipalityService->retrieveDataFromEndpoint($endpointUrl);

      $this->messenger()->addStatus(
        $this->t(
          'Updated municipality data. Item count: @count',
          ['@count' => count($result)]
        )
      );
    }
    catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('Failed to update municipality data. Error: @message', ['@message' => $e->getMessage()]));
    }
  }

  /**
   * Formats the municipality data.
   *
   * @param mixed $data
   *   Data from the service.
   *
   * @return string
   *   Result string.
   */
  public function formatData($data): string {
    $retVal = '';
    foreach ($data as $key => $val) {
      $retVal .= $key . ' ' . $val . PHP_EOL;
    }
    return $retVal;
  }

}
