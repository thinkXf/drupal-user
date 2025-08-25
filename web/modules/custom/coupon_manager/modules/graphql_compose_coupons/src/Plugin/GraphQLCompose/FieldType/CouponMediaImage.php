<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * @GraphQLComposeFieldType(
 *   id = "media_image_field",
 *   type_sdl = "MediaImage",
 * )
 */
class CouponMediaImage extends GraphQLComposeFieldTypeBase {
  public function getProducers(ResolverBuilder $builder): Composite {
    return $builder->compose(
      $builder->produce('coupon_media_image')
        ->map('coupon', $builder->fromParent())
    );
  }
}
