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

    $email_id = $message['id'];
    $module = $message['module'];
    $template_configured = $this->templateLookup($email_id, $module);

    // Have we been passed a perfect tidy bundle?
    if (!empty($message['params']['govuk_notify_email'])) {
      /** @var \Drupal\govuk_integrations_notify_email\EmailMessage $email */
      $email = $message['params']['govuk_notify_email'];
      if (!$email->getReplyTo() && $default_reply_to) {
        $email->setReplyTo($default_reply_to);
      }

      // The template is optional, so we check and set it if needed.
      if (!$email->getTemplate()) {
        $email->setTemplate($template_configured);
      }
    }
    // If we haven't used our fancy email object, then get things the usual way.
    elseif (!empty($message['govuk_notify_template'])) {
      $recipients = $message['params']['recipients'] ?? [$message['to']];
      $personalisation = $message['params']['personalisation'] ?? [];
      $reference = $message['params']['reference'] ?? NULL;
      $email = new EmailMessage($message['params']['govuk_notify_template'], $recipients, $personalisation, $reference);
      $replyto = $message['reply-to'] ?? $default_reply_to;
      if ($replyto ) {
        $email->setReplyTo($default_reply_to);
      }
    }
    // Else, what IF we were only passed a template ID!?
    else {
      // Try to look up the email template from config.
      $template_id = $this->templateLookup($message['id'], $message['module']);
      if (!$template_id) {
        return FALSE;
      }

      $recipients = $message['params']['recipients'] ?? $message['to'];
      $recipients = is_array($recipients) ? $recipients : [$recipients];
      $personalisation = $message['params']['personalisation'] ?? [];
      $email = new EmailMessage($template_id, $recipients, $personalisation);
      $replyto = $message['reply-to'] ?? $default_reply_to;
      if ($replyto ) {
        $email->setReplyTo($default_reply_to);
      }
    }

    $client = new \Drupal\govuk_integrations_notify_email\GovUKEmailClient();
    $mail_result = FALSE;
    try {
      $mail_result = $client->send($email);
    }
    catch (Exception $e) {
      watchdog_exception('govuk email send', $e, $e->getMessage(), NULL);
    }
    $successes = array_filter($mail_result);

    return count($mail_result) == count($successes);
  }

  /**
   * Given an email ID and a module, find the configured template.
   *
   * @param string $email_id
   *   Text identifier for the email being sent.
   * @param string $module
   *   Sending module's machine name.
   *
   * @return false
   */
  public function templateLookup($email_id, $module) {
    $template_lookup = str_replace('-', '_', $email_id);
    $template = \Drupal::config('govuk_integrations_notify_email.govuk_email_template.' . $template_lookup);
    if ($template->isNew()) {
      // @TODO ERROR we were passed an email but there is no template for it.
      \Drupal::logger('govuk email send')->error('No template specified for email %lookup by module %module', ['%lookup' => $email_id, '%module' => $module]);
      return FALSE;
    }
    else {
      $template_id = $template->get('template_id');
      if (!$template_id) {
        \Drupal::logger('govuk email send')->error('Template not found: %template', ['template' => $template_id]);
        return FALSE;
      }
      else {
        return $template_id;
      }
    }
  }

}
