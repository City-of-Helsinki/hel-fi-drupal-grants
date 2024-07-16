<?php

namespace Drupal\grants_handler\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\ApplicationInitService;
use Drupal\grants_handler\DebuggableTrait;
use Drupal\grants_profile\GrantsProfileException;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_helsinki_profiili\ProfileDataException;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Grants Handler form.
 *
 * @phpstan-consistent-constructor
 */
class CopyApplicationForm extends FormBase {

  use DebuggableTrait;

  /**
   * Application handler class.
   *
   * @var \Drupal\grants_handler\ApplicationHandler
   */
  protected ApplicationHandler $applicationHandler;

  /**
   * Application init service.
   *
   * @var \Drupal\grants_handler\ApplicationInitService
   */
  protected ApplicationInitService $applicationInitService;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Application getter service.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * Constructs a new AddressForm object.
   */
  public function __construct(
    ApplicationHandler $applicationHandler,
    ApplicationInitService $applicationInitService,
    LoggerChannelInterface $logger,
    ApplicationGetterService $applicationGetterService
  ) {
    $this->setDebug(NULL);

    $this->applicationHandler = $applicationHandler;
    $this->applicationInitService = $applicationInitService;
    $this->logger = $logger;
    $this->applicationGetterService = $applicationGetterService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MessageForm|static {
    return new static(
      $container->get('grants_handler.application_handler'),
      $container->get('grants_handler.application_init_service'),
      $container->get('logger.factory')->get('copy_application_form'),
      $container->get('grants_handler.application_getter_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_handler_copy_application';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $submission_id = ''): array {
    $tOpts = ['context' => 'grants_handler'];

    try {
      $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);

      if ($webform_submission != NULL) {
        $form_state->setStorage(['submission' => $webform_submission]);
      }
    }
    catch (\Exception | GuzzleException $e) {
      $this->getLogger('copy_application_form')->error('Failed to load submission: @error', ['@error' => $e->getMessage()]);
    }
    $form['copyFrom'] = [
      '#type' => 'markup',
      '#markup' => 'Tähän vois sitte laittaa hakemuksen perussettejä, tai vaikka koko hakemus näytille.',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Copy application', [], $tOpts),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $storage = $form_state->getStorage();
    /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $webform_submission = $storage['submission'];
    $webform = $webform_submission->getWebForm();

    // Init new application with copied data.
    try {
      $newSubmission = $this->applicationInitService->initApplication($webform->id(), $webform_submission->getData());
    }
    catch (EntityStorageException |
    GrantsProfileException |
    AtvDocumentNotFoundException |
    AtvFailedToConnectException |
    ProfileDataException |
    TokenExpiredException |
    GuzzleException $e) {
      $newSubmission = FALSE;
    }

    if ($newSubmission) {

      $newData = $newSubmission->getData();

      $this->messenger()
        ->addStatus(
          $this->t(
            'Grant application copied(<span id="saved-application-number">@number</span>)',
            [
              '@number' => $newData['application_number'],
            ]
          )
        );

      $form_state->setRedirect(
        'grants_handler.completion',
        ['submission_id' => $newData['application_number']],
        [
          'attributes' => [
            'data-drupal-selector' => 'application-saved-successfully-link',
          ],
        ]
      );
    }
    else {
      $this->messenger()->addError('Grant application copy failed.');
    }
  }

}
