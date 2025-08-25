<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_coupons\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\DataProducerPluginCachingInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\coupon_manager\Entity\Coupon;
use Drupal\media\MediaInterface;

/**
 * Returns the media image for a coupon.
 *
 * @DataProducer(
 *   id = "coupon_media_image",
 *   name = @Translation("Coupon media image"),
 *   description = @Translation("Returns the media image entity for a coupon."),
 *   produces = @ContextDefinition("entity:media",
 *     label = @Translation("Media image"),
 *   ),
 *   consumes = {
 *     "coupon" = @ContextDefinition("entity:coupon",
 *       label = @Translation("Coupon"),
 *     ),
 *   },
 * )
 */
class CouponMediaImage extends DataProducerPluginBase implements DataProducerPluginCachingInterface {

  /**
   * Resolves the value for this data producer.
   *
   * @param \Drupal\coupon_manager\Entity\Coupon $coupon
   *   The coupon entity.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The media image entity or null.
   */
  public function resolve(Coupon $coupon): ?MediaInterface {
    if ($coupon->hasField('media_image') && !$coupon->get('media_image')->isEmpty()) {
      return $coupon->get('media_image')->entity;
    }
    return NULL;
  }

}
