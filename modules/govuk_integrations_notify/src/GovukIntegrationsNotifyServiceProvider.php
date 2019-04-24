<?php

namespace Drupal\govuk_integrations_notify;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the mobile_number.util service.
 */
class GovukIntegrationsNotifyServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('mobile_number.util');
    $definition->setClass('Drupal\govuk_integrations_notify\MobileNumberUtil');
  }

}
