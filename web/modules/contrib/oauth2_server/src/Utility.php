<?php

namespace Drupal\oauth2_server;

use Drupal\Core\Url;
use OAuth2\Encryption\Jwt;
use OAuth2\HttpFoundationBridge\Request as BridgeRequest;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\OpenID\GrantType\AuthorizationCode;
use OAuth2\RequestInterface;
use OAuth2\Server;

/**
 * Contains utility methods for the OAuth2 Server.
 *
 * @package Drupal\oauth2_server
 *
 * @todo Maybe move some of these methods to other classes (and/or split this
 *   class into several utility classes).
 */
class Utility {

  /**
   * Returns an array of supported grant types and related data.
   */
  public static function getGrantTypes() {
    return [
      'authorization_code' => [
        'name' => t('Authorization code'),
        'class' => '\OAuth2\OpenID\GrantType\AuthorizationCode',
      ],
      'client_credentials' => [
        'name' => t('Client credentials'),
        'class' => '\OAuth2\GrantType\ClientCredentials',
      ],
      'urn:ietf:params:oauth:grant-type:jwt-bearer' => [
        'name' => t('JWT bearer'),
        'class' => '\OAuth2\GrantType\JwtBearer',
      ],
      'refresh_token' => [
        'name' => t('Refresh token'),
        'class' => '\OAuth2\GrantType\RefreshToken',
        'settings callback' => [
          __NAMESPACE__ . '\Form\ServerForm',
          'refreshTokenSettings',
        ],
        'default settings' => [
          'always_issue_new_refresh_token' => FALSE,
          'unset_refresh_token_after_use' => TRUE,
        ],
      ],
      'password' => [
        'name' => t('User credentials'),
        'class' => '\OAuth2\GrantType\UserCredentials',
      ],
    ];
  }

  /**
   * Decodes base64url encoded data.
   *
   * @param string $data
   *   A string containing the base64url encoded data.
   *
   * @return string|false
   *   The decoded data, or FALSE on failure.
   */
  public static function base64urlDecode($data) {
    $data = str_replace(['-', '_'], ['+', '/'], $data);
    return base64_decode($data);
  }

  /**
   * Encodes a string as base64url.
   *
   * @param string $data
   *   The string to encode.
   *
   * @return string
   *   The encoded data.
   */
  public static function base64urlEncode($data) {
    return str_replace(['+', '/'], ['-', '_'], base64_encode($data));
  }

  /**
   * Returns the pair of private and public keys used to sign tokens.
   *
   * @return array
   *   An array with the following keys:
   *   - private_key: The private key.
   *   - public_key: The public key certificate (PEM encoded X.509).
   *
   * @see oauth2_server_generate_keys()
   */
  public static function getKeys() {
    $keys = \Drupal::state()->get('oauth2_server.keys', FALSE);
    if (empty($keys['private_key']) || empty($keys['public_key'])) {
      $keys = static::generateKeys();
      \Drupal::state()->set('oauth2_server.keys', $keys);
    }

    return $keys;
  }

  /**
   * Generates a pair of private and public keys using OpenSSL.
   *
   * The public key is stored in a PEM encoded X.509 certificate, following
   * Google's example. The certificate can be passed to openssl_verify()
   * directly.
   *
   * @return array
   *   An array with the following keys:
   *   - private_key: The generated private key.
   *   - public_key: The generated public key certificate (PEM encoded X.509).
   */
  public static function generateKeys() {
    $module_path = \Drupal::service('extension.list.module')->getPath('oauth2_server');
    $module_realpath = \Drupal::service('file_system')->realpath($module_path);
    $config = [
      'config' => $module_realpath . DIRECTORY_SEPARATOR . 'oauth2_server.openssl.cnf',
    ];

    // Generate a private key.
    $resource = openssl_pkey_new($config);
    openssl_pkey_export($resource, $private_key);

    // Generate a public key certificate valid for 2 days.
    $serial = \Drupal::state()
      ->get('oauth2_server.next_certificate_id', 0);
    $uri = new Url('<front>', [], ['absolute' => TRUE, 'https' => TRUE]);
    $dn = [
      'CN' => $uri->toString(),
    ];
    $csr = openssl_csr_new($dn, $resource, $config);
    $x509 = openssl_csr_sign($csr, NULL, $resource, 2, $config, $serial);
    openssl_x509_export($x509, $public_key_certificate);
    // Increment the id for next time. db_next_id() is not used since it can't
    // guarantee sequential numbers.
    \Drupal::state()
      ->set('oauth2_server.next_certificate_id', ++$serial);

    return [
      'private_key' => $private_key,
      'public_key' => $public_key_certificate,
    ];
  }

