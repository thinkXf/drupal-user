<?php

namespace Drupal\firebase_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\firebase_cloud_messaging\Service\FirebaseMessagingService;
use Drupal\Core\Session\AccountInterface;

class FcmSubscriptionController extends ControllerBase {

  protected $fcmService;

  public function __construct(FirebaseMessagingService $fcmService) {
    $this->fcmService = $fcmService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('firebase_cloud_messaging.service')
    );
  }

  /**
   * 
   */
  public function registerToken(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $fcm_token = $content['fcm_token'] ?? '';
    $topic = $content['topic'] ?? 'all';

    if (empty($fcm_token)) {
      return new JsonResponse(['error' => 'FCM token is required.'], 400);
    }

    try {
        $user = \Drupal::entityTypeManager()
            ->getStorage('user')
            ->load(\Drupal::currentUser()->id());
        
        // if ($user && $user->hasField('field_fcm_token')) {
        //     $user->set('field_fcm_token', $fcm_token);
        //     $user->save();
        // }
        $this->fcmService->subscribeToTopic([$fcm_token], $topic);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Token registered and topic subscribed.'
        ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'error' => $e->getMessage()
      ], 500);
    }
  }
}