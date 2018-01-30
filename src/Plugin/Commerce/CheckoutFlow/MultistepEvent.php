<?php

namespace Drupal\commerce_rng\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_rng\Plugin\Commerce\CheckoutPane\IsPaneCompleteInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the conference multistep checkout flow.
 *
 * @CommerceCheckoutFlow(
 *   id = "multistep_event",
 *   label = "Multistep - Event",
 * )
 */
class MultistepEvent extends CheckoutFlowWithPanesBase implements IsStepCompleteInterface {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    return [
      'login' => [
        'label' => $this->t('Login'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => FALSE,
      ],
      'event_registration' => [
        'label' => $this->t('Event registration'),
        'previous_label' => $this->t('Go back'),
        'next_label' => $this->t('Continue to event registration'),
        'has_sidebar' => TRUE,
      ],
      'order_information' => [
        'label' => $this->t('Order information'),
        'previous_label' => $this->t('Go back'),
        'next_label' => $this->t('Continue'),
        'has_sidebar' => TRUE,
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to review'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => TRUE,
      ],
    ] + parent::getSteps();
  }

  /**
   * {@inheritdoc}
   */
  public function isStepComplete($step_id) {
    foreach ($this->getVisiblePanes($step_id) as $pane_id => $pane) {
      if ($pane instanceof IsPaneCompleteInterface) {
        if (!$pane->isComplete()) {
          return FALSE;
        }
      }
    }

    // All panes checked.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step_id = NULL) {
    $form = parent::buildForm($form, $form_state, $step_id);

    if (!$this->isStepComplete($step_id)) {
      $form['#attributes']['class'][] = 'step-incomplete';
    }

    return $form;
  }

}
