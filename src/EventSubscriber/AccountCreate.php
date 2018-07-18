<?php

namespace Drupal\commerce_rng\EventSubscriber;

use Drupal\commerce_checkout\Event\CheckoutAccountCreateEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for acting upon account creation during checkout.
 */
class AccountCreate implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // As of Commerce 8.x-2.8, the availability of the referenced class and
    // constant depends on the patch
    // "commerce-checkout-pane-guest-registration-2857157-88.patch" being
    // applied to Commerce.
    if (class_exists('Drupal\commerce_checkout\Event\CheckoutEvents') && defined('Drupal\commerce_checkout\Event\CheckoutEvents::ACCOUNT_CREATE')) {
      $events[CheckoutEvents::ACCOUNT_CREATE][] = 'checkoutComplete';
    }

    return $events;
  }

  /**
   * Reacts on accounts being created.
   *
   * Loops through all registrants for an order and sets owner on the
   * registrant's persons.
   *
   * @param \Drupal\commerce_checkout\Event\CheckoutAccountCreateEvent $event
   *   The account create event.
   */
  public function checkoutComplete(CheckoutAccountCreateEvent $event) {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $event->getAccount();

    // Assign persons used as registrant to account.
    /** @var \Drupal\rng\RegistrationInterface[] $registrations */
    $registrations = \Drupal::service('commerce_rng.registration_data')->getOrderRegistrations($event->getOrder());
    foreach ($registrations as $registration) {
      $registrants = $registration->getRegistrants();
      /** @var \Drupal\rng\RegistrantInterface $registrant */
      foreach ($registrants as $registrant) {
        $person = $registrant->getIdentity();
        if ($person instanceof EntityOwnerInterface && !$person->getOwnerId()) {
          $person->setOwnerId($account->id());
          $person->save();
        }
      }
    }
  }

}
