<?php

namespace Drupal\commerce_rng\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesInterface;

/**
 * Interface for checkout flows supporting isComplete() method.
 */
interface IsStepCompleteInterface extends CheckoutFlowWithPanesInterface {

  /**
   * Returns if the information for the given step is complete.
   *
   * @param string $step_id
   *   The step to check.
   *
   * @return bool
   *   True if all required information is available.
   *   False otherwise.
   */
  public function isStepComplete($step_id);

}
