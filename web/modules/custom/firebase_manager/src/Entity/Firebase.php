<?php

namespace Drupal\firebase_manager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Firebase entity.
 *
 * @ContentEntityType(
 *   id = "firebase",
 *   label = @Translation("Firebase"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\firebase_manager\Form\FirebaseForm",
 *       "edit" = "Drupal\firebase_manager\Form\FirebaseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     }
 *   },
 *   base_table = "firebase",
 *   admin_permission = "administer firebase entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "push_title",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/firebases/{firebase}",
 *     "add-form" = "/admin/firebases/add",
 *     "edit-form" = "/admin/firebases/{firebase}/edit",
 *     "delete-form" = "/admin/firebases/{firebase}/delete",
 *     "collection" = "/admin/firebases",
 *     "add-page" = "/admin/firebases/add"
 *   }
 * )
 */
class Firebase extends ContentEntityBase implements EntityPublishedInterface {
  use EntityOwnerTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['push_flag'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('プッシュ通知フラグ'))
      ->setDescription(t('プッシュ通知フラグ.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox', 
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['push_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('プッシュ通知タイトル'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128]);

    $fields['push_body'] = BaseFieldDefinition::create('string')
      ->setLabel(t('プッシュ通知本文'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255]);
    
    $fields['push_category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('分類'))
      ->setDescription(t('円以上で割引 / 円引'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default:taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['oush_message_category'],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
        'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'autocomplete_type' => 'tags',
        'placeholder' => '',
      ],
    ]);

    $fields['push_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('プッシュ通知遷移先URL'))
      ->setRequired(FALSE)
      ->setSettings(['max_length' => 255]);

    $fields['push_thumbnail'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('プッシュ通知サムネイル'))
      ->setDescription(t('1ファイルのみ。5 GB 制限。許可されたタイプ: png jpg jpeg 。'))
      ->setSetting('target_type', 'media') 
      ->setSetting('handler', 'default')  
      ->setSetting('handler_settings', [
        'target_bundles' => ['image'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'media_library_widget',
        'weight' => 10,
        'settings' => [
          'media_types' => ['image'],
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 10,
        'settings' => [
          'view_mode' => 'thumbnail'
        ],
      ])
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['push_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('プッシュ通知配信日時'))
      ->setDescription(t('プッシュ通知配信日時'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
      ])
      ->setRequired(FALSE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('状態'))
      ->setDescription(t('状態(0: 未送信，1: 送信成功，2: 送信に成功しました)'))
      ->setSetting('size', 'tiny') 
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('作成者'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\\firebase_manager\\Entity\\firebase::getCurrentUserId');

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