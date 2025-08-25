<?php

namespace Drupal\coupon_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CouponFilterForm extends FormBase {

    protected $listBuilder;

    public function getFormId() {
        return 'coupon_manager_filter_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $list_builder = NULL) {
        $this->listBuilder = $list_builder;

        $form['code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('优惠券码'),
            '#default_value' => \Drupal::request()->query->get('code', ''),
        ];
        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('标题'),
            '#default_value' => \Drupal::request()->query->get('title', ''),
        ];
        $form['category'] = [
            '#type' => 'select',
            '#title' => $this->t('分类'),
            '#options' => [
                '' => $this->t('-全部-'),
                'discount' => $this->t('折扣'),
                'full_reduction' => $this->t('满减'),
                'free_shipping' => $this->t('包邮'),
            ],
            '#default_value' => \Drupal::request()->query->get('category', ''),
        ];
        $form['status'] = [
            '#type' => 'select',
            '#title' => $this->t('状态'),
            '#options' => [
                '' => $this->t('-全部-'),
                1 => $this->t('有效'),
                0 => $this->t('无效'),
            ],
            '#default_value' => \Drupal::request()->query->get('status', ''),
        ];
        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type' => 'submit',
                '#value' => $this->t('筛选'),
            ],
        ];
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $query = [];
        foreach (['code', 'title', 'category', 'status'] as $field) {
        $value = $form_state->getValue($field);
        if ($value !== '' && $value !== NULL) {
            $query[$field] = $value;
        }
        }
        $form_state->setRedirect('<current>', [], ['query' => $query]);
    }
}