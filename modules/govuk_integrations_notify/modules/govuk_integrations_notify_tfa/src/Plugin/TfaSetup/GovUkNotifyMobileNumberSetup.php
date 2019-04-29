<?php

namespace Drupal\govuk_integrations_notify_tfa\Plugin\TfaSetup;

use Drupal\govuk_integrations_notify_tfa\Plugin\TfaValidation\GovUkNotifyMobileNumberTFA;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tfa\Plugin\TfaSetupInterface;
use Drupal\user\Entity\User;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use libphonenumber\PhoneNumber;

/**
 * GOV.UK Notify - Mobile Number setup for GOV.UK Notify SMS validation.
 *
 * @TfaSetup(
 *   id = "tfa_govuk_notify_mobile_number_setup",
 *   label = @Translation("GOV.UK Notify - Mobile Number Setup"),
 *   description = @Translation("GOV.UK Notify - Mobile Number Setup Plugin"),
 *   setupMessages = {
 *    "saved" = @Translation("Application code verified."),
 *    "skipped" = @Translation("Application codes not enabled.")
 *   }
 * )
 */
class GovUkNotifyMobileNumberSetup extends GovUkNotifyMobileNumberTFA implements TfaSetupInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $field_name = $this->mobileNumberUtil->getTfaField();
    $user = User::load($this->uid);
    if (
      $this->mobileNumberUtil->isTfaEnabled() &&
      $field_name
    ) {
      $field = $user->get($field_name)->getValue()[0];
    }
    $field['verified'] = FALSE;
    if (!boolval($field['verified'])) {
      $local_number = $field['value'];
      $country = $field['country'];
      $mobile_number = $this->mobileNumberUtil->getMobileNumber($local_number, $country);
      $this->configuration['mobileNo'] = $mobile_number;

      $verify = $this->verify($mobile_number);
      $help_links = $this->getHelpLinks();

      $items = [];
      foreach ($help_links as $item => $link) {
        $items[] = Link::fromTextAndUrl($item, Url::fromUri($link, ['attributes' => ['target' => '_blank']]));
      }

      $markup = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => $this->t('Install authentication code application on your mobile or desktop device:'),
      ];
      $form['apps'] = [
        '#type' => 'markup',
        '#markup' => \Drupal::service('renderer')->render($markup),
      ];
      $form['info'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>The two-asDasda authentication application will be used during this setup and for generating codes during regular authentication. If the application supports it, scan the QR code below to get the setup code otherwise you can manually enter the text code.</p>'),
      ];

      // Include code entry form.
      $form = $this->getForm($form, $form_state);
      $form['actions']['login']['#value'] = $this->t('Verify and save');
      // Value fields for storing specific message related information.
      $form['stored_code'] = [
        '#type' => 'value',
        '#value' => $verify['code'],
      ];
      $form['mobile_number'] = [
        '#type' => 'value',
        '#value' => $mobile_number,
      ];
      // Add AJAX callback for resending verification.
      $form['actions']['resend']['#submit'] = [];
      $form['actions']['resend']['#ajax'] = [
        'callback' => '_govuk_integrations_notify_tfa__resend_verification',
      ];
      // Alter code description.
      $form['code']['#description'] = $this->t('A verification code will be generated after you scan the above QR code or manually enter the setup code. The verification code is six digits long.');
    }
    else {
      $form['verified'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>You have already verified your Mobile number. Two factor authentication has been enabled.</p>'),
      ];
    }
    $form['account'] = [
      '#type' => 'value',
      '#value' => $user,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if ($trigger["#submit"][0] !== '_govuk_integrations_notify_tfa__resend_verification') {
      if (!$this->mobileValidate($this->configuration['mobileNo'], $form_state->getValue('code'))) {
        $this->errorMessages['code'] = $this->t('Invalid application code. Please try again.');
        return FALSE;
      }
      $this->storeAcceptedCode($form_state->getValue('code'));

      return TRUE;
    }
    return TRUE;
  }

  /**
   * Validate mobile number + code.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Mobile number stored.
   * @param string $code
   *   Code generated by Mobile Number.
   *
   * @return bool
   *   Return whether code is valid for the number.
   */
  protected function mobileValidate(PhoneNumber $mobile_number, $code) {
    return $this->mobileNumberUtil->verifyCode($mobile_number, $code);
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview($params) {
    $plugin_text = $this->t('Validation Plugin: @plugin',
      [
        '@plugin' => str_replace(' Setup', '', $this->getLabel()),
      ]
    );
    $output = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('TFA application'),
      ],
      'validation_plugin' => [
        '#type' => 'markup',
        '#markup' => '<p>' . $plugin_text . '</p>',
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Generate verification codes from a mobile or desktop application.'),
      ],
      'link' => [
        '#theme' => 'links',
        '#links' => [
          'admin' => [
            'title' => !$params['enabled'] ? $this->t('Set up application') : $this->t('Reset application'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpLinks() {
    return $this->pluginDefinition['helpLinks'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupMessages() {
    return ($this->pluginDefinition['setupMessages']) ?: '';
  }

  /**
   * Generates & sends a verification code to a mobile number.
   *
   * @param \libphonenumber\PhoneNumber $mobile_number
   *   Mobile number object.
   *
   * @return array
   *   Return token & code.
   */
  public function verify(PhoneNumber $mobile_number) {
    $util = $this->mobileNumberUtil;
    $code = $util->generateVerificationCode();
    $token = $util->sendVerification($mobile_number, '', $util->generateVerificationCode(), []);
    return [
      'token' => $token,
      'code' => $code,
    ];
  }

}
