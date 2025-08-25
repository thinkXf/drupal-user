<?php

namespace Drupal\oauth2_server\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\oauth2_server\ClientInterface;

/**
 * Defines the OAuth2 client entity.
 *
 * @ConfigEntityType(
 *   id = "oauth2_server_client",
 *   label = @Translation("OAuth2 Server Client"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\oauth2_server\ClientListBuilder",
 *     "form" = {
 *       "add" = "Drupal\oauth2_server\Form\ClientForm",
 *       "edit" = "Drupal\oauth2_server\Form\ClientForm",
 *       "default" = "Drupal\oauth2_server\Form\ClientForm",
 *       "delete" = "Drupal\oauth2_server\Form\ClientDeleteConfirmForm",
 *     },
 *   },
 *   config_prefix = "client",
 *   admin_permission = "administer oauth2 server",
 *   entity_keys = {
 *     "id" = "client_id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "client_id",
 *     "server_id",
 *     "name",
 *     "client_secret",
 *     "public_key",
 *     "redirect_uri",
 *     "automatic_authorization",
 *     "settings",
 *     "logo_uri",
 *     "client_uri",
 *     "policy_uri",
 *     "tos_uri",
 *   }
 * )
 */
class Client extends ConfigEntityBase implements ClientInterface {

  /**
   * The client id of this client.
   *
   * @var string
   */
  public $client_id;

  /**
   * The machine name of this client's server.
   *
   * @var string
   */
  public $server_id;

  /**
   * The loaded server.
   *
   * @var \Drupal\oauth2_server\ServerInterface
   */
  protected $server;

  /**
   * The label of the client.
   *
   * @var string
   */
  public $name;

  /**
   * The client URI.
   *
   * @var string
   */
  public $client_uri;

  /**
   * The client logo URI.
   *
   * @var string
   */
  public $logo_uri;

  /**
   * The policy URI.
   *
   * @var string
   */
  public $policy_uri;

  /**
   * The terms of service URI.
   *
   * @var string
   */
  public $tos_uri;

  /**
   * The client secret.
   *
   * @var string
   */
  public $client_secret;

  /**
   * The public key.
   *
   * @var string
   */
  public $public_key;

  /**
   * The absolute URI to redirect to after authorization.
   *
   * @var string
   */
  public $redirect_uri;

  /**
   * Whether authorization should be completed without user confirmation.
   *
   * @var bool
   */
  public $automatic_authorization = FALSE;

  /**
   * Client specific settings.
   *
   * @var array
   */
  public $settings = [
    'override_grant_types' => FALSE,
    'allow_implicit' => FALSE,
    'grant_types' => [
      'authorization_code' => 'authorization_code',
      'refresh_token' => 'refresh_token',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->client_id ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->name ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getServer() {
    if (!$this->server && $this->server_id) {
      $this->server = \Drupal::entityTypeManager()->getStorage('oauth2_server')
        ->load($this->server_id);
    }
    return $this->server;
  }

  /**
   * {@inheritdoc}
   */
  public function hashClientSecret($client_secret) {
    if ($client_secret === '') {
      return $client_secret;
    }

    /** @var \Drupal\Core\Password\PasswordInterface $password_hasher */
    $password_hasher = \Drupal::service('password');
    return $password_hasher->hash($client_secret);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    if (isset($values['unhashed_client_secret'])) {
      $values['client_secret'] = $this->hashClientSecret($values['unhashed_client_secret']);
    }
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep(): array {
    $this->server = NULL;
    return parent::__sleep();
  }

}
