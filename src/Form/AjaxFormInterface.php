<?php

namespace Drupal\commerce_rng\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for ajaxifying a registrant form.
 */
interface AjaxFormInterface extends FormInterface {

  /**
   * Ajax callback that saves the form values and closes the current dialog.
   *
   * @param [] $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormInterface $form_state
   *   The state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The respond to send via ajax.
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state);

  /**
   * Form submit handler that reacts on the cancel button being clicked.
   *
   * @param [] $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormInterface $form_state
   *   The state of the form.
   */
  public function submitCancel(array &$form, FormStateInterface $form_state);

  /**
   * Ajax callback that closes the current dialog.
   *
   * @param [] $form
   *   The form that was ajaxified.
   * @param \Drupal\Core\Form\FormInterface $form_state
   *   The state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The respond to send via ajax.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state);

}
