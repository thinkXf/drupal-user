<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Drupal\graphql\GraphQL\ResolverBuilder;
/**
 * {@inheritDoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "CouponsByCategory"
 * )
 */
class CouponsByCategory extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritDoc}
   */
  public function getTypes(): array {
    $types = [];

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A Coupon Category.'),
      'fields' => fn() => [
        'coupon' => [
          'type' => Type::listOf(static::type('Coupon')),
          'description' => (string) $this->t('List of coupons in this category.'),
        ],
      ],
    ]);

    return $types;
  }

}
