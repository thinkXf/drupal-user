<?php

namespace Drupal\graphql_compose_coupons\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose_webform\Plugin\GraphQLCompose\SchemaType\WebformElement as BaseWebformElement;
use GraphQL\Type\Definition\Type;
/**
 * @GraphQLComposeSchemaType(
 *   id = "WebformElement",
 *   type_sdl = "WebformElement"
 * )
 */
class WebformElement extends BaseWebformElement {
    public function getTypes(): array {
        \Drupal::logger('graphql_coupons')->debug('WebformElement::getTypes() executed');
        $types = parent::getTypes();
        
        $types[0] = new \GraphQL\Type\Definition\ObjectType([
            'name' => $this->getPluginId(),
            'fields' => function() {
                $fields = $this->getFieldDefinitions();
                return array_merge($fields, [
                    'minlength' => [
                        'type' => Type::int(),
                        'resolve' => function($value) {
                            return $value['#minlength'] ?? NULL;
                        }
                    ],
                    'maxlength' => [
                        'type' => Type::int(),
                        'resolve' => function($value) {
                            return $value['#maxlength'] ?? NULL;
                        }
                    ]
                ]);
            },
        ]);
    
        return $types;
    }
    
    protected function getFieldDefinitions(): array {
        $plugin = \Drupal::service('plugin.manager.graphql_compose_schema_type')
            ->getInstance(['id' => 'WebformElement']);
        return $plugin->getTypes()[0]->getFields();
    }
}