<?php

namespace Drupal\govuk_integrations_pay_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElement\WebformMarkupBase;

/**
 * Provides a 'webform_govuk_integrations_pay' element.
 *
 * @WebformElement(
 *   id = "webform_govuk_integrations_pay",
 *   default_key = "govuk_integrations_pay",
 *   label = @Translation("GOV.UK Pay"),
 *   description = @Translation("Provides an element to allow for GOV.UK Pay integration."),
 *   category = @Translation("Advanced elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformGovUkPay extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'wrapper_attributes' => [],
        // Component settings.
      'amount_provider' => '',
      'amount_element' => '',
      'amount_static' => '',
      'default_content' => '',
      'default_markup' => '',
      'payment_message' => '',
      'confirmation_message' => '',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($element['#markup'])) {
      $element['#markup'] = MailFormatHelper::htmlToText($element['#markup']);
    }
    return parent::buildText($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Define which element types are allowed to be set as a mapped element
    // to the GOV.UK Pay element.
    $allowed_element_types = [
      'hidden',
      'number',
      'radios',
      'select',
      'value',
      'webform_computed_token',
      'webform_computed_twig',
      'webform_radios_other',
      'webform_select_other',
    ];

    // Fetch the current Webform elements to choose from.
    $formState = $form_state->getCompleteFormState();
    $buildInfo = $formState->getBuildInfo();
    $webform = $buildInfo['callback_object']->getWebform();
    $elements = $webform->getElementsInitialized();

    // Add any valid elements to the option list.
    $webform_element_list = [];
    foreach ($elements as $element_name => $element_array) {
      if (in_array($element_array['#type'], $allowed_element_types)) {
        $webform_element_list[$element_name] = $element_array['#title'];
      }
    }

    $form['govuk_integrations_pay'] = [
      '#title' => 'GOV.UK Pay settings',
      '#type' => 'fieldset',
    ];
    $form['govuk_integrations_pay']['amount_provider'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Amount source'),
      '#description' => $this->t('Choose how the amount of the GOV.UK Payment will be provided.'),
      '#options' => [
        'element' => $this->t('Webform element'),
        'static' => $this->t('Fixed amount'),
      ],
    ];
    $form['govuk_integrations_pay']['amount_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Amount element'),
      '#description' => $this->t('Choose which webform element will provide the value to GOV.UK Pay.'),
      '#options' => $webform_element_list,
      '#states' => [
        'visible' => [
          ':input[name="properties[amount_provider]"]' => ['value' => 'element'],
        ],
        'required' => [
          ':input[name="properties[amount_provider]"]' => ['value' => 'element'],
        ],
      ],
    ];
    $form['govuk_integrations_pay']['amount_static'] = [
      '#type' => 'textfield',
      '#attributes' => [
        ' type' => 'number',
        ' min' => 1,
      ],
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Choose the amount that a payment will be made for.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[amount_provider]"]' => ['value' => 'static'],
        ],
        'required' => [
          ':input[name="properties[amount_provider]"]' => ['value' => 'static'],
        ],
      ],
    ];
    $form['govuk_integrations_pay']['default_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output default content?'),
      '#description' => $this->t('Show the default content for a GOV.UK Pay enabled form.'),
    ];
    $form['govuk_integrations_pay']['payment_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GOV.UK Pay summary'),
      '#description' => $this->t('Text to display to user once they are redirected to GOV.UK Pay.'),
      '#maxlength' => 255,
    ];
    $form['govuk_integrations_pay']['confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Confirmation message'),
      '#description' => $this->t('Text to display to user once they return to the site from GOV.UK Pay.'),
    ];
    return $form;
  }

}
