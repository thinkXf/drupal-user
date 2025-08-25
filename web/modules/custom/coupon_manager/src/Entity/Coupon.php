<?php

namespace Drupal\coupon_manager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Coupon entity.
 *
 * @ContentEntityType(
 *   id = "coupon",
 *   label = @Translation("Coupon"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\coupon_manager\Form\CouponForm",
 *       "edit" = "Drupal\coupon_manager\Form\CouponForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     }
 *   },
 *   base_table = "coupon",
 *   admin_permission = "administer coupon entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "code",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/coupons/{coupon}",
 *     "add-form" = "/admin/content/coupons/add",
 *     "edit-form" = "/admin/content/coupons/{coupon}/edit",
 *     "delete-form" = "/admin/content/coupons/{coupon}/delete",
 *     "collection" = "/admin/content/coupons",
 *     "add-page" = "/admin/content/coupons/add"
 *   }
 * )
 */
class Coupon extends ContentEntityBase implements EntityPublishedInterface {
  use EntityOwnerTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('クーポンコード'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 32]);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('タイトル'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128]);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('説明'))
      ->setRequired(FALSE)
      ->setSettings(['max_length' => 255]);

    $fields['media_image'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('画像'))
      ->setDescription(t('画像.'))
      ->setSetting('target_type', 'media') // 目标类型：media
      ->setSetting('handler', 'default')  // 使用默认引用处理器
      ->setSetting('handler_settings', [
        'target_bundles' => ['image'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget', // 表单使用自动完成
        'weight' => 10,
        'settings' => [
          'media_types' => ['image'],
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view', // 视图显示引用的媒体
        'weight' => 10,
        'settings' => [
          'view_mode' => 'thumbnail'
        ],
      ])
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('分類'))
      ->setDescription(t('円以上で割引 / 円引'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['kuponfen_lei'],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 20,
        'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'autocomplete_type' => 'tags',
        'placeholder' => '',
      ],
    ]);
    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('会社'))
      ->setDescription(t('会社'))
      ->setSetting('target_type', 'group') // 目标类型是 Group 实体
      ->setSetting('handler', 'default:group')
      ->setSetting('handler_settings', [
        'target_bundles' => ['scsk'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);
      
    $fields['valid_from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('有効開始日'))
      ->setDescription(t('有効開始日'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
      ])
      ->setRequired(TRUE);

    $fields['valid_to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('有効終了日'))
      ->setDescription(t('有効終了日'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
      ])
      ->setRequired(TRUE);

    $fields['discount_value'] = BaseFieldDefinition::create('float')
      ->setLabel(t('割引額/値引き'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('有効化'))
      ->setDefaultValue(TRUE);

      $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('作成者'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\\coupon_manager\\Entity\\Coupon::getCurrentUserId');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('作成日時'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('更新日時'));

    return $fields;
  }

  /**
   * Default value callback for 'user_id' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished()
  {
    $key = $this->getEntityType()->getKey('status');
    $this->set($key, FALSE);

    return $this;
  }

  public function setPublished()
  {
    $key = $this->getEntityType()->getKey('status');
    $this->set($key, TRUE);

    return $this;
  }
} 