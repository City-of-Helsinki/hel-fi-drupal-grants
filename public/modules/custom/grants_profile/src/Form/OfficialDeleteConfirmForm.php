<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Grants Profile form.
 *
 * @phpstan-consistent-constructor
 */
class OfficialDeleteConfirmForm extends FormBase {

  /**
   * Is debug on or off?
   *
   * @var bool
   */
  protected bool $debug;


  /**
   * Renderer for submission details.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  private array $tOpts = ['context' => 'grants_profile'];

  /**
   * Constructs a new ModalOfficialForm object.
   */
  public function __construct() {
    $debug = getenv('DEBUG');

    if ($debug == 'true') {
      $this->debug = TRUE;
    }
    else {
      $this->debug = FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OfficialDeleteConfirmForm|static {

    // Create a new form object and inject its services.
    $form = new static();
    $form->setRequestStack($container->get('request_stack'));
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_profile_official_delete_confirm_form';
  }

  /**
   * Helper method so we can have consistent dialog options.
   *
   * @return string[]
   *   An array of jQuery UI elements to pass on to our dialog form.
   */
  public static function getDataDialogOptions(): array {
    return [
      'width' => '33%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    string $official_id = '',
    string $nojs = '',
  ): array {

    // Add the core AJAX library.
    $form['#attached']['library'][] = 'core/drupal.ajax';

    // Add a link to show this form in a modal dialog if we're not already in
    // one.
    if ($nojs == 'nojs') {
      $form['use_ajax_container'] = [
        '#type' => 'details',
        '#open' => TRUE,
      ];
      $form['use_ajax_container']['use_ajax'] = [
        '#type' => 'link',
        '#title' => $this->t('See this form as a modal.', [], $this->tOpts),
        '#url' => Url::fromRoute('grants_profile.application_official.remove_confirm_modal', [
          'official_id' => $official_id,
          'nojs' => 'ajax',
        ]),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(static::getDataDialogOptions()),
          // Add this id so that we can test this form.
          'id' => 'remove-official-confirm-link',
        ],
      ];
    }

    // This element is responsible for displaying form errors in the AJAX
    // dialog.
    if ($nojs == 'ajax') {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -999,
      ];
    }
    $form['officialId'] = [
      '#type' => 'hidden',
      '#value' => $official_id,
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Official', [], $this->tOpts),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];

    // Set the form to not use AJAX if we're on a nojs path. When this form is
    // within the modal dialog, Drupal will make sure we're using an AJAX path
    // instead of a nojs one.
    if ($nojs == 'nojs') {
      unset($form['actions']['submit']['#ajax']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $official_id = $form_state->getValue('officialId');

    $form_state->setRedirect(
      'grants_profile.application_official.remove',
      [
        'official_id' => $official_id,
      ]
    );
  }

  /**
   * Implements the submit handler for the modal dialog AJAX call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of AJAX commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    // We begin building a new ajax reponse.
    $response = new AjaxResponse();

    // If the user submitted the form and there are errors, show them the
    // input dialog again with error messages. Since the title element is
    // required, the empty string wont't validate and there will be an error.
    if ($form_state->getErrors()) {
      // If there are errors, we can show the form again with the errors in
      // the status_messages section.
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new OpenModalDialogCommand($this->t('Errors', [], $this->tOpts),
        $form, static::getDataDialogOptions()));
    }
    else {
      // No errors, we load things from form state.
      $official_id = $form_state->getValue('officialId');

      // Create url redirect for this new submission.
      $url = Url::fromRoute('grants_profile.application_official.remove',
        [
          'official_id' => $official_id,
        ]);
      $response->addCommand(new CloseModalDialogCommand());
      $command = new RedirectCommand($url->toString());
      $response->addCommand($command);
    }

    // Finally return our response.
    return $response;
  }

}
