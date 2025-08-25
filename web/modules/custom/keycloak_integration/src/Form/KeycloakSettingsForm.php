<?php

namespace Drupal\keycloak_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class KeycloakSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'keycloak_integration_settings';
  }

  protected function getEditableConfigNames() {
    return ['keycloak_integration.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('keycloak_integration.settings');

    $form['keycloak_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keycloak Server URL'),
      '#default_value' => $config->get('keycloak_url'),
      '#required' => TRUE,
    ];

    $form['realm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Realm'),
      '#default_value' => $config->get('realm'),
      '#required' => TRUE,
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('keycloak_integration.settings')
      ->set('keycloak_url', $form_state->getValue('keycloak_url'))
      ->set('realm', $form_state->getValue('realm'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->save();

    parent::submitForm($form, $form_state);
}
}