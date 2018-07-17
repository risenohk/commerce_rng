<?php

namespace Drupal\commerce_rng_test_profile_label\EventSubscriber;

use Drupal\profile\Event\ProfileEvents;
use Drupal\profile\Event\ProfileLabelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to events from the profile module.
 */
class ProfileLabelEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      ProfileEvents::PROFILE_LABEL => ['profileSetLabel', -100],
    ];

    return $events;
  }

  /**
   * Overrides the label of a profile.
   *
   * @param \Drupal\profile\Event\ProfileLabelEvent $event
   *   The profile label event.
   */
  public function profileSetLabel(ProfileLabelEvent $event) {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $event->getProfile();

    switch ($profile->bundle()) {
      case 'person':
        $value = $profile->field_name->value;
        if ($value) {
          $event->setLabel($value);
        }
        break;
    }
  }

}
