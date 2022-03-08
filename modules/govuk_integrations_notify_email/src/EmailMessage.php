<?php

namespace Drupal\govuk_integrations_notify_email;

class EmailMessage {

  private string $template;

  private array $personalisation;

  private array $recipients;

  private string $reference;

  private string $replyTo;

  public function __construct($template, $recipients, $tokens = [], $reference = '', $replyTo = '') {
    $this->template = $template;
    $this->recipients = $recipients;
    $this->personalisation = $tokens;
    if ($reference) {
      $this->reference = $reference;
    }
    if ($replyTo) {
      $this->replyTo = $replyTo;
    }
  }

  /**
   * @return string
   */
  public function getTemplate(): string {
    return $this->template;
  }

  /**
   * @param string $template
   */
  public function setTemplate(string $template): void {
    $this->template = $template;
  }

  /**
   * @return array
   */
  public function getPersonalisation(): array {
    return $this->personalisation;
  }

  /**
   * @param array $personalisation
   */
  public function setPersonalisation(array $personalisation): void {
    $this->personalisation = $personalisation;
  }

  /**
   * @return array
   */
  public function getRecipients(): array {
    return $this->recipients;
  }

  /**
   * @param array $recipients
   */
  public function setRecipients(array $recipients): void {
    $this->recipients = $recipients;
  }

  /**
   * @return string
   */
  public function getReference(): string {
    return $this->reference;
  }

  /**
   * @return mixed|string
   */
  public function getReplyTo() {
    return $this->replyTo ?? NULL;
  }

  /**
   * @param mixed|string $replyTo
   */
  public function setReplyTo($replyTo): void {
    $this->replyTo = $replyTo;
  }

}
