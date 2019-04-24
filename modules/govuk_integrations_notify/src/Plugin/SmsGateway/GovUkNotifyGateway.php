<?php

namespace Drupal\govuk_integrations_notify\Plugin\SmsGateway;

use Alphagov\Notifications\Exception\ApiException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Http\Adapter\Guzzle6\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResultStatus;
use Alphagov\Notifications\Client as GovUKNotify;

/**
 * GOV.UK Notify SMS Gateway.
 *
 * @SmsGateway(
 *   id = "govuk_notify",
 *   label = "GOV.UK Notify",
 *   outgoing_message_max_recipients = 1,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 * )
 */
class GovUkNotifyGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

  protected $govUkNotifyConfig;

  /**
   * Constructs a new GOV.UK Notify instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->govUkNotifyConfig = [
      'httpClient' => new Client(),
      'apiKey' => $configuration['api_key'],
      'templateId' => $configuration['template_id'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'template_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['govuk_notify'] = [
      '#type' => 'details',
      '#title' => 'GOV.UK Notify',
      '#open' => TRUE,
    ];

    $form['govuk_notify']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GOV.UK Notify API Key'),
      '#default_value' => $config['api_key'],
      '#required' => TRUE,
    ];

    $form['govuk_notify']['template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GOV.UK Notify Template ID'),
      '#default_value' => $config['template_id'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['api_key'] = trim($form_state->getValue('api_key'));
    $this->configuration['template_id'] = trim($form_state->getValue('template_id'));
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $client = new GovUKNotify($this->govUkNotifyConfig);
    $result = new SmsMessageResult();
    $report = new SmsDeliveryReport();

    $code = $sms_message->getOption('code') ? $sms_message->getOption('code') : NULL;
    $website = \Drupal::request()->getSchemeAndHttpHost();

    $response = NULL;
    foreach ($sms_message->getRecipients() as $recipient) {
      try {
        $response = $client->sendSms(
          $recipient,
          $this->govUkNotifyConfig['templateId'],
          [
            'code' => $code,
            'website' => $website,
          ]
        );
      }
      catch (ApiException $e) {
        $report->setStatus(SmsMessageReportStatus::ERROR);
        $report->setStatusMessage($e->getMessage());
        return $result
          ->addReport($report)
          ->setError(SmsMessageResultStatus::ERROR)
          ->setErrorMessage($e->getMessage());
      }
    }

    if (!is_null($response)) {
      $report->setStatus(SmsMessageReportStatus::QUEUED);
      $report->setMessageId($code);
    }
    else {
      $report->setStatus(SmsMessageReportStatus::ERROR);
      $report->setStatusMessage('Sending message failed.');
    }

    $report->setRecipient($sms_message->getRecipients()[0]);

    $result->addReport($report);

    return $result;
  }

}
