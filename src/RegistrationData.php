<?php

namespace Drupal\commerce_rng;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\RegistrationInterface;

/**
 * Service for managing registration data.
 */
class RegistrationData implements RegistrationDataInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The factory for querying entities.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The registration manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrationManager;

  /**
   * The registrant manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $registrantManager;

  /**
   * RegistrationData object constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueryFactory $query_factory,
    EventManagerInterface $event_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    $this->eventManager = $event_manager;
    $this->registrationManager = $this->entityTypeManager->getStorage('registration');
    $this->registrantManager = $this->entityTypeManager->getStorage('registrant');
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderRegistrations(OrderInterface $order) {
    // Get all order items id's.
    $order_item_ids = array_column($order->order_items->getValue(), 'target_id');

    // Get all registrations referring these order id's.
    $registration_ids = $this->queryFactory->get('registration')
      ->condition('field_order_item', $order_item_ids, 'IN')
      ->execute();
    krsort($registration_ids);

    $registrations = $this->registrationManager->loadMultiple($registration_ids);
    return $this->loadRegistrationData($registrations);
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrationByOrderItemId($order_item_id) {
    // Get all registrations referring these order id's.
    $registration_ids = $this->queryFactory->get('registration')
      ->condition('field_order_item', $order_item_id)
      ->execute();

    if (!empty($registration_ids)) {
      $registration_id = reset($registration_ids);
      return $this->registrationManager->load($registration_id);
    }
  }

  /**
   * Returns the order item's product if the product is a RNG event.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item to check for.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product entity if it is an event, or null.
   */
  public function orderItemGetEvent(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();
    if ($purchased_entity instanceof ProductVariationInterface) {
      $product = $purchased_entity->getProduct();
      if ($product && $this->eventManager->isEvent($product)) {
        return $product;
      }
    }
  }

  /**
   * Builds registrant list per order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface
   *   The order to build a registrant list for.
   *
   * @return []
   *   A drupal render array.
   */
  public function buildRegistrantLists(OrderInterface $order) {
    // Get registrants per order item.
    $registrants_per_order_item = [];
    foreach ($order->getItems() as $item) {
      $order_item_id = $item->id();
      $registration = $this->getRegistrationByOrderItemId($order_item_id);
      if ($registration) {
        foreach ($registration->getRegistrants() as $registrant) {
          $identity = $registrant->getIdentity();
          if ($identity) {
            $registrants_per_order_item[$order_item_id][$registrant->id()] = $identity->label();
          }
          else {
            $registrants_per_order_item[$order_item_id][$registrant->id()] = $registrant->label();
          }
        }
      }
    }

    $list = [];
    if (!empty($registrants_per_order_item)) {
      foreach ($registrants_per_order_item as $order_item_id => $registrant_list) {
        $list[$order_item_id]['registrants'] = [
          '#theme' => 'item_list',
          '#title' => t('Registrants'),
          '#items' => $registrant_list,
        ];
      }
    }

    return $list;
  }

  /**
   * Returns the order item from the registration.
   *
   * @param \Drupal\rng\RegistrationInterface
   *   The registration entity.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface|null
   *   The order item associated with the registration or null, if the registration
   *   does not have an order item.
   */
  public function registrationGetOrderItem(RegistrationInterface $registration) {
    if ($registration->hasField('field_order_item')) {
      $items = $registration->field_order_item->referencedEntities();
      return reset($items);
    }
  }

  /**
   * Updates the order item quantity based on the number of registrants for this item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item to update.
   */
  public function orderItemUpdateQuantity(OrderItemInterface $order_item) {
    $registration = $this->getRegistrationByOrderItemId($order_item->id());
    if ($registration) {
      $quantity = count($registration->getRegistrantIds());
      $order_item->setQuantity($quantity);
    }
    elseif ($this->orderItemGetEvent($order_item)) {
      // If no registration for this item is known, the quantity is always one.
      $order_item->setQuantity(1);
    }
  }

  /**
   * @return array
   */
  private function loadRegistrationData($registrations) {
    $data = [];

    foreach ($registrations as $registration) {
      $registration_type = $registration->get('type')->referencedEntities()[0];
      $conference = $registration->get('event')->referencedEntities()[0];
      $order_item = $registration->get('field_order_item')->referencedEntities()[0];
      $order = $order_item->getOrder();
      $product_variation = $order_item->getPurchasedEntity();
      $product_variation_type = ProductVariationType::load($product_variation->bundle());

      $billing_profile = null;
      if ($order->getBillingProfile()) {
        $billing_profile = $order->getBillingProfile()->get('address')[0];
      }

      $general_data = [
        'order_id' => $order->getOrderNumber(),
        'order_data' => $order->getCreatedTime(),
        'conference_id' => $conference->id(),
        'conference_name' => $conference->getTitle(),
        'registration_id' => $registration->id(),
        'registration_type' => $registration_type->label,
        'order_item_id' => $order_item->id(),
        'product_variation_id' => $product_variation->id(),
        'product_variation_title' => $product_variation->getTitle(),
        'product_variation_type' => $product_variation->bundle(),
        'product_variation_type_title' => $product_variation_type->label(),
        'registrant_company' => $billing_profile ? $billing_profile->getOrganization() : '',
      ];

      // get the registrants
      $registrant_ids = \Drupal::entityQuery('registrant')
        ->condition('registration', $registration->id())
        ->execute();

      $registrants = $this->registrantManager->loadMultiple($registrant_ids);

      foreach ($registrants as $registrant) {
        $registrant_id = $registrant->id();
        $data[$registrant_id] = $general_data + [
          'registrant_id' => $registrant_id,
        ];

        $identity = $registrant->getIdentity();
        if ($identity) {
          $data[$registrant_id] += [
            'registrant_identity_id' => $identity->id(),
            'registrant_identity_type' => $identity->getEntityTypeId(),
            'registrant_label' => $identity->label(),
          ];
        }
        else {
          $data[$registrant_id] += [
            'registrant_identity_id' => 0,
            'registrant_identity_type' => '',
            'registrant_label' => t('Unknown'),
          ];
        }
      }
    }

    return $data;
  }
}
