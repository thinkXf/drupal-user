<?php

namespace Drupal\oauth2_server\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class Server Disable Confirm Form.
 *
 * @package Drupal\oauth2_server\Form
 */
class ServerDisableConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable the OAuth2 server %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Disabling a server will also disable all attached scopes and clients. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('oauth2_server.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\oauth2_server\ServerInterface $server */
    $server = $this->entity;
    $server->setStatus(FALSE)->save();

    $this->messenger()->addMessage($this->t('The OAuth2 server %name has been disabled.', ['%name' => $this->entity->label()]));
    $form_state->setRedirect('oauth2_server.overview');
  }

}
