<?php

namespace Drupal\graphql_compose_coupons\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * @DataProducer(
 *   id = "format_date",
 *   name = @Translation("Format date"),
 *   description = @Translation("Format a timestamp as a date string."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Date string"),
 *   ),
 *   consumes = {
 *     "timestamp" = @ContextDefinition("integer",
 *       label = @Translation("Timestamp"),
 *     ),
 *     "format" = @ContextDefinition("string",
 *       label = @Translation("Format"),
 *       required = FALSE,
 *     ),
 *   },
 * )
 */
class FormatDate extends DataProducerPluginBase {
  public function resolve($timestamp, $format = 'Y-m-d H:i:s') {
    return $timestamp ? date($format, $timestamp) : null;
  }
}