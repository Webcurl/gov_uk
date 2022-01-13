<?php

namespace Drupal\govuk_integrations_notify_email\Plugin\EmailGateway;

use Alphagov\Notifications\Exception\ApiException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\govuk_integrations_notify_email\EmailMessage;
use Http\Adapter\Guzzle6\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Alphagov\Notifications\Client as GovUKNotify;

/**
 * GOV.UK Notify Email Gateway.
 */
class GovUkNotifyGatewayEmail implements ContainerFactoryPluginInterface {

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
  public function sendEmail(EmailMessage $email_message) {

    $response = NULL;
    foreach ($email_message->getRecipients() as $recipient) {
      try {
        $response = $this->client->sendEmail(
          $recipient,
          $email_message->getTemplate(),
          $email_message->getPersonalisation(),
        );
      }
      catch (ApiException $e) {
        // @todo Catch or report or something!
      }
    }

    if (!is_null($response)) {
      $stop = 1;
      // $report->setStatus(EmailMessageReportStatus::QUEUED);
      //    $report->setMessageId($code);
    }
    else {
      $stop = 1;
      // $report->setStatus(EmailMessageReportStatus::ERROR);
      //    $report->setStatusMessage('Sending message failed.');
    }

    // @todo review
    $result = 1;
    return $result;
  }

}
