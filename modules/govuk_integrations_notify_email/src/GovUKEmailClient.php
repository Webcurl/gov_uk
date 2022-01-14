<?php

namespace Drupal\govuk_integrations_notify_email;

use Alphagov\Notifications\Client as GovUKNotify;
use Http\Adapter\Guzzle6\Client;

/**
 *
 */
class GovUKEmailClient {

  private GovUKNotify $client;

  /**
   *
   */
  public function __construct() {
    $this->client = new GovUKNotify([
      'apiKey' => \Drupal::config('govuk_integrations_notify_email.settings')->get('apiKey'),
      'httpClient' => new Client(),
    ]);
  }

  /**
   * Send a single email to multiple recipients.
   */
  public function send(EmailMessage $email_message) {
    $response = NULL;
    $success = [];
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
        watchdog_exception('GovUk Notify Email', $e);
        $success[] = FALSE;
      }

      if (!is_null($response)) {
        $success[] = TRUE;
      }
      else {
        $success[] = FALSE;
      }
    }

    return $success;
  }

}
