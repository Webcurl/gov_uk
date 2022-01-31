<?php

namespace Drupal\govuk_integrations_notify_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GovUkNotifyEmailForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['govuk_integrations_notify_email.settings'];
  }

  public function getFormId() {
    return 'GovUkNotifyEmailForm';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('govuk_integrations_notify_email.settings');

    $form['apiKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('apiKey'),
      '#description' => $this->t('The secret key that provides access to the API'),
      '#required' => TRUE,
    ];

    $form['replyTo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reply-to email address ID'),
      '#default_value' => $config->get('replyTo'),
      '#description' => $this->t('The ID for the email address configured on GOV.UK Notify.'),
      '#required' => FALSE,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('govuk_integrations_notify_email.settings');
    $config->set('apiKey', $form_state->getValue('apiKey'));
    $config->set('replyTo', $form_state->getValue('replyTo'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
