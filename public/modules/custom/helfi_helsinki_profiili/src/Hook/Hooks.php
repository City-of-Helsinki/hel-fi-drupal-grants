<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\ProfiiliException;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helsinki Profiili hooks.
 */
final class Hooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly HelsinkiProfiiliUserData $userData,
    private readonly MessengerInterface $messenger,
    #[Autowire(service: 'logger.channel.helfi_helsinki_profiili')]
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * OpenID Connect pre-authorize hook.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account identified using the "sub" provided by the identity
   *   provider, or FALSE, if no such account exists.
   * @param array<mixed> $context
   *   An associative array with context information:
   *   - tokens:         An array of tokens.
   *   - user_data:      An array of user and session data.*
   *   - plugin_id:      The plugin identifier.
   *   - sub:            The remote user identifier.
   */
  #[Hook('openid_connect_pre_authorize')]
  public function preAuthorize(UserInterface|bool $account, array $context): bool {
    // Don't do anything for entra users.
    if ($context['plugin_id'] !== 'tunnistamo') {
      return TRUE;
    }

    // After authorization, validate JWT payload. The payload is used
    // _everwhere_ in the application, so if we fail here, we should
    // logout the user with a clear error message.
    //
    // The JWT payload is parsed by HelsinkiProfiiliUserData::getUserData,
    // but the session is not yet set up in pre authorize hook.
    if (empty($context['user_data']['sub'])) {
      $this->logger->error('Malformed JWT payload @jwt', [
        '@jwt' => json_encode(array_diff_key($context['user_data'], array_flip([
          'name', 'email', 'given_name', 'family_name',
        ]))),
      ]);

      $this->requestStack
        ->getSession()
        ->set('openid_connect_destination', '<front>');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * OpenID Connect post authorize hook.
   *
   * This hook runs after a user has been authorized and claims have been mapped
   * to the user's account.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account object of the authorized user.
   * @param array<mixed> $context
   *   An associative array with context information:
   *   - tokens:         An array of tokens.
   *   - user_data:      An array of user and session data.*
   *   - plugin_id:      The plugin identifier.
   *   - sub:            The remote user identifier.
   */
  #[Hook('openid_connect_post_authorize')]
  public function postAuthorize(UserInterface $account, array $context): void {
    $session = $this->requestStack->getSession();
    $session->set('openid_connect_plugin_id', $context["plugin_id"]);

    if (isset($context['tokens']['refresh_token'])) {
      $session->set('openid_connect_refresh_token', $context['tokens']['refresh_token']);
      $session->set('openid_connect_expire', $context['tokens']['expire']);
    }

    // Don't do anything for entra users.
    if (!empty($context["user_data"]["ad_groups"])) {
      return;
    }

    try {
      // Verify that we are able to read profile
      // data. Log an error if this fails.
      $data = $this->userData->getUserProfileData();

      if ($data == NULL) {
        $this->messenger->addWarning($this->t('User logged in to Helsinki services, no profile data found.'));
      }
      else {
        $this->messenger->addStatus($this->t('User logged in to Helsinki services and data fetched.'));
      }

    }
    catch (TokenExpiredException $e) {
      Error::logException($this->logger, $e, 'API token fetch failed ' . Error::DEFAULT_ERROR_MESSAGE);
      $this->messenger->addError($this->t('User logged in but fetching tokens failed'));
    }
    catch (ProfiiliException $e) {
      Error::logException($this->logger, $e, 'User profile data fetch failed ' . Error::DEFAULT_ERROR_MESSAGE);
      $this->messenger->addError($this->t('User logged in to Helsinki services and data fetch failed'));
    }
  }

}
