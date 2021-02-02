<?php

namespace Drupal\govuk_integrations_pay;

/**
 * Class PayClientFactory
 *
 * Instantiate GOV.UK Pay library using API key from config.
 */
class PayClientFactory {

  static function create($http_client, $config_factory) {
    $config = $config_factory->get('govuk_integrations_pay.settings');

    return new \Alphagov\Pay\Client([
      'apiKey' => $config->get('gov_pay__apikey'),
      'httpClient' => $http_client,
    ]);
  }

}
