<?php
namespace Drupal\firebase_manager\Cron;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class FirebaseNotificationCron {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function processScheduledNotifications() {
    $current_time = \Drupal::time()->getCurrentTime();
    
    $query = $this->entityTypeManager->getStorage('scheduled_notification')
      ->getQuery()
      ->condition('status', 'pending')
      ->condition('scheduled_time', $current_time, '<=')
      ->range(0, 10);
    
    $ids = $query->execute();
    
    if (!empty($ids)) {
      $notifications = $this->entityTypeManager
        ->getStorage('scheduled_notification')
        ->loadMultiple($ids);
      
      $queue = \Drupal::queue('firebase_notification_queue');
      
      foreach ($notifications as $notification) {
        $queue->createItem([
          'entity_id' => $notification->id(),
          'title' => $notification->get('title')->value,
          'message' => $notification->get('message')->value,
          'scheduled_time' => $notification->get('scheduled_time')->value,
        ]);
      }
    }
  }
}