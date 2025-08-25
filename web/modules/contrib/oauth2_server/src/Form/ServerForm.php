<?php

namespace Drupal\oauth2_server\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oauth2_server\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Server Form.
 *
 * @package Drupal\oauth2_server\Form
 */
class ServerForm extends EntityForm {

  /**
   * The server entity.
   *
   * @var \Drupal\oauth2_server\ServerInterface
   */
  protected $entity;

  /**
   * The server storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * ServerForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('oauth2_server');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $server = $this->entity;

    $form['#title'] = $this->t('OAuth2 Server: %label edit', ['%label' => $server->label()]);
    $form['#tree'] = TRUE;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server name'),
      '#description' => $this->t('Enter the displayed name for the server.'),
      '#default_value' => $server->label(),
      '#required' => TRUE,
    ];
    $form['server_id'] = [
      '#type' => 'machine_name',
      '#default_value' => !$server->isNew() ? $server->id() : '',
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this->storage, 'load'],
        'source' => ['name'],
      ],
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Only enabled servers can be used for OAuth2.'),
      '#default_value' => $server->status(),
    ];
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Settings'),
    ];
    $form['settings']['enforce_state'] = [
      '#type' => 'value',
      '#value' => $server->settings['enforce_state'],
    ];

    // The default scope is actually edited from the Scope UI to avoid showing
    // a select box with potentially thousands of options here.
    $form['settings']['default_scope'] = [
      '#type' => 'value',
      '#value' => $server->settings['default_scope'],
    ];
    $form['settings']['allow_implicit'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow the implicit flow'),
      '#description' => t('Allows clients to receive an access token without the need for an authorization request token.'),
      '#default_value' => !empty($server->settings['allow_implicit']),
    ];
    $form['settings']['use_openid_connect'] = [
      '#type' => 'checkbox',
      '#title' => t('Use OpenID Connect'),
      '#description' => t("Strongly recommended for login providers."),
      '#default_value' => !empty($server->settings['use_openid_connect']),
      '#access' => extension_loaded('openssl'),
    ];
    $form['settings']['use_crypto_tokens'] = [
      '#type' => 'checkbox',
      '#title' => t('Use JWT Access Tokens'),
      '#description' => t("Sends encrypted JWT access tokens that aren't stored in the database."),
      '#default_value' => !empty($server->settings['use_crypto_tokens']),
      '#access' => extension_loaded('openssl'),
    ];
    $form['settings']['log_session_opened'] = [
      '#type' => 'checkbox',
      '#title' => t('Log session opened messages'),
      '#description' => t('Log messages when new sessions are opened after a successful authentication request.'),
      '#default_value' => $server->settings['log_session_opened'] ?? TRUE,
    ];

    // Prepare a list of available grant types.
    $grant_types = Utility::getGrantTypes();
    $grant_type_options = [];
    foreach ($grant_types as $type => $grant_type) {
      $grant_type_options[$type] = $grant_type['name'];
    }
    $form['settings']['grant_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enabled grant types'),
      '#options' => $grant_type_options,
      '#default_value' => $server->settings['grant_types'],
    ];

    // Add any grant type specific settings.
    foreach ($grant_types as $type => $grant_type) {
      // Merge-in any provided defaults.
      if (isset($grant_type['default settings'])) {
        $server->settings += $grant_type['default settings'];
      }

      // Add the form elements.
      if (isset($grant_type['settings callback'])) {
        $dom_ids = [];
        $dom_ids[] = 'edit-settings-grant-types-' . str_replace('_', '-', $type);
        $form['settings'] += $grant_type['settings callback']($server->settings, $dom_ids);
      }
    }

    $form['settings']['advanced_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['settings']['advanced_settings']['access_lifetime'] = [
      '#type' => 'textfield',
      '#title' => t('Access token lifetime'),
      '#description' => t('The number of seconds the access token will be valid for.'),
      '#default_value' => $server->settings['advanced_settings']['access_lifetime'],
      '#size' => 11,
    ];
    $form['settings']['advanced_settings']['id_lifetime'] = [
      '#type' => 'textfield',
      '#title' => t('ID token lifetime'),
      '#description' => t('The number of seconds the ID token will be valid for.'),
      '#default_value' => $server->settings['advanced_settings']['id_lifetime'],
      '#size' => 11,
      '#states' => [
        'visible' => [
          '#edit-settings-use-openid-connect' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['settings']['advanced_settings']['refresh_token_lifetime'] = [
      '#type' => 'textfield',
      '#title' => t('Refresh token lifetime'),
      '#description' => t('The number of seconds the refresh token will be valid for. 0 for forever.'),
      '#default_value' => $server->settings['advanced_settings']['refresh_token_lifetime'],
      '#size' => 11,
    ];
    $form['settings']['advanced_settings']['require_exact_redirect_uri'] = [
      '#type' => 'checkbox',
      '#title' => t('Require exact redirect uri'),
      '#description' => t("Require the redirect url to be an exact match of the client's redirect url. If not enabled, the redirect url in the request can contain additional segments, such as a query string."),
      '#default_value' => $server->settings['advanced_settings']['require_exact_redirect_uri'] ?? TRUE,
    ];
    return parent::form($form, $form_state);
  }

  /**
   * Provides a settings form for the refresh_token grant type.
   *
   * @param array $config
   *   The config array.
   * @param array $dom_ids
   *   The DOM ids.
   *
   * @return array
   *   A renderable form array.
   */
  public static function refreshTokenSettings(array $config, array $dom_ids = []) {
    $form = [];
    $form['always_issue_new_refresh_token'] = [
      '#type' => 'checkbox',
      '#title' => t('Always issue a new refresh token after the existing one has been used'),
      '#default_value' => $config['always_issue_new_refresh_token'],
    ];
    $form['unset_refresh_token_after_use'] = [
      '#type' => 'checkbox',
      '#title' => t('Unset (delete) the refresh token after it has been used'),
      '#default_value' => $config['unset_refresh_token_after_use'],
    ];
    foreach ($dom_ids as $dom_id) {
      $form['always_issue_new_refresh_token']['#states']['visible']['#' . $dom_id]['checked'] = TRUE;
      $form['unset_refresh_token_after_use']['#states']['visible']['#' . $dom_id]['checked'] = TRUE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save server');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addMessage($this->t('The server configuration has been saved.'));
    $form_state->setRedirect('oauth2_server.overview');
  }

}
