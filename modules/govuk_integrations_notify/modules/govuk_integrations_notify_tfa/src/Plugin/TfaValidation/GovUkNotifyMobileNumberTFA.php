<?php

namespace Drupal\govuk_integrations_notify_tfa\Plugin\TfaValidation;

use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\tfa\Plugin\TfaSendInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\mobile_number\Exception\MobileNumberException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use libphonenumber\PhoneNumber;

/**
 * Class GovUkNotifyMobileNumberTFA is a validation and sending plugin for TFA.
 *
 * @TfaValidation(
 *   id = "tfa_govuk_notify_mobile_number",
 *   label = @Translation("GOV.UK Notify - Mobile Number"),
 *   description = @Translation("GOV.UK Notify implement of the Mobile Number TFA Validation Plugin"),
 * )
 */
class GovUkNotifyMobileNumberTFA extends TfaBasePlugin implements TfaValidationInterface, TfaSendInterface {
  /**
   * Libphonenumber Utility object.
   *
   * @var \Drupal\mobile_number\MobileNumberUtilInterface
   */
  public $mobileNumberUtil;

  /**
   * Libphonenumber phone number object.
   *
   * @var \libphonenumber\PhoneNumber
   */
  public $mobileNumber;

  protected $userField;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);
    $this->mobileNumberUtil = \Drupal::service('mobile_number.util');

    if (!empty($context['validate_context']) && !empty($context['validate_context']['code'])) {
      $this->code = $context['validate_context']['code'];
    }

    if (!empty($context['validate_context']) && !empty($context['validate_context']['verification_token'])) {
      $this->verificationToken = $context['validate_context']['verification_token'];
    }

    $this->codeLength = 4;

    $plugin_settings = \Drupal::config('tfa.settings')->get('validation_plugin_settings');
    $settings = isset($plugin_settings['tfa_govuk_notify_mobile_number']) ? $plugin_settings['tfa_govuk_notify_mobile_number'] : [];
    $this->userField = $settings['user_field'];

    if ($m = $this->mobileNumberUtil->tfaAccountNumber($configuration["uid"])) {
      try {
        $this->mobileNumber = $this->mobileNumberUtil->testMobileNumber($m);
      }
      catch (MobileNumberException $e) {
        throw new Exception("Two factor authentication failed: \n" . $e->getMessage(), $e->getCode());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.data'),
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return $this->mobileNumberUtil->tfaAccountNumber(($this->uid)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function begin() {
    if (!$this->code) {
      if (!$this->sendCode()) {
        drupal_set_message(t('Unable to deliver the code. Please contact support.'), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->mobileNumberUtil->getTfaField();
    $user = User::load($this->uid);
    $field = NULL;
    if (
      $this->mobileNumberUtil->isTfaEnabled() &&
      $field_name
    ) {
      $field = $user->get($field_name)->getValue()[0];
    }
    $form = [];
    if (!is_null($field)) {
      $local_number = $field['value'];
      $country = $field['country'];
      $mobile_number = $this->mobileNumberUtil->getMobileNumber($local_number, $country);
      $this->configuration['mobileNo'] = $mobile_number;

      $getToken = $this->mobileNumberUtil->getToken($mobile_number);
      if (!$getToken) {
        $verify = $this->verify($mobile_number);
      }
      $local_number = $this->mobileNumberUtil->getLocalNumber($this->mobileNumber);
      $numberClue = str_pad(substr($local_number, -3, 3), strlen($local_number), 'X', STR_PAD_LEFT);
      $numberClue = substr_replace($numberClue, '-', 3, 0);

      $form['code'] = [
        '#type' => 'textfield',
        '#title' => t('Verification Code'),
        '#required' => TRUE,
        '#description' => t('A verification code was sent to %clue. Enter the @length-character code sent to your device.', ['@length' => $this->codeLength, '%clue' => $numberClue]),
      ];

      $form['stored_code'] = [
        '#type' => 'value',
        '#value' => $verify['code'],
      ];
      $form['mobile_number'] = [
        '#type' => 'value',
        '#value' => $mobile_number,
      ];

      $this->configuration['stored_code'] = $verify['code'];

      $form['actions']['#type'] = 'actions';
      $form['actions']['login'] = [
        '#type' => 'submit',
        '#value' => t('Verify'),
      ];
      $form['actions']['resend'] = [
        '#type' => 'submit',
        '#value' => t('Resend'),
        '#submit' => [''],
        '#limit_validation_errors' => [],
        '#name' => 'resend',
        '#ajax' => [
          'callback' => '_govuk_integrations_notify_tfa__resend_verification',
        ],
      ];
    }
    else {
      $form['failure'] = [
        '#type' => 'markup',
        '#markup' => '<p>We were unable to send through a verification code due to not having a valid mobile number to send to.</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm($config, $state) {
    $baseUser = new User([], 'user');
    $fields = $baseUser->getFields();
    $fieldOptions = [];
    foreach ($fields as $fieldName => $fieldList) {
      $definition = $fieldList->getFieldDefinition();
      if ($definition->getType() === 'mobile_number') {
        $fieldOptions[$fieldName] = $definition->get('label');
      }
    }

    $settings_form['user_field'] = [
      '#type' => 'select',
      '#empty_option' => TRUE,
      '#options' => $fieldOptions,
      '#title' => t('Verification field'),
      '#default_value' => ($this->userField) ?: NULL,
      '#description' => 'Choose the User field to verify mobile numbers upon.',
      '#states' => $state,
      '#required' => TRUE,
    ];
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $values = $form_state->getValues();
    // If operation is resend then do not attempt to validate code.
    if ($trigger['#name'] === 'resend') {
      return TRUE;
    }
    else {
      $verify = $this->verifyCode($values['code']);
      if (!$verify) {
        $form_state->setError($form['code'], 'Invalid code. Please re-enter your code, or Resend the code.');
        $form_state->setErrorByName('code', 'Invalid code. Please re-enter your code, or Resend the code.');
      }
      return $verify;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface &$form_state) {
    // Resend code if pushed.
    if ($form_state['values']['op'] === $form_state['values']['resend']) {
      if (!$this->mobileNumberUtil->checkFlood($this->mobileNumber, 'sms')) {
        drupal_set_message(t('Too many verification code requests, please try again shortly.'), 'error');
      }
      elseif (!$this->sendCode()) {
        drupal_set_message(t('Unable to deliver the code. Please contact support.'), 'error');
      }
      else {
        drupal_set_message(t('Code resent'));
      }

      return FALSE;
    }
    else {
      return parent::submitForm($form, $form_state);
    }
  }

  /**
   * Return context for this plugin.
   */
  public function getPluginContext() {
    return array('code' => $this->code, 'verification_token' => !empty($this->verificationToken) ? $this->verificationToken : '');
  }

  /**
   * Send the code via the client.
   *
   * @return bool
   *   Where sending sms was successful.
   */
  public function sendCode() {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($this->context['uid']);
    $this->code = $this->mobileNumberUtil->generateVerificationCode($this->codeLength);
    try {
      $message = \Drupal::configFactory()->getEditable('mobile_number.settings')->get('tfa_message');
      $message = $message ? $message : $this->mobileNumberUtil->MOBILE_NUMBER_DEFAULT_SMS_MESSAGE;
      if (!($this->verificationToken = $this->mobileNumberUtil->sendVerification($this->mobileNumber, $message, $this->code, array('user' => $user)))) {
        return FALSE;
      }

      // @todo Consider storing date_sent or date_updated to inform user.
      \Drupal::logger('mobile_number_tfa')->info('TFA validation code sent to user @uid', array('@uid' => $this->context['uid']));
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('mobile_number_tfa')
        ->error(
          'Send message error to user @uid. Status code: @code, message: @message',
          array(
            '@uid' => $this->context['uid'],
            '@code' => $e->getCode(),
            '@message' => $e->getMessage(),
          )
        );
      return FALSE;
    }
  }

  /**
   * Verifies the given code with this session's verification token.
   *
   * @param string $code
   *   Code.
   *
   * @return bool
   *   Verification status.
   */
  public function verifyCode($code) {
    return $this->isValid = $this->mobileNumberUtil->verifyCode($this->mobileNumber, $code);
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbacks() {
    return ($this->pluginDefinition['fallbacks']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function purge() {
    // TODO: Implement purge() method.
  }

  /**
   * {@inheritdoc}
   */
  public function isFallback() {
    // TODO: Implement isFallback() method.
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
