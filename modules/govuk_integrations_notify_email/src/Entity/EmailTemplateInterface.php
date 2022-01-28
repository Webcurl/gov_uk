<?php

namespace Drupal\govuk_integrations_notify_email\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface EmailTemplateInterface extends ConfigEntityInterface {


  public function getTemplateId();

  public function getEmailId();

  public function getLabel();

}
