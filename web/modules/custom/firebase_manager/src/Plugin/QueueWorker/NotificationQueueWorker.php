<?php

namespace Drupal\firebase_manager\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Kreait\Firebase\Factory;

/**
 * @QueueWorker(
 *   id = "firebase_notification_queue",
 *   title = @Translation("Firebase Notification Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
class NotificationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $firebase;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->firebase = \Drupal::service('firebase_cloud_messaging.service');;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  public function processItem($data) {
    $current_time = \Drupal::time()->getCurrentTime();
    
    if ($data['scheduled_time'] > $current_time) {
      \Drupal::queue('firebase_notification_queue')->createItem($data);
      return;
    }
    
    try {
        $notification = [
            'title' => $data['title'],
            'body' => $data['message']
        ];
        $result = $this->firebase->sendToTopic($data['topic'], $notification);
        if (isset($data['entity_id'])) {
            $notification = \Drupal::entityTypeManager()
            ->getStorage('firebase')
            ->load($data['entity_id']);
            if((isset($result['success']) && !$result['success']) || !isset($result['success'])) {
              $notification->set('status', 2)->save();
            } else {
              $notification->set('status', 1)->save();
            }
        }
    } catch (\Exception $e) {
      \Drupal::logger('firebase_messaging')->error($e->getMessage());
    }
  }
}