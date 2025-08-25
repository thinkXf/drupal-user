<?php

namespace Drupal\coupon_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CouponBatchUploadForm extends FormBase {
  public function getFormId() {
    return 'coupon_manager_batch_upload_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'file',
      '#title' => $this->t('CSVファイルのアップロード'),
      '#description' => $this->t('code、title、category、company、valid_from、valid_to、discount_value、status を含むCSVファイルをアップロードしてください。'),
      '#required' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('アップロード'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $validators = [
      'FileExtension' => ['extensions' => 'csv']
    ];
    if ($file = file_save_upload('file', $validators, FALSE, 0)) {
      $handle = fopen($file->getFileUri(), 'r');
      $header = fgetcsv($handle);
      while ($row = fgetcsv($handle)) {
        $data = array_combine($header, $row);
        $coupon = \Drupal\coupon_manager\Entity\Coupon::create($data);
        $coupon->save();
      }
      fclose($handle);
      $this->messenger()->addStatus($this->t('一括アップロードが成功しました。'));
    } else {
      $this->messenger()->addError($this->t('ファイルのアップロードに失敗しました。'));
    }
  }
}