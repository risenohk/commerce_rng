<?php

namespace Drupal\commerce_rng\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ajaxifies registrant add form.
 */
trait AjaxButtonsTrait {

  /**
   * Returns a "save" button.
   *
   * @param array $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return array
   *   Renderable form array.
   */
  protected function saveButton(array &$form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $button = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm'],
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    if ($this->routeMatch->getParameter('js') == 'ajax') {
      $button['#attributes']['class'][] = 'use-ajax';
      $button['#ajax'] = [
        'callback' => '::ajaxSave',
        'event' => 'click',
      ];

      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    return $button;
  }

  /**
   * Ajax callback that saves the form values and closes the current dialog.
   *
   * @param array $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The respond to send via ajax.
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal', $form));
    }
    else {
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Returns a cancel button.
   *
   * @param array $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return array
   *   Renderable form array.
   */
  protected function cancelButton(array &$form, FormStateInterface $form_state) {
    $button = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['button', 'button--cancel'],
      ],
      '#submit' => [
        '::submitCancel',
      ],
    ];

    if ($this->routeMatch->getParameter('js') == 'ajax') {
      $button['#attributes']['class'][] = 'use-ajax';
      $button['#ajax'] = [
        'callback' => '::ajaxCancel',
        'event' => 'click',
      ];
    }

    return $button;
  }

  /**
   * Form submit handler that reacts on the cancel button being clicked.
   *
   * @param array $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function submitCancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Ajax callback that closes the current dialog.
   *
   * @param array $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The respond to send via ajax.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
