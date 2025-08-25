<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQLCompose\EntityType;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeEntityTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
/**
 * @GraphQLComposeEntityType(
 *   id = "coupon",
 *   base_fields = {
 *     "code" = {},
 *     "title" = {},
 *     "description" = {
 *       "required" = false,
 *     },
 *     "category" = {},
 *     "company" = {},
 *     "valid_from" = {
 *       "field_type" = "valid_from_string_field",
 *     },
 *     "valid_to" = {
 *       "field_type" = "valid_to_string_field",
 *     },
 *     "status" = {},
 *     "discount_value" = {},
 *     "media_image" = {
 *       "field_type" = "media_image_field",
 *       "required" = false,
 *     },
 *     "group_id" = {
 *       "required" = false,
 *     },
 *   },
 * )
 */
class Coupon extends GraphQLComposeEntityTypeBase {
  // ：registerTypes、registerResolvers
  /**
   * {@inheritdoc}
   */
  public function registerTypes(): void {
    parent::registerTypes();

    $this->registerCouponQueries();
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    parent::registerResolvers($registry, $builder);
  }
  protected function registerCouponQueries(): void {
    $mutation_type = new ObjectType([
      'name' => 'Query',
      'fields' => fn() => [
        'couponsByCategory' => [
          'type' => $this->gqlSchemaTypeManager->get('CouponsByCategory'),
          'description' => (string) $this->t('Add a Coupon.'),
          'args' => [
            'category' => [
              'type' => Type::string(),
              'description' => (string) $this->t('Category term ID or name to filter by'),
            ],
            'limit' => [
              'type' => Type::int(),
              'defaultValue' => 10,
              'description' => (string) $this->t('Number of results to return'),
            ],
            'offset' => [
              'type' => Type::int(),
              'defaultValue' => 0,
              'description' => (string) $this->t('Starting offset for results'),
            ],
          ],
        ],
      ],
    ]);

    $this->gqlSchemaTypeManager->extend($mutation_type);
  }

  protected function resolveCouponsByCategory(array $args): array {
    $storage = \Drupal::entityTypeManager()->getStorage('coupon');
    
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1);
    if (!empty($args['category'])) {
      $this->applyCategoryFilter($query, $args['category']);
    }

    $limit = $args['limit'] ?? 10;
    $offset = $args['offset'] ?? 0;
    $query->range($offset, $limit);

    $query->sort('created', 'DESC');

    $coupon_ids = $query->execute();

    return $coupon_ids ? $storage->loadMultiple($coupon_ids) : [];
  }

  protected function applyCategoryFilter($query, $category): void {
    \Drupal::logger('Coupon')->info('category: ' . $category);
    if (is_numeric($category)) {
      $query->condition('category.target_id', $category);
      return;
    }

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $category]);

    if (!empty($terms)) {
      $term_ids = array_keys($terms);
      $query->condition('field_category.target_id', $term_ids, 'IN');
    }
  }
}
