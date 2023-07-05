<?php

namespace Drupal\grants_menu;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for managing node menu items.
 */
class ForceMenuItemService {

  /**
   * The plugin ID of the "Avustukset" (FI) menu item.
   */
  const MENU_PARENT_PLUGIN_ID_FI = 'menu_link_content:cfaa8af9-e0f6-4814-9538-a954f4feb6a2';

  /**
   * The plugin ID of the "Understöden" (SV) menu item.
   */
  const MENU_PARENT_PLUGIN_ID_SV = 'menu_link_content:da1386f7-6b42-4409-bfba-4f2abccffb53';

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
   * Constructs a MenuService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The MenuLinkManagerInterface.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The LanguageManagerInterface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The LoggerChannelFactoryInterface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuLinkManagerInterface $menuLinkManager, LanguageManagerInterface $languageManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkManager = $menuLinkManager;
    $this->languageManager = $languageManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * The forceMenuItem method.
   *
   * This method either creates a menu entry for a node
   * if it does not have one, or updates the current menu
   * entry if it is under an incorrect parent.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   */
  public function forceMenuItem(NodeInterface $node): void {
    $menuItems = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $node->id()], self::MENU_MACHINE_NAME);

    if (empty($menuItems)) {
      $this->createMenuItem($node);
      return;
    }

    foreach ($menuItems as $menuItem) {
      $menuItemPluginId = $menuItem->getPluginId();
      $menuParents = $this->menuLinkManager->getParentIds($menuItemPluginId);
      $menuParentPluginIds = array_keys($menuParents);
      $correctMenuParentPluginId = $this->getCorrectMenuParentByLanguage();

      if (!in_array($correctMenuParentPluginId, $menuParentPluginIds)) {
        $this->updateMenuItem($node, $menuItem);
      }
    }
  }

  /**
   * The createMenuItem method.
   *
   * This method creates menu items.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   */
  protected function createMenuItem(NodeInterface $node): void {
    try {
      dump('CREATING');
      $menuLinkStorage = $this->entityTypeManager->getStorage('menu_link_content');
      $menuLink = $menuLinkStorage->create([
        'title' => $node->label(),
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'menu_name' => self::MENU_MACHINE_NAME,
        'weight' => 0,
        'expanded' => FALSE,
        'enabled' => TRUE,
        'parent' => $this->getCorrectMenuParentByLanguage(),
        'langcode' => $node->language()->getId(),
      ]);
      $menuLink->save();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      dump($e);
    }
  }

  /**
   * The updateMenuItem method.
   *
   * This method updates a menu items.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Menu\MenuLinkInterface $menuItem
   *   A menu item.
   */
  protected function updateMenuItem(NodeInterface $node, MenuLinkInterface $menuItem): void {
    try {
      dump('UPDATING');
      $menuItem->updateLink([
        'link' => ['uri' => 'entity:node/' . $node->id()],
        'menu_name' => self::MENU_MACHINE_NAME,
        'weight' => 0,
        'expanded' => FALSE,
        'enabled' => TRUE,
        'parent' => $this->getCorrectMenuParentByLanguage(),
        'langcode' => $node->language()->getId(),
      ], TRUE);
    }
    catch (\Exception $e) {
      dump($e);
    }
  }

  /**
   * The getCorrectMenuParentByLanguage method.
   *
   * This method returns a menu parent plugin ID. The returned
   * value is based on the currently activated language.
   *
   * @return string
   *   Return the menu parent plugin ID based on language.
   */
  protected function getCorrectMenuParentByLanguage(): string {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    return ($language === 'fi') ? self::MENU_PARENT_PLUGIN_ID_FI : self::MENU_PARENT_PLUGIN_ID_SV;
  }

  protected function logMessage(string $type): void {

  }

}