  /**
   * Initializes and returns an OAuth2 server.
   *
   * @param \Drupal\oauth2_server\ServerInterface|null $server
   *   The server entity to use for supplying settings to the server, and
   *   initializing the scope. NULL only when we expect the validation to
   *   fail due to an incomplete or invalid request.
   * @param \Drupal\oauth2_server\OAuth2StorageInterface $storage
   *   The storage service to use for retrieving data.
   *
   * @return \OAuth2\Server|null
   *   An instance of OAuth2\Server or NULL if the server is not enabled.
   */
  public static function startServer(?ServerInterface $server, OAuth2StorageInterface $storage) {
    $grant_types = static::getGrantTypes();
    if ($server) {
      // Refuse to load config if the server is not enabled.
      // Hopefully this will mean we throw an error instead of allowing logins
      // if we fail to catch the server disabled status elsewhere.
      if (!$server->status()) {
        return NULL;
      }

      $uri = new Url('<front>', [], ['absolute' => TRUE, 'https' => TRUE]);
      $settings = $server->settings + [
        'issuer' => $uri->toString(),
      ] + $server->settings['advanced_settings'];
      unset($settings['advanced_settings']);

      // The setting 'use_crypto_tokens' was changed to 'use_jwt_access_tokens'
      // in v1.6 of the library. So this provides both.
      $settings['use_jwt_access_tokens'] = !empty($settings['use_crypto_tokens']) ?: FALSE;

      // Initialize the server and add the scope util.
      $oauth2_server = new Server($storage, $settings);
      $scope_util = new ScopeUtility($server);
      $oauth2_server->setScopeUtil($scope_util);

      // Determine the available grant types based on server settings.
      $enabled_grant_types = array_filter($settings['grant_types']);
    }
    else {
      $oauth2_server = new Server($storage);

      // Enable all grant types. One of them will handle the validation failure.
      $enabled_grant_types = array_keys($grant_types);
      $settings = [];
    }

    // Initialize the enabled grant types.
    foreach ($enabled_grant_types as $grant_type_name) {
      if ($grant_type_name == 'urn:ietf:params:oauth:grant-type:jwt-bearer') {
        $audience = new Url('oauth2_server.token', [], ['absolute' => TRUE]);
        $grant_type = new $grant_types[$grant_type_name]['class']($storage, $audience->toString());
      }
      else {
        $grant_type = new $grant_types[$grant_type_name]['class']($storage, $settings);
      }
      $oauth2_server->addGrantType($grant_type);
    }

    // Implicit flow requires its own instance of
    // OAuth2_GrantType_AuthorizationCode.
    if (!empty($settings['allow_implicit'])) {
      $grant_type = new AuthorizationCode($storage);
      $oauth2_server->addGrantType($grant_type, 'implicit');
    }
    return $oauth2_server;
  }

