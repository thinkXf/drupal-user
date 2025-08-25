<?php

namespace Drupal\coupon_manager\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Coupon entities.
 */
class CouponListBuilder extends EntityListBuilder {

  protected $filterValues = [];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'code' => [
        'data' => $this->t('クーポンコード'),
        'field' => 'code',
        'sort' => 'desc',
        'specifier' => 'code',
      ],
      'title' => [
        'data' => $this->t('タイトル'),
        'field' => 'title',
        'specifier' => 'title',
      ],
      'company' => [
        'data' => $this->t('会社'),
        'field' => 'company',
        'specifier' => 'company',
      ],
      'category' => [
        'data' => $this->t('分類'),
        'field' => 'category',
        'specifier' => 'category',
      ],
      'status' => [
        'data' => $this->t('有効化'),
        'field' => 'status',
        'specifier' => 'status',
      ],
      'valid_from' => [
        'data' => $this->t('有効開始日'),
        'field' => 'valid_from',
        'specifier' => 'valid_from',
      ],
      'valid_to' => [
        'data' => $this->t('有有効終了日効化'),
        'field' => 'valid_to',
        'specifier' => 'valid_to',
      ],
    ];
    $header += parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\coupon_manager\Entity\Coupon $entity */
    $row['code'] = $entity->get('code')->value;
    $row['title'] = $entity->get('title')->value;
    $row['company'] = $entity->get('company')->value;
    $row['category'] = $entity->get('category')->value;
    $row['status'] = $entity->get('status')->value ? $this->t('有効') : $this->t('无効');
    $row['valid_from'] = $entity->get('valid_from')->value ? date('Y-m-d H:i', strtotime($entity->get('valid_from')->value)) : '';
    $row['valid_to'] = $entity->get('valid_to')->value ? date('Y-m-d H:i', strtotime($entity->get('valid_to')->value)) : '';
    $row += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // $build['filter_form'] = \Drupal::formBuilder()->getForm('\Drupal\coupon_manager\Form\CouponFilterForm', $this);
    
    $build += parent::render();

    $build['batch_upload'] = [
      '#type' => 'link',
      '#title' => $this->t('批量上传优惠券'),
      '#url' => \Drupal\Core\Url::fromRoute('coupon_manager.batch_upload'),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#weight' => -9,
    ];

    // クーポンを追加
    $build['add'] = [
      '#type' => 'link',
      '#title' => $this->t('クーポンを追加'),
      '#url' => Url::fromRoute('entity.coupon.add_form'),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#weight' => -10,
    ];

    // $filter = [
    //   'code' => \Drupal::request()->query->get('code', ''),
    //   'title' => \Drupal::request()->query->get('title', ''),
    //   'category' => \Drupal::request()->query->get('category', ''),
    //   'status' => \Drupal::request()->query->get('status', ''),
    // ];
    // $this->setFilterValues($filter);

    return $build;
  }

  public function setFilterValues(array $values) {
      $this->filterValues = $values;
  }

  protected function getEntityIds() {
      $query = $this->getStorage()->getQuery();
      $query->accessCheck(TRUE);
      if (!empty($this->filterValues['code'])) {
        $query->condition('code', $this->filterValues['code'], 'CONTAINS');
      }
      if (!empty($this->filterValues['title'])) {
        $query->condition('title', $this->filterValues['title'], 'CONTAINS');
      }
      if (!empty($this->filterValues['category'])) {
        $query->condition('category', $this->filterValues['category']);
      }
      if (isset($this->filterValues['status']) && $this->filterValues['status'] !== '') {
        $query->condition('status', $this->filterValues['status']);
      }

      $query->sort($this->entityType->getKey('id'), 'DESC');
      return $query->execute();
  }
}