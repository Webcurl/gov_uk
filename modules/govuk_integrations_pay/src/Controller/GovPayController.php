<?php

namespace Drupal\govuk_integrations_pay\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\govuk_integrations_pay\Entity\GovUkPayment;

/**
 * Provides base interaction between GOV.UK Pay & Drupal.
 */
class GovPayController extends ControllerBase {
  protected $baseUrl;
  protected $apiKey;

  /**
   * GovPay constructor.
   */
  public function __construct() {
    $config = $this->config('govuk_integrations_pay.settings');
    if ($config->get('gov_pay__apikey')) {
      $this->apiKey = $config->get('gov_pay__apikey');
      $this->baseUrl = "https://publicapi.payments.service.gov.uk";
    }
  }

  /**
   * Get payment via GovPay paymentId.
   *
   * @param string $paymentId
   *   A payment id.
   *
   * @return mixed|null
   *   Returns object containing payment information.
   */
  public function getPayment($paymentId) {
    $return = NULL;
    if (isset($this->baseUrl)) {
      $url = "$this->baseUrl/v1/payments/$paymentId";
      // Instantiate request.
      $headers = [
        'CONTENT-TYPE' => "application/json",
        'Authorization' => "Bearer $this->apiKey",
      ];
      try {
        // Make request.
        $response = \Drupal::httpClient()->get(
          $url,
          [
            'headers' => $headers,
          ]
        );
        $resultData = (string) $response->getBody();
        $resultDecode = \GuzzleHttp\json_decode($resultData);
        $return = $resultDecode;
      }
      catch (RequestException $e) {
        watchdog_exception('govuk_integrations_pay', $e);
      }
    }
    return $return;
  }

  /**
   * Create a Payment in GovPay.
   *
   * @param array $params
   *   Additional parts to save (Node, Submission ID).
   *
   * @return mixed|null
   *   Return the GovPay Payment Object, or NULL if an issue occurs.
   */
  public function makePayment(array $params = []) {
    $return = NULL;
    if (isset($this->baseUrl)) {
      $base_url = \Drupal::request()->getSchemeAndHttpHost();
      $submission_id = isset($params['submission_id']) ? $params['submission_id'] : NULL;
      $webform_id = isset($params['webform_id']) ? $params['webform_id'] : NULL;
      $uuid = isset($params['uuid']) ? $params['uuid'] : NULL;
      if (is_null($uuid)) {
        $uuidService = \Drupal::service('uuid');
        $uuid = $uuidService->generate();
      }
      $reference = isset($params['reference']) ? $params['reference'] : 'na';

      $returnUrl = "$base_url/gov-pay-confirmation/$uuid";
      if (!is_null($webform_id)) {
        $returnUrl .= "/$webform_id";
        if (!is_null($submission_id)) {
          $returnUrl .= "/$submission_id";
        }
      }

      try {
        $url = "$this->baseUrl/v1/payments";
        // Instantiate request.
        $headers = [
          'Content-Type' => "application/json",
          'Authorization' => "Bearer $this->apiKey",
        ];
        $reqBody = [
          "amount" => $params['amount'],
          "email" => $params['email'] ?? "",
          "reference" => $reference,
          "return_url" => $returnUrl,
          "description" => $params['message'],
          "language" => "en",
        ];

        if (!empty($params['prefilled_cardholder_details'])) {
          $reqBody['prefilled_cardholder_details'] = $params['prefilled_cardholder_details'];
        }

        $jsonBody = json_encode($reqBody, JSON_UNESCAPED_SLASHES);
        // Make request.
        $options = [
          'headers' => $headers,
          'body' => $jsonBody,
        ];
        // Make request.
        $response = \Drupal::httpClient()->request(
          'POST',
          $url,
          $options
        );
        // If request returns data, decode.
        $resultData = (string) $response->getBody();
        $resultDataDecode = json_decode($resultData);

        $return = $resultDataDecode;

        // Setup entity record.
        $payment = GovUkPayment::create([
          'payment_id' => $resultDataDecode->payment_id,
          'uuid' => $uuid,
          'status' => $resultDataDecode->state->status,
          'amount' => $params['amount'],
        ]);
        if (!is_null($webform_id)) {
          $payment->webform_id = $webform_id;
        }
        if (!is_null($submission_id)) {
          $payment->submission_id = $submission_id;
        }
        $payment->save();
        $return->payment_object = $payment;
      }
      catch (RequestException $e) {
        watchdog_exception('govuk_integrations_pay', $e);
      }
    }
    return $return;
  }

  /**
   * Retrieve onsite record of a gov_payment.
   *
   * @param string $uuid
   *   UUID of payment.
   * @param int $webform_id
   *   Optional Webform ID of payment.
   * @param int $submission_id
   *   Optional Submission ID of payment.
   *
   * @return array
   *   Return loaded gov_payment entities.
   */
  public function fetchGovPayment($uuid, $webform_id = NULL, $submission_id = NULL) {
    $return = [];

    // Initial Query.
    $query = \Drupal::entityQuery('content_entity_govukpayment');
    $query->accessCheck(TRUE);
    $query->condition('uuid', $uuid);
    if ($webform_id) {
      $query->condition('webform_id', $webform_id);
    }
    if ($submission_id) {
      $query->condition('submission_id', $submission_id);
    }
    $result = $query->execute();

    if (!empty($result)) {
      $return = GovUkPayment::loadMultiple($result);
    }

    return $return;
  }

}
