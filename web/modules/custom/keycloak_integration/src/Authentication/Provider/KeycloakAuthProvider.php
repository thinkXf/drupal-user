<?php

namespace Drupal\keycloak_integration\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;
use Symfony\Component\HttpFoundation\Request;

class KeycloakAuthProvider implements AuthenticationProviderInterface {

  protected $oauthProvider;
  protected $config;

  public function __construct(SimpleOauthAuthenticationProvider $oauth_provider, ConfigFactoryInterface $config_factory) {
    $this->oauthProvider = $oauth_provider;
    $this->config = $config_factory;
  }

  public function applies(Request $request) {
    $auth_header = $request->headers->get('Authorization');
    return !empty($auth_header) && strpos($auth_header, 'Bearer ') === 0;
  }

  public function authenticate(Request $request) {
    $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));
    
    if ($this->isKeycloakToken($token)) {
      $user = $this->convertKeycloakToken($token);
      if ($user) {
        \Drupal::logger('keycloak_integration')->info('Keycloak authenticated user: @username (UID: @uid)', [
          '@username' => $user->getDisplayName(),
          '@uid' => $user->id(),
        ]);
        return $user;
      }
    }
    
    return $this->oauthProvider->authenticate($request);
}

  protected function isKeycloakToken($token) {
    $config = $this->config->get('keycloak_integration.settings');
    $keycloak_url = $config->get('keycloak_url');
    $realm = $config->get('realm');
    $client_id = $config->get('client_id');
    
    $url = "{$keycloak_url}/realms/{$realm}/protocol/openid-connect/userinfo";
    try {
        $response = \Drupal::httpClient()->get($url, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
        return $response->getStatusCode() === 200;
    } catch (\Exception $e) {
      \Drupal::logger('keycloak_integration')->info('Keycloak authenticated message @url', [
        '@url' => $e->getMessage(),
      ]);
        return FALSE;
    }
  }

  protected function convertKeycloakToken($token) {
    $config = $this->config->get('keycloak_integration.settings');
    $keycloak_url = $config->get('keycloak_url');
    $realm = $config->get('realm');
    
    $url = "{$keycloak_url}/realms/{$realm}/protocol/openid-connect/userinfo";
    
    try {
      $response = \Drupal::httpClient()->get($url, [
        'headers' => [
          'Authorization' => "Bearer {$token}",
          'Content-Type' => 'application/json',
        ],
      ]);
      
      $userinfo = json_decode($response->getBody(), TRUE);
      
      $user_storage = \Drupal::entityTypeManager()->getStorage('user');
      $users = $user_storage->loadByProperties(['mail' => $userinfo['email']]);
      
      if (empty($users)) {
        $user = $user_storage->create([
          'name' => $userinfo['preferred_username'],
          'mail' => $userinfo['email'],
          'pass' => user_password(),
          'status' => 1,
        ]);
        $user->save();
      } else {
        $user = reset($users);
      }
      $roles = $user->getRoles();
      \Drupal::logger('keycloak_integration')->info('User roles: @roles', [
        '@roles' => implode(', ', $roles),
      ]);
      return $user;
      // 创建Simple OAuth token
      // $token_storage = \Drupal::entityTypeManager()->getStorage('oauth2_token');
      // $tokens = $token_storage->loadByProperties(['auth_user_id' => $user->id()]);
      
      // if (empty($tokens)) {
      //   $token_entity = $token_storage->create([
      //     'auth_user_id' => $user->id(),
      //     'client' => $config->get('client_id'),
      //     'expire' => $userinfo['exp'],
      //     'value' => $token,
      //   ]);
      //   $token_entity->save();
      // }
      
      // return $token;
    } catch (\Exception $e) {
      return NULL;
    }
  }
}