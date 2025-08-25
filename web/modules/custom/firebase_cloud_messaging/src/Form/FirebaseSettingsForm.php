<?php

namespace Drupal\firebase_cloud_messaging\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\file\Entity\File;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   */
class FirebaseSettingsForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected TypedConfigManagerInterface $typedConfigManager
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'firebase_cloud_messaging_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['firebase_cloud_messaging.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('firebase_cloud_messaging.settings');
    $current_path = $config->get('service_account_path');

    $form['service_account'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Firebase Service Account'),
    ];

    // Show current file info if exists
    if ($current_path && file_exists($current_path)) {
      $form['service_account']['current_file'] = [
        '#type' => 'item',
        '#title' => $this->t('Current Service Account File'),
        '#markup' => $current_path,
      ];
    }

    $form['service_account']['upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Service Account JSON File'),
      '#description' => $this->t('Upload the JSON file you downloaded from Firebase Console.'),
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'json']
      ],
      '#upload_location' => $this->getUploadLocation(),
      '#default_value' => $config->get('service_account_file_id') ? [$config->get('service_account_file_id')] : NULL,
    ];

    // Hidden field to store the actual path
    $form['service_account_path'] = [
      '#type' => 'hidden',
      '#value' => $current_path,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Gets the upload location for the service account file.
   *
   * @return string
   *   The upload location URI.
   */
  protected function getUploadLocation() {
    $scheme = PrivateStream::basePath() ? 'private' : 'public';
    return $scheme . '://firebase';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $uploaded_files = $form_state->getValue('upload', []);
    if (!empty($uploaded_files)) {
      $file_id = reset($uploaded_files);
      if ($file_id) {
        $file = File::load($file_id);
        if ($file) {
          $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
          
          // Validate JSON content
          $content = file_get_contents($file_path);
          if (!json_decode($content)) {
            $form_state->setErrorByName('upload', $this->t('The uploaded file is not a valid JSON file.'));
          } else {
            // Store the path in the hidden field
            $form_state->setValue('service_account_path', $file_path);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uploaded_files = $form_state->getValue('upload', []);
    $file_id = !empty($uploaded_files) ? reset($uploaded_files) : NULL;
    if ($file_id) {
      $file = \Drupal\file\Entity\File::load($file_id);
      $file->setPermanent();
      $file->save();
    }
    $this->config('firebase_cloud_messaging.settings')
      ->set('service_account_path', $form_state->getValue('service_account_path'))
      ->set('service_account_file_id', $file_id)
      ->save();

    parent::submitForm($form, $form_state);
  }
}