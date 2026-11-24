<?php

declare(strict_types=1);

namespace Drupal\grants_application;

namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Base class for rest resources.
 */
class HelfiResourceBase extends ResourceBase {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return static::createInstanceAutowired($container, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Redirect on incomplete profile.
   *
   * Used in Application -rest resource.
   *
   * Build a redirect to the grants profile edit page if the profile is
   * incomplete. Mirrors the old webform behavior in
   * GrantsHandler::prepareForm. Applies uniformly to all applicant types.
   *
   * @param array<string, mixed> $profileContent
   *   The grants profile content array (from GrantsProfile::toArray()).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|null
   *   Redirect response when the profile is incomplete, NULL otherwise.
   */
  protected function profileIncompleteJsonResponse(array $profileContent): ?JsonResponse {
    $tOpts = ['context' => 'grants_handler'];

    if (empty($profileContent)) {
      $this->messenger()->addWarning($this->t('You must have grants profile created.', [], $tOpts));
      return new JsonResponse([
        'error' => $this->t('Your profile is incomplete.', [], $tOpts),
        'redirect_url' => Url::fromRoute('grants_profile.edit', [], ['absolute' => TRUE])->toString(),
      ], 403);
    }

    $needsRedirect = FALSE;
    if (empty($profileContent['addresses'])) {
      $this->messenger()->addWarning($this->t('You must have address saved to your profile.', [], $tOpts));
      $needsRedirect = TRUE;
    }
    if (empty($profileContent['bankAccounts'])) {
      $this->messenger()->addWarning($this->t('You must have bank account saved to your profile.', [], $tOpts));
      $needsRedirect = TRUE;
    }

    if (!$needsRedirect) {
      return NULL;
    }

    return new JsonResponse([
      'error' => $this->t('Your profile is incomplete.', [], $tOpts),
      'redirect_url' => Url::fromRoute('grants_profile.edit', [], ['absolute' => TRUE])->toString(),
    ], 403);
  }

  /**
   * The profile incomplete redirect response.
   *
   * Used in DraftApplication -rest resource.
   *
   * Build a redirect to the grants profile edit page if the profile is
   * incomplete (no profile, or missing addresses / bank accounts).
   *
   * Mirrors the old webform behavior in GrantsHandler::prepareForm. Applies
   * uniformly to all applicant types; warnings stack into the messenger.
   *
   * @param array<string, mixed> $profileContent
   *   The grants profile content array (from GrantsProfile::toArray()).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|null
   *   Redirect response when the profile is incomplete, NULL otherwise.
   */
  protected function profileIncompleteRedirectResponse(array $profileContent): ?JsonResponse {
    $tOpts = ['context' => 'grants_handler'];

    if (empty($profileContent)) {
      $this->messenger()->addWarning($this->t('You must have grants profile created.', [], $tOpts));
      return $this->redirectJsonResponse(
        Url::fromRoute('grants_profile.edit', [], ['absolute' => TRUE]),
        $this->t('Your profile is incomplete.', [], $tOpts),
      );
    }

    $needsRedirect = FALSE;
    if (empty($profileContent['addresses'])) {
      $this->messenger()->addWarning($this->t('You must have address saved to your profile.', [], $tOpts));
      $needsRedirect = TRUE;
    }
    if (empty($profileContent['bankAccounts'])) {
      $this->messenger()->addWarning($this->t('You must have bank account saved to your profile.', [], $tOpts));
      $needsRedirect = TRUE;
    }

    if (!$needsRedirect) {
      return NULL;
    }

    return $this->redirectJsonResponse(
      Url::fromRoute('grants_profile.edit', [], ['absolute' => TRUE]),
      $this->t('Your profile is incomplete.', [], $tOpts),
    );
  }

  /**
   * Build a JSON response carrying an error message and a redirect URL.
   *
   * The frontend's handleErrors detects redirect_url and navigates instead of
   * rendering the error notification.
   */
  protected function redirectJsonResponse(Url $target, string|TranslatableMarkup $errorMessage): JsonResponse {
    return new JsonResponse([
      'error' => $errorMessage,
      'redirect_url' => $target->toString(),
    ], 403);
  }

}
