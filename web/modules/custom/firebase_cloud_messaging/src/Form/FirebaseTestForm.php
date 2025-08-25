<?php

namespace Drupal\firebase_cloud_messaging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\firebase_cloud_messaging\Service\FirebaseMessagingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Firebase test notification form.
 */
class FirebaseTestForm extends FormBase {

  /**
   * The Firebase messaging service.
   *
   * @var \Drupal\firebase_cloud_messaging\Service\FirebaseMessagingService
   */
  protected $firebaseMessaging;

  /**
   * Constructs a new FirebaseTestForm.
   *
   * @param \Drupal\firebase_cloud_messaging\Service\FirebaseMessagingService $firebase_messaging
   *   The Firebase messaging service.
   */
  public function __construct(FirebaseMessagingService $firebase_messaging) {
    $this->firebaseMessaging = $firebase_messaging;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('firebase_cloud_messaging.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'firebase_cloud_messaging_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['notification_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('通知タイプ'),
      '#options' => [
        'device' => $this->t('Single Device'),
        'devices' => $this->t('Multiple Devices'),
        'topic' => $this->t('Topic'),
        'subscribe' => $this->t('Subscribe to Topic'),
        'unsubscribe' => $this->t('Unsubscribe from Topic'),
      ],
      '#default_value' => 'device',
      '#required' => TRUE,
    ];

    $form['device_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('デバイストークン'),
      '#description' => $this->t('ターゲットデバイスの登録トークン。'),
      '#states' => [
        'visible' => [
          ':input[name="notification_type"]' => ['value' => 'device'],
        ],
        'required' => [
          ':input[name="notification_type"]' => ['value' => 'device'],
        ],
      ],
    ];

    $form['device_tokens'] = [
      '#type' => 'textarea',
      '#title' => $this->t('デバイストークン(複数)'),
      '#description' => $this->t('複数の登録トークンを1行ずつ入力。'),
      '#states' => [
        'visible' => [
          ':input[name="notification_type"]' => [
            ['value' => 'devices'],
            ['value' => 'subscribe'],
            ['value' => 'unsubscribe'],
          ],
        ],
        'required' => [
          ':input[name="notification_type"]' => [
            ['value' => 'devices'],
            ['value' => 'subscribe'],
            ['value' => 'unsubscribe'],
          ],
        ],
      ],
    ];

    $form['topic_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('トピック名'),
      '#description' => $this->t('送信/購読対象のトピック名。'),
      '#states' => [
        'visible' => [
          ':input[name="notification_type"]' => [
            ['value' => 'topic'],
            ['value' => 'subscribe'],
            ['value' => 'unsubscribe'],
          ],
        ],
        'required' => [
          ':input[name="notification_type"]' => [
            ['value' => 'topic'],
            ['value' => 'subscribe'],
            ['value' => 'unsubscribe'],
          ],
        ],
      ],
    ];

    $form['notification'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('通知の詳細'),
      '#states' => [
        'visible' => [
          ':input[name="notification_type"]' => [
            ['value' => 'device'],
            ['value' => 'devices'],
            ['value' => 'topic'],
          ],
        ],
      ],
    ];

    $form['notification']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => 'テスト通知',
    ];

    $form['notification']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#required' => TRUE,
      '#default_value' => 'こちらはDrupalシステムからのテスト通知となります。',
    ];

    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('カスタムデータ (JSON)'),
      '#description' => $this->t('追加データ（JSON形式）の入力例：{"key":"value"}'),
      '#default_value' => '{}',
      '#states' => [
        'visible' => [
          ':input[name="notification_type"]' => [
            ['value' => 'device'],
            ['value' => 'devices'],
            ['value' => 'topic'],
          ],
        ],
      ],
    ];

    $form['platform_options'] = [
      '#type' => 'details',
      '#title' => $this->t('各プラットフォーム向け個別設定'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="notification_type"]' => [
            ['value' => 'device'],
            ['value' => 'devices'],
            ['value' => 'topic'],
          ],
        ],
      ],
    ];

    $form['platform_options']['android'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Android Config (JSON)'),
      '#description' => $this->t('Android向けオプション（JSON形式）'),
      '#default_value' => '{}',
    ];

    $form['platform_options']['apns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IOS Config (JSON)'),
      '#description' => $this->t('iOS通知向けカスタム設定（JSON形式）'),
      '#default_value' => '{}',
    ];

    $form['platform_options']['webpush'] = [
      '#type' => 'textarea',
      '#title' => $this->t('WebPush Config (JSON)'),
      '#description' => $this->t('WebPush 向けオプション設定（JSON形式）'),
      '#default_value' => '{}',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('テスト送信'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate JSON fields
    $json_fields = [
      'data',
      'android',
      'apns',
      'webpush',
    ];

    foreach ($json_fields as $field) {
      $value = $form_state->getValue($field);
      if (!empty($value)) {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
          $form_state->setErrorByName($field, $this->t('Invalid JSON format for @field.', ['@field' => $field]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $notification_type = $form_state->getValue('notification_type');
    $data = json_decode($form_state->getValue('data'), TRUE) ?: [];
    $android = json_decode($form_state->getValue('android'), TRUE) ?: [];
    $apns = json_decode($form_state->getValue('apns'), TRUE) ?: [];
    $webpush = json_decode($form_state->getValue('webpush'), TRUE) ?: [];

    $options = [
      'android' => $android,
      'apns' => $apns,
      'webpush' => $webpush,
    ];

    try {
      switch ($notification_type) {
        case 'device':
          $device_token = trim($form_state->getValue('device_token'));
          $notification = [
            'title' => $form_state->getValue('title'),
            'body' => $form_state->getValue('body'),
          ];
          $result = $this->firebaseMessaging->sendToDevice($device_token, $notification, $data, $options);
          break;

        case 'devices':
          $device_tokens = array_filter(array_map('trim', explode("\n", $form_state->getValue('device_tokens'))));
          $notification = [
            'title' => $form_state->getValue('title'),
            'body' => $form_state->getValue('body'),
          ];
          $result = $this->firebaseMessaging->sendToDevices($device_tokens, $notification, $data, $options);
          break;

        case 'topic':
          $topic_name = $form_state->getValue('topic_name');
          $notification = [
            'title' => $form_state->getValue('title'),
            'body' => $form_state->getValue('body'),
          ];
          $result = $this->firebaseMessaging->sendToTopic($topic_name, $notification, $data, $options);
          break;

        case 'subscribe':
          $topic_name = $form_state->getValue('topic_name');
          $device_tokens = array_filter(array_map('trim', explode("\n", $form_state->getValue('device_tokens'))));
          $result = $this->firebaseMessaging->subscribeToTopic($device_tokens, $topic_name);
          break;

        case 'unsubscribe':
          $topic_name = $form_state->getValue('topic_name');
          $device_tokens = array_filter(array_map('trim', explode("\n", $form_state->getValue('device_tokens'))));
          $result = $this->firebaseMessaging->unsubscribeFromTopic($device_tokens, $topic_name);
          break;
      }

      if (!empty($result['success'])) {
        $this->messenger()->addStatus($this->t('Operation completed successfully. Result: @result', [
          '@result' => print_r($result, TRUE),
        ]));
      }
      else {
        $this->messenger()->addError($this->t('Operation failed. Error: @error', [
          '@error' => $result['error'] ?? 'Unknown error',
        ]));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('An error occurred: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }
}