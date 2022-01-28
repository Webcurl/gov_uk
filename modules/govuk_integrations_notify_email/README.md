# GOV UK Notify Emails

This module wraps/exposes the GOV UK Notify API for sending emails.

## Setup

### API Key

Create an API key at GOV UK Notify here: https://www.notifications.service.gov.uk/

Enter it on the configuration page at:

**/admin/config/govuk_integrations/email**


## Things to know

1. The API itself allows you to send one email at a time. This module will do a foreach through the receipients, thus allowing you to send the same email to multiple addresses. But for optimal safety you should still use a queue to send these emails one at a time.
2. The module will return an array of booleans, indicating the success of each email.
3. The module does not currently handle file attachments.

## Example code

@TODO Example with new code
@TODO Example of other drupally options

@TODO How to use the interface

Send an email like this:

```  
  $personalisation = [
    'consultation' => 'Demonstration consultation',
    'close date' => '25/12/2022 at 5pm',
    'link' => 'https://www.mkc.prod.onwebcurl.com'
  ]; 
  $template = '8c5e08cd-f370-4f39-a80d-4936bc3c7801';
  $recipients = ['email@example.com', 'test@example.com'];
  
  $email = new \Drupal\govuk_integrations_notify_email\EmailMessage($template, $recipients, $personalisation);
  $client = new \Drupal\govuk_integrations_notify_email\GovUkEmailClient();
  $client->send($email);
```
