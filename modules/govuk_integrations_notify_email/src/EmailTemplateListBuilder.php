<?php

namespace Drupal\govuk_integrations_notify_email;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

class EmailTemplateListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govuk_integrations_notify_email_entity_govuk_email_template_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['email_id'] = $this->t('Email');
    $header['template_id'] = $this->t('GOVUK Template');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
//    $row['label'] = [
//      '#type' => 'link',
//      '#title' => $entity->label(),
//      '#url' => Url::fromRoute('govuk_integrations_notify_email.template_edit_form', ['govuk_email_template' => $entity->id()])->toString(),
//    ];

    $row['label'] = $entity->label();

    $row['id'] = $entity->id();

    $row['template'] = $entity->getTemplateId();

    return $row + parent::buildRow($entity);
  }


  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
//    $operations['edit'] = [
//      'title' => t('Edit'),
//      'weight' => 0,
//      'url' => Url::fromRoute('govuk_integrations_notify_email.template_edit_form', ['govuk_email_template' => $entity->id()])->toString(),
//    ];
//    $operations['delete'] = [
//      'title' => t('Delete'),
//      'weight' => 1,
//      'url' => Url::fromRoute('govuk_integrations_notify_email.template_delete_form', ['govuk_email_template' => $entity->id()])->toString(),
//    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
//  public function submitForm(array &$form, FormStateInterface $form_state) {
//    parent::submitForm($form, $form_state);
//
//    drupal_set_message(t('The template settings have been updated.'));
//  }


}
