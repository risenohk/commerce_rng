<?php

namespace Drupal\commerce_rng\Plugin\IdentityChannel\CourierEmail;

use Drupal\courier\Exception\IdentityException;
use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Supports profile entities.
 *
 * @IdentityChannel(
 *   id = "identity:commerce_rng_profile:courier_email",
 *   label = @Translation("profile to courier_mail"),
 *   channel = "courier_email",
 *   identity = "profile",
 *   weight = 10
 * )
 */
class Profile implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {
    if (isset($identity->field_email)) {
      $email = $identity->field_email;
      if (!empty($email->value)) {
        $message->setRecipientName($identity->label());
        $message->setEmailAddress($email->value);
      }
      else {
        throw new IdentityException('Contact missing email address.');
      }
    }
    else {
      throw new IdentityException('Contact type email field not configured.');
    }
  }

}
