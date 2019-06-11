<?php

namespace Drupal\govuk_integrations_pay\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for GOV.UK pay.
 */
class GovUkPayGeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govuk_integrations_pay_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['govuk_integrations_pay.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('govuk_integrations_pay.settings');

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Settings',
    ];

    $form['settings']['gov_pay__apikey'] = [
      '#title' => 'Active API key',
      '#type' => 'textfield',
      '#default_value' => $config->get('gov_pay__apikey'),
      '#description' => t('The API key used for interacting with GOV.UK Pay.'),
    ];

    $form['settings']['gov_pay__reference'] = [
      '#title' => 'Payment reference',
      '#type' => 'textfield',
      '#default_value' => $config->get('gov_pay__reference'),
      '#description' => t('The payment reference assigned to all GOV.UK Pay transactions on this site.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('govuk_integrations_pay.settings');

    $config
      ->set('gov_pay__apikey', $form_state->getValue('gov_pay__apikey'))
      ->set('gov_pay__reference', $form_state->getValue('gov_pay__reference'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
