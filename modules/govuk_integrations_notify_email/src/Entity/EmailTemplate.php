<?php

namespace Drupal\govuk_integrations_notify_email\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\govuk_integrations_notify_email\Entity\EmailTemplateInterface;

/**
 * Defines the configured text editor entity.
 *
 * @ConfigEntityType(
 *   id = "govuk_email_template",
 *   label = @Translation("Email Template"),
 *   label_collection = @Translation("Email Templates"),
 *   label_singular = @Translation("Email Template"),
 *   label_plural = @Translation("Email Templates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count email template",
 *     plural = "@count email templates",
 *   ),
 *   admin_permission = "administer govuk notify email templates",
 *   handlers = {
 *    "list_builder" = "Drupal\govuk_integrations_notify_email\EmailTemplateListBuilder",
 *    "form" = {
 *      "default" = "Drupal\govuk_integrations_notify_email\Form\EmailTemplateForm",
 *      "add" = "Drupal\govuk_integrations_notify_email\Form\EmailTemplateForm",
 *      "edit" = "Drupal\govuk_integrations_notify_email\Form\EmailTemplateForm",
 *      "delete" = "Drupal\govuk_integrations_notify_email\Form\EmailTemplateDeleteForm",
 *    },
 *   },
 *   links = {
 *    "canonical" = "/admin/config/govuk_integrations/email/templates/{govuk_email_template}/edit",
 *    "edit-form" = "/admin/config/govuk_integrations/email/templates/{govuk_email_template}/edit",
 *    "delete-form" = "/admin/config/govuk_integrations/email/templates/{govuk_email_template}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "template_id",
 *     "email_id",
 *     "label",
 *   }
 * )
 */
class EmailTemplate extends ConfigEntityBase implements EmailTemplateInterface {

  /**
   * The machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The GovUK Template ID.
   *
   * @var string
   *
   * @see getTemplateId()
   */
  protected $template_id;

  /**
   * The Drupal email ID.
   *
   * @var string
   *
   * @see getEmailId()
   */
  protected $email_id;

  /**
   * The config entity name.
   *
   * @var string
   */
  protected $label;

  public function id() {
    return $this->id;
  }

  public function label() {
    return $this->label;
  }

  public function getTemplateId() {
    return $this->template_id;
  }

  public function getEmailId() {
    return $this->email_id;
  }

  public function getLabel() {
    return $this->label;
  }

}
