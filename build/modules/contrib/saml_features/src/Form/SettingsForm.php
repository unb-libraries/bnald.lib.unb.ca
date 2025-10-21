<?php
/**
 * @file
 * Contains Drupal\saml_features\Form\SettingsForm.
 */
namespace Drupal\saml_features\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'saml_features.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saml_features_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saml_features.adminsettings');

    $form['exclude_stu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude <strong>STU</strong> reference from SAML messaging text'),
      '#default_value' => $config->get('exclude_stu'),
    ];

    $form['user_form_protect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent authenticated users from editing their profile'),
      '#default_value' => $config->get('user_form_protect'),
    ];

    $form['enable_user_email_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow enabling the <b>User Email Notify</b> checkbox on new user account form'),
      '#default_value' => $config->get('enable_user_email_notify'),
    ];

    $form['display_metadata_alert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display <b>Directory Info</b> alert on user profile'),
      '#default_value' => $config->get('display_metadata_alert'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('saml_features.adminsettings')
      ->set('exclude_stu', $form_state->getValue('exclude_stu'))
      ->set('user_form_protect', $form_state->getValue('user_form_protect'))
      ->set('enable_user_email_notify', $form_state->getValue('enable_user_email_notify'))
      ->set('display_metadata_alert', $form_state->getValue('display_metadata_alert'))
      ->save();
  }

}
