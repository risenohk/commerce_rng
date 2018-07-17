<?php

namespace Drupal\Tests\commerce_rng\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Provides a base class for Commerce functional tests.
 */
abstract class CommerceRngBrowserTestBase extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'block',
    'field',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_rng',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    // Change RNG settings.
    $config = \Drupal::configFactory()->getEditable('rng.settings');
    $config->set('identity_types', ['profile']);
    $config->save();

    // Update entity info.
    $entity = $this->container
      ->get('entity_type.manager')
      ->getStorage('event_type')
      ->load('commerce_product.event');
    $entity->setIdentityTypeReference('profile', 'person', TRUE);
    $entity->setIdentityTypeCreate('profile', 'person', TRUE);
    $entity->save();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'event',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'event',
      'title' => 'My event',
      'variations' => [$variation],
      'stores' => [$this->store],
      'rng_registrants_minimum' => 1,
      'rng_status' => TRUE,
      'rng_registrants_duplicate' => TRUE,
      'rng_registration_type' => ['standard_registration'],
    ]);
  }

}
