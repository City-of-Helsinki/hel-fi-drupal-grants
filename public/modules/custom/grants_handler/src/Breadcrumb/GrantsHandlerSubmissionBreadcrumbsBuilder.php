<?php

declare(strict_types=1);

namespace Drupal\grants_handler\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;

/**
 * Create breadcrumbs for sumbission pages.
 */
class GrantsHandlerSubmissionBreadcrumbsBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata;

  /**
   * Grants profile access.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * {@inheritdoc}
   */
  public function __construct(HelsinkiProfiiliUserData $helsinkiProfiiliUserData, GrantsProfileService $grantsProfileService) {
    $this->helfiHelsinkiProfiiliUserdata = $helsinkiProfiiliUserData;
    $this->grantsProfileService = $grantsProfileService;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    $parameters = $attributes->getParameters()->all();

    if (
      isset($parameters['submission_id'])
    ) {
      return TRUE;
    }

    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $applicationNumber = $route_match->getParameters()->get('submission_id');
    $selectedCompany = $this->grantsProfileService->getSelectedCompany();

    $breadcrumb->addLink(Link::createFromRoute($this->t('Front page'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($selectedCompany['name'], 'grants_oma_asiointi.front'));
    $breadcrumb->addLink(Link::createFromRoute($applicationNumber, 'grants_handler.view_application', ['submission_id' => $applicationNumber]));

    // Don't forget to add cache control,otherwise you will surprised,
    // all breadcrumb will be the same for all pages.
    // By setting a "cache context" to the "url", each requested URL gets it's
    // own cache. This way a single breadcrumb isn't cached for all pages on the
    // site.
    $breadcrumb->addCacheContexts(['url.path']);

    // By adding "cache tags" for this specific node, the cache is invalidated
    // when the node is edited.
    $breadcrumb->addCacheTags(["applicationNumber:{$applicationNumber}"]);

    return $breadcrumb;
  }

}
