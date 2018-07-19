<?php

/**
 * @file
 * API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of persons that can be chosen at checkout.
 *
 * Commerce RNG allows a customer to select existing persons to use as
 * registrant for an event. This hook allows you to alter the list. It could for
 * example be useful to remove persons from the list or to sort them.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $persons
 *   A list of persons from which the customer can choose.
 * @param \Drupal\commerce_order\Entity\OrderInterface $order
 *   The active commerce order.
 * @param \Drupal\rng\RegistrationInterface $registration
 *   The registration for which the customer is adding a registrant.
 */
function hook_commerce_rng_persons_list_alter(array &$persons, \Drupal\commerce_order\Entity\OrderInterface $order, \Drupal\rng\RegistrationInterface $registration) {
  // Example 1: remove inactive persons from the list.
  foreach ($persons as $person_id => $person) {
    if (!$person->isActive()) {
      unset($persons[$person_id]);
    }
  }

  // Example 2: apply sorting.
  uasort($persons, function ($a, $b) {
    return strcmp($a->field_name->value, $b->field_name->value);
  });
}

/**
 * @} End of "addtogroup hooks".
 */
