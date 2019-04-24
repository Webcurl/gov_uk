<?php

namespace Drupal\govuk_integrations_pay;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a GovUkPayment entity.
 */
interface GovUkPaymentInterface extends ContentEntityInterface, EntityChangedInterface {

}
