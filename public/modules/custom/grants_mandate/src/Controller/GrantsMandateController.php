<?php

namespace Drupal\grants_mandate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\grants_handler\Helpers;
use Drupal\grants_mandate\GrantsMandateRedirectService;
use Drupal\grants_mandate\GrantsMandateService;
use Drupal\grants_profile\GrantsProfileService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for grants_mandate routes.
 *
 * @phpstan-consistent-constructor
 */
class GrantsMandateController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Allowed roles.
   *
   * @var array
   */
  protected array $allowedRoles;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Grants Mandate Controller constructor.
   */
  public function __construct(
    private RequestStack $requestStack,
    private GrantsMandateService $grantsMandateService,
    private GrantsProfileService $grantsProfileService,
    private GrantsMandateRedirectService $redirectService,
  ) {
    $this->logger = $this->getLogger('grants_mandate');
    $config = $this->configFactory->get('grants_mandate.settings');
    $extraRoles = is_array($config->get('extra_access_roles')) ? $config->get('extra_access_roles') : [];
    $this->allowedRoles = [
      'http://valtuusrekisteri.suomi.fi/avustushakemuksen_tekeminen',
      'PJ',
      'J',
      ...$extraRoles,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsMandateController|static {
    $controller = new static(
      $container->get('request_stack'),
      $container->get('grants_mandate.service'),
      $container->get('grants_profile.service'),
      $container->get('grants_mandate_redirect.service'),
    );
    return $controller->setLoggerFactory($container->get('logger.factory'));
  }

  /**
   * Callback for YPA service in DVV valtuutuspalvelu.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to profile page in case of success. Return
   *   to mandate login page in case of error.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\grants_mandate\GrantsMandateException
   */
  public function mandateCallbackYpa(): RedirectResponse {
    $tOpts = ['context' => 'grants_mandate'];
    $code = $this->requestStack->getMainRequest()->query->get('code');

    $callbackUrl = Url::fromRoute('grants_mandate.callback_ypa', [], ['absolute' => TRUE])
      ->toString();

    $appEnv = Helpers::getAppEnv();

    // @todo What is the meaning and purpose of the "code" (returned by api).
    if (!is_string($code) || !$code) {
      return $this->handleNoCode($tOpts);
    }

    $this->grantsMandateService->changeCodeToToken($code, $callbackUrl);
    $roles = $this->grantsMandateService->getRoles();
    $isAllowed = FALSE;
    if ($roles && isset($roles[0]) && $roles[0]['roles']) {
      $rolesArray = $roles[0]['roles'];
      $isAllowed = $this->hasAllowedRole($rolesArray);
    }
    if (!$isAllowed && !str_contains($appEnv, 'LOCAL')) {
      $this->messenger()->addError($this->t('Your mandate does not allow you to use this service.', [], $tOpts));
      // Redirect user to grants profile page.
      $redirectUrl = Url::fromRoute('grants_mandate.mandateform');
      return new RedirectResponse($redirectUrl->toString());
    }

    $roles = reset($roles);
    $roles['type'] = 'registered_community';
    $this->grantsProfileService->setSelectedRoleData($roles);

    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();

    // Load grants profile.
    $grantsProfile = $this->grantsProfileService->getGrantsProfile($selectedRoleData, TRUE);

    // Redirect user based on if the user has a profile.
    $redirectUrl = $grantsProfile ? Url::fromRoute('grants_oma_asiointi.front') : Url::fromRoute('grants_profile.edit');
    $defaultRedirect = new RedirectResponse($redirectUrl->toString());

    return $this->redirectService->getRedirect($defaultRedirect);
  }

  /**
   * Api did not return a valid code.
   *
   * @param array $tOpts
   *   The options.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect in case of bad code.
   */
  private function handleNoCode(array $tOpts): RedirectResponse {
    $state = $this->requestStack->getMainRequest()->query->get('state');

    $error = $this->requestStack->getMainRequest()->query->get('error');
    $error_description = $this->requestStack->getMainRequest()->query->get('error_description');
    $error_uri = $this->requestStack->getMainRequest()->query->get('error_uri');

    $msg = $this->t('Code exchange error. @error: @error_desc. State: @state, Error URI: @error_uri',
      [
        '@error' => $error,
        '@error_description' => $error_description,
        '@state' => $state,
        '@error_uri' => $error_uri,
      ], $tOpts);

    $this->logger->error('Error: %error', ['%error' => $msg->render()]);

    $this->messenger()->addError($this->t('Mandate process was interrupted or there was an error. Please try again.', [], $tOpts));
    // Redirect user to grants profile page.
    $redirectUrl = Url::fromRoute('grants_mandate.mandateform');
    return new RedirectResponse($redirectUrl->toString());
  }

  /**
   * Check if user has required role in their mandate.
   *
   * @param array $roles
   *   Array of user's roles.
   *
   * @return bool
   *   Is user allowed to use this mandate.
   */
  protected function hasAllowedRole(array $roles) {
    $allowedRoles = $this->allowedRoles;
    foreach ($roles as $role) {
      if (in_array($role, $allowedRoles)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Callback for user mandates.
   */
  public function mandateCallbackHpa() {
    throw new \Exception('Called a function which has no implementation');
  }

  /**
   * Callback for hpa listing.
   */
  public function mandateCallbackHpaList() {
    throw new \Exception('Called a function which has no implementation');
  }

}
