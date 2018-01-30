<?php

namespace Drupal\commerce_rng;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Interface for dealing with registrations on orders.
 */
interface RegistrationDataInterface {

  /**
   * Returns all registrations.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to find all registrations for.
   *
   * @return []
   *   The registration data.
   */
  public function getOrderRegistrations(OrderInterface $order);

  /**
   * Returns a single registration for the given order item ID.
   *
   * @param int $order_item_id
   *   The ID of the order item to find a registration for.
   *
   * @return \Drupal\rng\Entity\Registration|null
   *   A registration entity, if found. Null otherwise.
   */
  public function getRegistrationByOrderItemId($order_item_id);

}
