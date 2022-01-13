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
      'apiKey' => \Drupal::config('govuk_notify')->get('apiKey'),
      'httpClient' => new Client(),
    ]);
  }

  /**
   *
   */
  public function send(EmailMessage $email_message) {

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
