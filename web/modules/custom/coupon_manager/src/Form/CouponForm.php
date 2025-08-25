<?php

namespace Drupal\coupon_manager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Datetime\DrupalDateTime;


class CouponForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coupon_manager_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\coupon_manager\Entity\Coupon $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('タイトル'),
      '#default_value' => $entity->get('title')->value ?? '',
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('説明'),
      '#default_value' => $entity->get('description')->value ?? '',
      '#required' => FALSE,
    ];

    $is_new = $entity->isNew();
    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('クーポンコード'),
      '#default_value' => $entity->get('code')->value ?? ($is_new ? $this->generateCouponCode() : ''),
      '#required' => TRUE,
      '#attributes' => $is_new ? [] : ['readonly' => 'readonly'],
      '#description' => $is_new ? $this->t('クーポンを自動作成しました（編集可能）') : $this->t('生成済み（編集不可）'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('有効化'),
      '#default_value' => $entity->get('status')->value ?? 1,
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\coupon_manager\Entity\Coupon $entity */
    $entity = $this->entity;

    $entity->set('title', $form_state->getValue('title'));
    $entity->set('description', $form_state->getValue('description') ?? '');
    $entity->set('status', $form_state->getValue('status') ? 1 : 0);

    if ($entity->isNew()) {
      $entity->set('code', $form_state->getValue('code'));
    }
    parent::submitForm($form, $form_state);
  }

  protected function generateCouponCode($length = 8) {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);
  
    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('クーポンが作成されました。'));
    }
    else {
      $this->messenger()->addStatus($this->t('クーポンが更新されました。'));
    }
    $form_state->setRedirect('entity.coupon.collection');
  }
}