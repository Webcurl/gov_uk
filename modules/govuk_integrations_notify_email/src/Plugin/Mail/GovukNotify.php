<?php

namespace Drupal\govuk_integrations_notify_email\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Site\Settings;
use Drupal\govuk_integrations_notify_email\EmailMessage;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\UnstructuredHeader;

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "govuk_notify_email",
 *   label = @Translation("GovUK Notify mailer"),
 *   description = @Translation("Sends via GovUK Notify using preconfigured tempates.")
 * )
 */
class GovukNotify implements MailInterface {

  /**
   * A list of headers that can contain multiple email addresses.
   *
   * @see \Symfony\Component\Mime\Header\Headers::HEADER_CLASS_MAP
   */
  private const MAILBOX_LIST_HEADERS = ['from', 'to', 'reply-to', 'cc', 'bcc'];

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PhpMail constructor.
   */
  public function __construct() {
    $this->configFactory = \Drupal::configFactory();
  }

  /**
   * Concatenates and wraps the email body for plain-text mails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    // @TODO Need this function or not really?
    // @TODO Just bin the message?

    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    // Convert any HTML to plain-text.
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }

  /**
   * Sends an email message.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *   We check for a GovUK EmailMessage object that we can directly use.
   *   Else, check for template ID and parameters.
   *   Else, go find the template to use.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   *
   * @see http://php.net/manual/function.mail.php
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function mail(array $message) {

    $email = FALSE;

    $default_reply_to = \Drupal::config('govuk_integrations_notify_email.settings')->get('replyTo');

    // Have we been passed a perfect tidy bundle?
    if (!empty($message['params']['govuk_notify_email'])) {
      /** @var \Drupal\govuk_integrations_notify_email\EmailMessage $email */
      $email = $message['params']['govuk_notify_email'];
      if (!$email->getReplyTo()) {
        $email->setReplyTo($default_reply_to);
      }
    }
    elseif (!empty($message['govuk_notify_template'])) {
      $recipients = !empty($message['params']['recipients']) ?? [$message['to']];
      $replyto = !empty($message['reply-to']) ?? $default_reply_to;
      $personalisation = !empty($message['params']['personalisation']) ?? [];
      $reference = !empty($message['params']['reference']) ?? NULL;
      $email = new EmailMessage($message['params']['govuk_notify_template'], $recipients, $personalisation, $reference, $replyto);
    }
    // Else, maybe just a template ID?
    else {
      // @TODO Try to look up the email template from config.
      $template_id = NULL;

      $template_lookup = str_replace('-', '_', $message['id']);
      $template = \Drupal::config('govuk_integrations_notify_email.govuk_email_template.' . $template_lookup);
      if ($template->isNew()) {
        // @TODO ERROR we were passed an email but there is no template for it.
        return FALSE;
      }
      else {
        $template_id = $template->get('template_id');
        if (!$template_id) {
          return FALSE;
        }
      }

      $recipients = !empty($message['params']['recipients']) ?? [$message['to']];
      $replyto = !empty($message['reply-to']) ?? $default_reply_to;
      $personalisation = !empty($message['params']['personalisation']) ?? [];
      $reference = !empty($message['params']['reference']) ?? NULL;
      $email = new EmailMessage($template_id, $recipients, $personalisation);
    }

    $client = new \Drupal\govuk_integrations_notify_email\GovUKEmailClient();
    $mail_result = $client->send($email);
    $successes = array_filter($mail_result);

    return count($mail_result) == count($successes);
  }

}
