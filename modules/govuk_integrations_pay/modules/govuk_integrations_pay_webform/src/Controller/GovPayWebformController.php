<?php

namespace Drupal\govuk_integrations_pay_webform\Controller;

use Drupal\govuk_integrations_pay\Controller\GovPayController;
use Drupal\webform\Entity\Webform;

/**
 * Provides base interaction between GOV.UK Pay & Drupal.
 */
class GovPayWebformController extends GovPayController {

  public function confirmationPage($uuid, $webform_id = NULL, $submission_id = NULL) {
    // Base variables to return.
    $paymentId = NULL;
    $amount = NULL;
    $paymentStatus = NULL;
    $paymentMessage = NULL;
    $confirmationMessage = NULL;

    // Find GOV.UK Pay element.
    $webform = Webform::load($webform_id);
    $elements = $webform->getElementsInitialized();
    $govPayElement = NULL;
    foreach ($elements as $element) {
      if ($element['#type'] === 'webform_govuk_integrations_pay') {
        $govPayElement = $element;
        break;
      }
    }

    // Fetch GOV.UK Pay values out of element.
    $confirmationMessage = isset($govPayElement['#confirmation_message']) ? $govPayElement['#confirmation_message'] : NULL;

    // Provide default confirmation message if empty.
    if (is_null($confirmationMessage)) {
      $confirmationMessage = '
        Thank you for making a payment via GOV.UK Pay.<br/>
        If your payment has not shown as complete for over 1 day, 
        please contact us with your payment ID.
      ';
    }

    // Fetch on-site payment record.
    $govPay = new GovPayController();
    $fetchPayment = $govPay->fetchGovPayment($uuid);

    // Ensure only 1 payment matches.
    if (count($fetchPayment) === 1) {
      // Allow alterations to the information before sending.
      \Drupal::moduleHandler()->alter('govuk_integrations_pay_confirmation', $fetchPayment);

      // Fetch GOV.UK Pay payment.
      $paymentObject = $fetchPayment[array_keys($fetchPayment)[0]];
      $paymentId = $paymentObject->get('payment_id')->getValue()[0]['value'];
      $getPayment = $govPay->getPayment($paymentId);

      // Set Status & Message.
      $paymentStatus = isset($getPayment->state->status) ?
        $getPayment->state->status :
        'Status not found.';
      $paymentMessage = isset($getPayment->state->message) ?
        $getPayment->state->message :
        '';
      $amount = isset($getPayment->amount) ?
        '£' . number_format(floatval($getPayment->amount) / 100, 2) :
        'Payment not found';
    }

    return [
      '#theme' => 'govuk_integrations_pay_webform__govuk_confirmation_page',
      '#payment_id' => $paymentId,
      '#payment_amount' => $amount,
      '#payment_status' => $paymentStatus,
      '#payment_message' => $paymentMessage,
      '#confirmation_message' => $confirmationMessage,
    ];
  }

}
