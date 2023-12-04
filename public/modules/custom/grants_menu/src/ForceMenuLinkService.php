<?php

namespace Drupal\grants_menu;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a ForceMenuLinkService service.
 */
class ForceMenuLinkService {

  use StringTranslationTrait;

  /**
   * The plugin ID of the "Avustukset" (FI and SV) menu item.
   */
  const MENU_PARENT_PLUGIN_ID = 'menu_link_content:cfaa8af9-e0f6-4814-9538-a954f4feb6a2';

  /**
   * The machine name of the menu.
   */
  const MENU_MACHINE_NAME = 'main';

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The MenuLinkManagerInterface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected MenuLinkManagerInterface $menuLinkManager;

  /**
   * The LanguageManagerInterface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The LoggerChannelFactoryInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The MessengerInterface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The MenuLinkManagerInterface.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The LanguageManagerInterface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The LoggerChannelFactoryInterface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The MessengerInterface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuLinkManagerInterface $menuLinkManager, LanguageManagerInterface $languageManager, LoggerChannelFactoryInterface $loggerFactory, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkManager = $menuLinkManager;
    $this->languageManager = $languageManager;
    $this->loggerFactory = $loggerFactory;
    $this->messenger = $messenger;
  }

  /**
   * The forceMenuItem method.
   *
   * This method creates a menu link for a service
   * page node if it does not have one.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node we are working with.
   */
  public function forceMenuLink(NodeInterface $node): void {
    $menuLinks = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $node->id()], self::MENU_MACHINE_NAME);

    if (empty($menuLinks)) {
      $this->createMenuLink($node);
    }
  }

  /**
   * The createMenuLink method.
   *
   * This method creates a menu link for a node and sets it
   * under the correct parent. A translation for the link is also
   * added if the node has a translation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node we are working with.
   */
  protected function createMenuLink(NodeInterface $node): void {
    try {
      $menuLinkStorage = $this->entityTypeManager->getStorage('menu_link_content');
      $translationLanguage = ($node->language()->getId() === 'fi') ? 'sv' : 'fi';

      /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menuLink */
      $menuLink = $menuLinkStorage->create([
        'title' => $node->label(),
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'menu_name' => self::MENU_MACHINE_NAME,
        'weight' => 0,
        'expanded' => FALSE,
        'enabled' => TRUE,
        'parent' => self::MENU_PARENT_PLUGIN_ID,
        'langcode' => $node->language()->getId(),
      ]);

      if ($node->hasTranslation($translationLanguage)) {
        $nodeTranslation = $node->getTranslation($translationLanguage);

        if ($nodeTranslation && $menuLink->isTranslatable()) {
          $menuLink->addTranslation($translationLanguage, [
            'title' => $nodeTranslation->label(),
            'link' => ['uri' => 'entity:node/' . $nodeTranslation->id()],
            'menu_name' => self::MENU_MACHINE_NAME,
            'weight' => 0,
            'expanded' => FALSE,
            'enabled' => TRUE,
            'parent' => self::MENU_PARENT_PLUGIN_ID,
            'langcode' => $translationLanguage,
          ]);
        }
      }

      $menuLink->isDefaultRevision($node->isDefaultRevision());
      $menuLink->save();
      $this->messenger->addStatus($this->t('Menu link created automatically.', [], ['context' => 'grants_menu']));
      $this->logMessage('CREATED', $node, $menuLink);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      $this->logMessage('CREATE_FAILED', $node, $menuLink, $e);
    }
  }

  /**
   * The logMessage method.
   *
   * This method logs a message when a new menu link has been created,
   * or when the operation fails.
   *
   * @param string $messageType
   *   A string determining the message type.
   * @param \Drupal\node\NodeInterface|null $node
   *   The node we are working with or NULL.
   * @param \Drupal\menu_link_content\MenuLinkContentInterface|null $menuLink
   *   The nodes menu link we or NULL.
   * @param mixed $exception
   *   An exception on failure.
   */
  protected function logMessage(string $messageType, NodeInterface $node = NULL, MenuLinkContentInterface $menuLink = NULL, mixed $exception = NULL): void {
    $message = match ($messageType) {
      'CREATED' => $this->t('Created the following menu item:'),
      'CREATE_FAILED' => $this->t('Failed to create the following menu item:'),
    };

    $this->loggerFactory->get('grants_menu')
      ->notice('@message Node ID: @node, Menu link ID: @menu_link, Exception: @exception', [
        '@message'   => $message,
        '@node'      => ($node !== NULL) ? $node->id() : 'NO NODE FOUND.',
        '@menu_link' => ($menuLink !== NULL) ? $menuLink->id() : 'NO MENU LINK FOUND.',
        '@exception' => ($exception !== NULL) ? '<pre><code>' . print_r($exception, TRUE) . '</code></pre>' : 'NO EXCEPTION.',
      ]);
  }

}
