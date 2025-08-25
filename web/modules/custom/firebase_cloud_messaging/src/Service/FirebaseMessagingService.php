<?php

namespace Drupal\firebase_cloud_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\Topic;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

/**
 * Firebase Cloud Messaging service.
 */
class FirebaseMessagingService {

  /**
   * The Firebase Messaging instance.
   *
   * @var \Kreait\Firebase\Messaging
   */
  protected $messaging;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a FirebaseMessagingService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $config = $config_factory->get('firebase_cloud_messaging.settings');
    $service_account_path = $config->get('service_account_path');

    if (!file_exists($service_account_path)) {
      throw new \RuntimeException('Firebase service account file not found.');
    }

    $firebase = (new Factory)
      ->withServiceAccount($service_account_path);

    $this->messaging = $firebase->createMessaging();
    $this->logger = $logger_factory->get('firebase_cloud_messaging');
  }

  /**
   * Send a notification to a single device.
   *
   * @param string $deviceToken
   *   The device registration token.
   * @param array $notification
   *   The notification data (title, body).
   * @param array $data
   *   Additional data payload.
   * @param array $options
   *   Platform-specific options (android, apns, webpush).
   *
   * @return array
   *   The message ID and response.
   */
  public function sendToDevice(string $deviceToken, array $notification, array $data = [], array $options = []) {
    try {
      $message = CloudMessage::withTarget('token', $deviceToken)
        ->withNotification(Notification::create($notification['title'], $notification['body']))
        ->withData($data);

      // Apply platform-specific configurations
      $message = $this->applyPlatformConfigs($message, $options);

      $response = $this->messaging->send($message);

      $this->logger->info('Notification sent to device @token. Message ID: @messageId', [
        '@token' => $deviceToken,
        '@messageId' => $response,
      ]);

      return [
        'message_id' => $response,
        'success' => TRUE,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error sending notification to device @token: @error', [
        '@token' => $deviceToken,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Send a notification to multiple devices.
   *
   * @param array $deviceTokens
   *   Array of device registration tokens.
   * @param array $notification
   *   The notification data (title, body).
   * @param array $data
   *   Additional data payload.
   * @param array $options
   *   Platform-specific options (android, apns, webpush).
   *
   * @return array
   *   Results for each device.
   */
  public function sendToDevices(array $deviceTokens, array $notification, array $data = [], array $options = []) {
    $results = [];
    $validTokens = [];

    // Validate tokens first
    foreach ($deviceTokens as $token) {
      if (!empty($token)) {
        $validTokens[] = $token;
      }
    }

    if (empty($validTokens)) {
      return [
        'success' => FALSE,
        'error' => 'No valid device tokens provided.',
      ];
    }

    try {
      $message = CloudMessage::new()
        ->withNotification(Notification::create($notification['title'], $notification['body']))
        ->withData($data);

      // Apply platform-specific configurations
      $message = $this->applyPlatformConfigs($message, $options);

      $response = $this->messaging->sendMulticast($message, $validTokens);

      foreach ($response->getItems() as $item) {
        $token = $item->target()->value();
        if ($item->isSuccess()) {
          $results[$token] = [
            'success' => TRUE,
            'message_id' => $item->messageId(),
          ];
        }
        else {
          $results[$token] = [
            'success' => FALSE,
            'error' => $item->error()->getMessage(),
          ];
        }
      }

      $this->logger->info('Multicast notification sent to @count devices.', [
        '@count' => count($validTokens),
      ]);

      return [
        'results' => $results,
        'success_count' => $response->successes()->count(),
        'failure_count' => $response->failures()->count(),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error sending multicast notification: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Subscribe devices to a topic.
   *
   * @param array $deviceTokens
   *   Array of device registration tokens.
   * @param string $topicName
   *   The topic name to subscribe to.
   *
   * @return array
   *   The subscription results.
   */
  public function subscribeToTopic(array $deviceTokens, string $topicName) {
    try {
      $topic = Topic::fromValue($topicName);
      $result = $this->messaging->subscribeToTopic($topic, $deviceTokens);

      $this->logger->info('@count devices subscribed to topic "@topic".', [
        '@count' => count($deviceTokens),
        '@topic' => $topicName,
      ]);

      return [
        'success' => TRUE,
        'success_count' => $result['successCount'] ?? 0,
        'failure_count' => $result['failureCount'] ?? count($deviceTokens),
        'errors' => $result['errors'] ?? [],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error subscribing to topic "@topic": @error', [
        '@topic' => $topicName,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Unsubscribe devices from a topic.
   *
   * @param array $deviceTokens
   *   Array of device registration tokens.
   * @param string $topicName
   *   The topic name to unsubscribe from.
   *
   * @return array
   *   The unsubscription results.
   */
  public function unsubscribeFromTopic(array $deviceTokens, string $topicName) {
    try {
      $topic = new Topic($topicName);
      $response = $this->messaging->unsubscribeFromTopic($topic, $deviceTokens);

      $this->logger->info('@count devices unsubscribed from topic "@topic".', [
        '@count' => count($deviceTokens),
        '@topic' => $topicName,
      ]);

      return [
        'success' => TRUE,
        'success_count' => count($deviceTokens) - count($response->errors()),
        'failure_count' => count($response->errors()),
        'errors' => $response->errors(),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error unsubscribing from topic "@topic": @error', [
        '@topic' => $topicName,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Send a notification to a topic.
   *
   * @param string $topicName
   *   The topic name.
   * @param array $notification
   *   The notification data (title, body).
   * @param array $data
   *   Additional data payload.
   * @param array $options
   *   Platform-specific options (android, apns, webpush).
   *
   * @return array
   *   The message ID and response.
   */
  public function sendToTopic(string $topicName, array $notification, array $data = [], array $options = []) {
    try {
      $message = CloudMessage::withTarget('topic', $topicName)
        ->withNotification(Notification::create($notification['title'], $notification['body']))
        ->withData($data);

      // Apply platform-specific configurations
      $message = $this->applyPlatformConfigs($message, $options);

      $response = $this->messaging->send($message);

      $this->logger->info('Notification sent to topic "@topic". Message ID: @messageId', [
        '@topic' => $topicName,
        '@messageId' => $response,
      ]);

      return [
        'message_id' => $response,
        'success' => TRUE,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error sending notification to topic "@topic": @error', [
        '@topic' => $topicName,
        '@error' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Apply platform-specific configurations to the message.
   *
   * @param \Kreait\Firebase\Messaging\CloudMessage $message
   *   The message object.
   * @param array $options
   *   Platform-specific options.
   *
   * @return \Kreait\Firebase\Messaging\CloudMessage
   *   The configured message.
   */
  protected function applyPlatformConfigs(CloudMessage $message, array $options) {
    // Android specific config
    if (!empty($options['android'])) {
      $androidConfig = AndroidConfig::fromArray($options['android']);
      $message = $message->withAndroidConfig($androidConfig);
    }

    // APNs (Apple) specific config
    if (!empty($options['apns'])) {
      $apnsConfig = ApnsConfig::fromArray($options['apns']);
      $message = $message->withApnsConfig($apnsConfig);
    }

    // WebPush specific config
    if (!empty($options['webpush'])) {
      $webPushConfig = WebPushConfig::fromArray($options['webpush']);
      $message = $message->withWebPushConfig($webPushConfig);
    }

    return $message;
  }
}