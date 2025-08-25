<?php

namespace Drupal\navigation_extra_tools\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Provide hooks for navigation extra tools.
 */
class NavigationExtraToolsHooks {

  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension) {
    if ($extension === 'navigation_extra_tools' && isset($libraries['icon'])) {
      if ($this->moduleHandler->moduleExists('toolbar')) {
        $libraries['icon']['dependencies'][] = 'toolbar/drupal.toolbar';
      }
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page) {
    if ($this->currentUser->hasPermission('access navigation')) {
      $page['#attached']['library'][] = 'navigation_extra_tools/icon';
    }
  }

}
