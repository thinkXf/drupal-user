<?php

namespace Drupal\firebase_manager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Datetime\DrupalDateTime;

class FirebaseForm extends ContentEntityForm {

  protected const SELF_IMMEDIATE = 'immediate';
  protected const SELF_SCHEDULED = 'scheduled';
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'firebase_manager_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\firebase_manager\Entity\firebase $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['push_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('プッシュ通知タイトル'),
      '#default_value' => $entity->get('push_title')->value ?? '',
      '#required' => TRUE,
    ];
    $form['push_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('プッシュ通知本文'),
      '#required' => TRUE,
      '#default_value' => $entity->get('push_body')->value ?? '',
    ];
    // $form['push_url'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('プッシュ通知遷移先URL'),
    //   '#default_value' => $entity->get('push_url')->value ?? '',
    //   '#required' => FALSE,
    // ];
    $form['send_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('送信方法'),
      '#options' => [
        'immediate' => $this->t('即時送信'),
        'scheduled' => $this->t('予約送信'),
      ],
      '#default_value' => $this->getSendTypeDefaultValue(),
      '#weight' => 10,
      '#ajax' => [
        'callback' => '::toggleTimeFieldVisibility',
        'wrapper' => 'push-date-wrapper',
      ],
    ];

    $form['push_date']['widget'][0]['value'] += [
      '#prefix' => '<div id="push-date-wrapper">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="send_type"]' => ['value' => 'scheduled'],
        ],
      ],
    ];
    unset($form['status']);
    unset($form['push_flag']);
    unset($form['push_thumbnail']);
    unset($form['push_url']);

    return $form;
  }

  /**
   * 
   */
  protected function getSendTypeDefaultValue() {
    $entity = $this->entity;
    return $entity->get('push_date')->isEmpty() ? self::SELF_IMMEDIATE : self::SELF_SCHEDULED;
  }

  /**
   * 
   */
  public function toggleTimeFieldVisibility(array &$form, FormStateInterface $form_state) {
    return $form['push_date']['widget'][0]['value'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\firebase_manager\Entity\firebase $entity */
    $entity = $this->entity;
    
    $sendType = $form_state->getValue('send_type') ?? self::SELF_IMMEDIATE;
    if($sendType == self::SELF_IMMEDIATE) {
      $firebase_service = \Drupal::service('firebase_cloud_messaging.service');

      $device_token = "cmnz4Wz6bkCBYlLknLlBsq:APA91bEUPJ8yACHdY9M3V68LFNB1rQM8NQg-vcya3cxVh4BDSFJgnvdnfN9UIx_4_PTO90E0is81FNIYYmCwr7SnF1t-pGewoevDSIIeJ3-YOM4wVqFdp3E";
      $imageUrl = "";
      if(!empty($form_state->getValue('push_thumbnail')['selection'][0]['target_id'])) {
        $media_id = $form_state->getValue('push_thumbnail')['selection'][0]['target_id'];
        $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
        if ($media && $media->hasField('field_media_image')) {
          $image_field = $media->get('field_media_image');
          
          if (!$image_field->isEmpty()) {
            $file_entity = $image_field->entity;
            $image_uri = $file_entity->getFileUri();
            
            $imageUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($image_uri);
          }
        }
      }
      try {
        $notification = [
          'title' => $form_state->getValue('push_title'),
          'body' => $form_state->getValue('push_body'),
          'image' => $imageUrl ?? "",
        ];
        $data = [];
        $options = [];
        $pushCategoryId = $form_state->getValue('push_category')[0]['target_id'];
        if(!empty($pushCategoryId)) {
          \Drupal::logger('firebase')->info('notification: ' . json_encode($notification));
          $term = Term::load($pushCategoryId);
          if ($term) {
            $topicName = $term->get('field_topic_name')->value;
          }else {
            $form_state->setErrorByName('プッシュ通知', $this->t('トピック名が存在しません'));
          }
          // $result = $firebase_service->sendToDevice($device_token, $notification, $data, $options);
          $result = $firebase_service->sendToTopic($topicName, $notification);
          if((isset($result['success']) && !$result['success']) || !isset($result['success'])) {
            $entity->set('status', 2);
          }
          $entity->set('status', 1);
        }
      } catch (\Exception $e) {
        \Drupal::logger('firebase')->error($e->getMessage());
        $entity->set('status', 2);
      }
    }else {
      $entity->set('status', 0);
    }
    parent::submitForm($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);
    $entity_id = $entity->id();
    $sendType = $form_state->getValue('send_type') ?? self::SELF_IMMEDIATE;
    if ($sendType == self::SELF_SCHEDULED) {
        $pushCategoryId = $form_state->getValue('push_category')[0]['target_id'];
        if(!empty($pushCategoryId)) {
          $term = Term::load($pushCategoryId);
          if ($term) {
            $topicName = $term->get('field_topic_name')->value;
          }else {
            $form_state->setErrorByName('プッシュ通知', $this->t('トピック名が存在しません'));
          }
        }
        $pushDate = $form_state->getValue('push_date')[0]['value']->getTimestamp() ?? NULL;
        $queue = \Drupal::queue('firebase_notification_queue');
        $queue->createItem([
            'entity_id' => $entity_id,
            'topic' => $topicName,
            'title' => $form_state->getValue('push_title'),
            'message' => $form_state->getValue('push_body'),
            'scheduled_time' => $pushDate,
        ]);
    }
    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('プッシュ通知の送信に成功しました。'));
    }
    else {
      $this->messenger()->addStatus($this->t('プッシュ通知の送信に成功しました。'));
    }
    $form_state->setRedirect('entity.firebase.collection');
  }
}