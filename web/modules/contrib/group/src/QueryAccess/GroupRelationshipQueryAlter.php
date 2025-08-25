<?php

namespace Drupal\group\QueryAccess;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for altering group queries.
 *
 * @todo Revisit cacheability and see if we can optimize some more.
 *
 * @internal
 */
class GroupRelationshipQueryAlter extends PluginBasedQueryAlterBase {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('group_relation_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doAlter($operation) {
    $installed_ids = $this->pluginManager->getAllInstalledIds();

    // Check if any of the plugins actually support the operation. If not, we
    // can simply bail out here to play nice with other modules that do support
    // the provided operation.
    $operation_is_supported = FALSE;
    foreach ($installed_ids as $plugin_id) {
      if ($this->pluginManager->getAccessControlHandler($plugin_id)->supportsOperation($operation, 'relationship')) {
        $operation_is_supported = TRUE;
        break;
      }
    }

    if (!$operation_is_supported) {
      return;
    }

    // If any new relationship is added, it might change access.
    $this->cacheableMetadata->addCacheTags(['group_relationship_list']);

    // Retrieve the full list of group permissions for the user.
    $this->cacheableMetadata->addCacheContexts(['user.group_permissions']);
    $calculated_permissions = $this->permissionCalculator->calculateFullPermissions($this->currentUser);

    $allowed_any_ids = $allowed_own_ids = [];
    foreach ($installed_ids as $plugin_id) {
      $handler = $this->pluginManager->getPermissionProvider($plugin_id);
      $admin_permission = $handler->getAdminPermission();
      $any_permission = $handler->getPermission($operation, 'relationship', 'any');
      $own_permission = $handler->getPermission($operation, 'relationship', 'own');

      foreach ($calculated_permissions->getItems() as $item) {
        if ($admin_permission !== FALSE && $item->hasPermission($admin_permission)) {
          $allowed_any_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
        elseif ($any_permission !== FALSE && $item->hasPermission($any_permission)) {
          $allowed_any_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
        elseif ($own_permission !== FALSE && $item->hasPermission($own_permission)) {
          $allowed_own_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
      }
    }

    // If no group type or group gave access, we deny access altogether.
    if (empty($allowed_any_ids) && empty($allowed_own_ids)) {
      $this->query->alwaysFalse();
      return;
    }

    // If we only have any IDs or own IDs, we can simply add those conditions
    // in their dedicated section below. However, if we have both, we need to
    // add both sections to an OR group to avoid two contradicting access checks
    // to cancel each other out, leading to no results.
    $condition_attacher = $this->query;
    if (!empty($allowed_any_ids) && !empty($allowed_own_ids)) {
      $condition_attacher = $this->ensureOrConjunction($this->query);
    }

    if (!empty($allowed_any_ids)) {
      $this->addScopedConditions($allowed_any_ids, $condition_attacher);
    }

    if (!empty($allowed_own_ids)) {
      $this->cacheableMetadata->addCacheContexts(['user']);
      $data_table = $this->ensureDataTable();

      $condition_attacher->condition($owner_conditions = $this->query->andConditionGroup());
      $owner_conditions->condition("$data_table.uid", $this->currentUser->id());
      $this->addScopedConditions($allowed_own_ids, $owner_conditions);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginDataTable() {
    return $this->ensureDataTable();
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return $this->ensureDataTable();
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinLeftField() {
    return 'gid';
  }

}
