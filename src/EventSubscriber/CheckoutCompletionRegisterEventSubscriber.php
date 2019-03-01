<?php

namespace Drupal\commerce_rng\EventSubscriber;

use Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for acting upon account creation during checkout.
 */
class CheckoutCompletionRegisterEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // The class is only available since Commerce 8.x-2.12.
    if (class_exists('Drupal\commerce_checkout\Event\CheckoutEvents') && defined('Drupal\commerce_checkout\Event\CheckoutEvents::COMPLETION_REGISTER')) {
      $events[CheckoutEvents::COMPLETION_REGISTER][] = 'checkoutComplete';
    }

    return $events;
  }

  /**
   * Reacts on accounts being created.
   *
   * Loops through all registrants for an order and sets owner on the
   * registrant's persons.
   *
   * @param \Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent $event
   *   The completion register event.
   */
  public function checkoutComplete(CheckoutCompletionRegisterEvent $event) {
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
