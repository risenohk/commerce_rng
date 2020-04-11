<?php

namespace Drupal\commerce_rng\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;

class CompletionMessage extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['message'] = [
      '#theme' => 'commerce_rng_checkout_completion_message',
      '#order_entity' => $this->order,
    ];

    return $pane_form;
  }
}