  /**
   * Get the client credentials from authorization header or request body.
   *
   * Used during token requests.
   *
   * @param \OAuth2\RequestInterface $request
   *   An instance of \OAuth2\HttpFoundationBridge\Request.
   *
   * @return array|null
   *   An array with the following keys:
   *   - client_id: The client key.
   *   - client_secret: The client secret.
   *   or NULL if no client credentials were found.
   */
  public static function getClientCredentials(RequestInterface $request) {
    // Get the client credentials from the Authorization header.
    if (!is_null($request->headers('PHP_AUTH_USER'))) {
      return [
        'client_id' => $request->headers('PHP_AUTH_USER'),
        'client_secret' => $request->headers('PHP_AUTH_PW', ''),
      ];
    }

    // Get the client credentials from the request body (POST).
    // Per spec, this method is not recommended and should be limited to clients
    // unable to utilize HTTP authentication.
    if (!is_null($request->request('client_id'))) {
      return [
        'client_id' => $request->request('client_id'),
        'client_secret' => $request->request('client_secret', ''),
      ];
    }

    // This request contains a JWT, extract the client_id from there.
    if (!is_null($request->request('assertion'))) {
      $jwt_util = new Jwt();
      $jwt = $jwt_util->decode($request->request('assertion'), NULL, FALSE);
      if (!empty($jwt['iss'])) {
        return [
          'client_id' => $jwt['iss'],
          // The JWT bearer grant type doesn't use the client_secret.
          'client_secret' => '',
        ];
      }
    }

    return NULL;
  }

  /**
   * Returns whether the current site needs to have keys generated.
   *
   * @return bool
   *   TRUE if at least one server uses JWT Access Tokens or OpenID Connect,
   *   FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function siteNeedsKeys() {
    /** @var \Drupal\oauth2_server\ServerInterface[] $servers */
    $servers = \Drupal::entityTypeManager()->getStorage('oauth2_server')
      ->loadMultiple();
    foreach ($servers as $server) {
      if (!empty($server->settings['use_crypto_tokens'])) {
        return TRUE;
      }
      if (!empty($server->settings['use_openid_connect'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check access for the passed server and scope.
   *
   * @param string $server_name
   *   The name of the server for which access should be verified.
   * @param string|null $scope
   *   An optional string of space-separated scopes to check.
   *
   * @return \OAuth2\ResponseInterface|array
   *   A valid access token if found, otherwise an \OAuth2\Response object
   *   containing an appropriate response message and status code.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function checkAccess($server_name, $scope = NULL) {
    /** @var \Drupal\oauth2_server\ServerInterface $server */
    $server = \Drupal::entityTypeManager()->getStorage('oauth2_server')
      ->load($server_name);
    $storage = \Drupal::service('oauth2_server.storage');
    $oauth2_server = Utility::startServer($server, $storage);
    $response = new BridgeResponse();

    $request = \Drupal::requestStack()
      ->getCurrentRequest();
    $bridgeRequest = BridgeRequest::createFromRequest($request);

    $token = $oauth2_server->getAccessTokenData($bridgeRequest, $response);

    // If there's no token, that means validation failed. Stop here.
    if (!$token) {
      return $response;
    }

    // Make sure that the token we have matches our server.
    if ($token['server'] != $server->id()) {
      $response->setError(401, 'invalid_grant', 'The access token provided is invalid');
      $response->addHttpHeaders([
        'WWW-Authenticate' => sprintf('%s, realm="%s", scope="%s"', 'bearer', 'Service', $scope),
      ]);
      return $response;
    }

    // Check scope, if provided. If token doesn't have a scope, it's null/empty,
    // or it's insufficient, throw an error.
    $scope_util = new ScopeUtility($server);
    if ($scope &&
       (!isset($token["scope"]) || !$token["scope"] || !$scope_util->checkScope($scope, $token["scope"]))) {
      $response->setError(401, 'insufficient_scope', 'The request requires higher privileges than provided by the access token');
      $response->addHttpHeaders([
        'WWW-Authenticate' => sprintf('%s, realm="%s", scope="%s"', 'bearer', 'Service', $scope),
      ]);
      return $response;
    }
    return $token;
  }

}
