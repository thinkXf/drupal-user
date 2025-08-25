<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\webform\Entity\WebformSubmission as WebformSubmissionEntity;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\group\GroupMembershipLoaderInterface as GroupMembershipLoader;

/**
 * coupons by category.
 *
 * @DataProducer(
 *   id = "coupons_by_category",
 *   name = @Translation("Query coupons by category"),
 *   description = @Translation("Query coupons filtered by category."),
 *   produces = @ContextDefinition("entity:coupon",
 *     label = @Translation("Coupons"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "category" = @ContextDefinition("integer",
 *       label = @Translation("Category"),
 *       required = FALSE,
 *       default_value = null
 *     ),
 *     "limit" = @ContextDefinition("integer",
 *       label = @Translation("Limit"),
 *       required = FALSE,
 *       default_value = 10
 *     ),
 *     "offset" = @ContextDefinition("integer",
 *       label = @Translation("Offset"),
 *       required = FALSE,
 *       default_value = 0
 *     )
 *   }
 * )
 */
class CouponsByCategory extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;
  protected $groupMembershipLoader;

  public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityTypeManagerInterface $entityTypeManager, GroupMembershipLoader $groupMembershipLoader) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->groupMembershipLoader = $groupMembershipLoader;
  }

  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('group.membership_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(?string $category = null, int $limit = 10, int $offset = 0) {
    $storage = $this->entityTypeManager->getStorage('coupon');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1);

    $current_user = \Drupal::currentUser();
    $group_memberships = $this->groupMembershipLoader->loadByUser($current_user);
    $user_group_ids = array_map(function($membership) {
        return $membership->getGroup()->id();
    }, $group_memberships);
    \Drupal::logger('Coupon')->info('userids: ' . json_encode($user_group_ids));
    if (!empty($user_group_ids)) {
      $query->condition('group_id', $user_group_ids, 'IN');
    }

    if ($category !== null) {
      $this->applyCategoryFilter($query, $category);
    }

    $limit = $limit ?? 10;
    $offset = $offset ?? 0;
    $query->range($offset, $limit);

    $query->sort('created', 'DESC');

    $coupon_ids = $query->execute();
    \Drupal::logger('Coupon')->info('ids: ' . json_encode($coupon_ids));
    return $coupon_ids ? [
      'coupon' => $storage->loadMultiple($coupon_ids),
    ] : [];
  }

  protected function applyCategoryFilter($query, $category): void {
    if (is_numeric($category)) {
      $query->condition('category.target_id', $category);
      return;
    }

    // $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
    //   ->loadByProperties(['name' => $category]);
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['uuid' => $category]);
    if (!empty($term)) {
      $term = reset($term);
      $query->condition('category.target_id', $term->id());
    }
  }
}
