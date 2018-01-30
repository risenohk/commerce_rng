<?php

namespace Drupal\commerce_rng\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;

/**
 * Interface for checkout panes supporting isComplete() method.
 */
interface IsPaneCompleteInterface extends CheckoutPaneInterface {

  /**
   * Returns if the information for the current pane is complete.
   *
   * @return bool
   *   True if all required information is available.
   *   False otherwise.
   */
  public function isComplete();

}
