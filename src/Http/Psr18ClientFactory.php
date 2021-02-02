<?php

namespace Drupal\govuk_integrations\Http;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Site\Settings;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;


/**
 * Class Psr18ClientFactory
 *
 * @todo Use Guzzle 7 native PSR-18 interface instead if/when available
 *
 * @package Drupal\gov_uk\Http
 */
class Psr18ClientFactory extends ClientFactory {

  public function fromOptions(array $config = []) {
    // Default Drupal Guzzle initialisation stuff
    $default_config = [
      // Security consideration: we must not use the certificate authority
      // file shipped with Guzzle because it can easily get outdated if a
      // certificate authority is hacked. Instead, we rely on the certificate
      // authority file provided by the operating system which is more likely
      // going to be updated in a timely fashion. This overrides the default
      // path to the pem file bundled with Guzzle.
      'verify' => TRUE,
      'timeout' => 30,
      'headers' => [
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
      ],
      'handler' => $this->stack,
      // Security consideration: prevent Guzzle from using environment variables
      // to configure the outbound proxy.
      'proxy' => [
        'http' => NULL,
        'https' => NULL,
        'no' => [],
      ],
    ];

    // PSR-18 client overrides below, i.e. the reason we're doing all this and
    // not just constructing the adapter from Drupal's http_client service.

    // Must not throw exceptions for valid HTTP responses.
    $default_config['http_errors'] = FALSE;

    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []), $config);

    return GuzzleAdapter::createWithConfig($config);
  }

}
