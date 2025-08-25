<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;

/**
 * @GraphQLComposeSchemaType(
 *   id = "MediaImage",
 * )
 */
class CouponMediaImage extends GraphQLComposeSchemaTypeBase {
  public function getTypes(): array {
    $types = [];

    return $types;
  }
}
