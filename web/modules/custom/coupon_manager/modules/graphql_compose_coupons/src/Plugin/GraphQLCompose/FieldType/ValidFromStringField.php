<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQLCompose\FieldType;

use Drupal\graphql\GraphQL\Resolver\Composite;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * @GraphQLComposeFieldType(
 *   id = "valid_from_string_field",
 *   type_sdl = "String",
 * )
 */
class ValidFromStringField extends GraphQLComposeFieldTypeBase {
    public function getProducers(ResolverBuilder $builder): Composite {
        return $builder->compose(
            $builder->callback(function ($param) {
                if (is_object($param) && method_exists($param, 'get')) {
                    $timestamp = $param->get('valid_from')->value ?? null;
                } else {
                    $timestamp = $param;
                }
                return $timestamp ? date('Y-m-d H:i:s', (int) $timestamp) : null;
            }, [$builder->fromParent()])
        );
    }
}