<?php

namespace Drupal\graphql_compose_coupons\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql_compose\Plugin\GraphQL\SchemaExtension\ResolverOnlySchemaExtensionPluginBase;

/**
 * Add coupon to the Schema.
 *
 * @SchemaExtension(
 *   id = "coupon_extension",
 *   name = "GraphQL Compose Coupon",
 *   description = @Translation("Add coupon to the Schema."),
 *   schema = "graphql_compose",
 * )
 */
class CouponsSchemaExtension extends ResolverOnlySchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Query', 'couponsByCategory',
      $builder->produce('coupons_by_category')
        ->map('category', $builder->fromArgument('category'))
        ->map('limit', $builder->fromArgument('limit'))
        ->map('offset', $builder->fromArgument('offset'))
    );
  }
}
