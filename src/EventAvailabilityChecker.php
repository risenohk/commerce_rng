<?php

namespace Drupal\commerce_rng;

use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\Context;
use Drupal\rng\EventManagerInterface;

/**
 * Checks if an event is open for registrations.
 *
 * @package Drupal\commerce_rng
 */
class EventAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * The event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new StockAvailabilityChecker object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $entity) {
    // Check if the entity is an event.
    if (!empty($entity) && $product = $entity->getProduct()) {
      return $this->eventManager->isEvent($product);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    /** @var \Drupal\rng\EventMetaInterface|null $meta */
    $meta = $this->eventManager->getMeta($entity->getProduct());
    if (!$meta) {
      // No metadata available.
      return FALSE;
    }

    if (!$meta->isAcceptingRegistrations()) {
      return FALSE;
    }

    // Check for registration types.
    $types = $meta->getRegistrationTypeIds();
    if (empty($types)) {
      // No registration types.
      return FALSE;
    }

    // Check if current user is allowed to register.
    // @todo
  }

}
