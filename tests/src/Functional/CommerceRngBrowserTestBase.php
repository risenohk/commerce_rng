<?php

namespace Drupal\Tests\commerce_rng\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce_rng\Traits\CommerceRngCommonTrait;

/**
 * Provides a base class for Commerce functional tests.
 */
abstract class CommerceRngBrowserTestBase extends CommerceBrowserTestBase {

  use CommerceRngCommonTrait;

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
   * A product that can be placed in a cart.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    // Change RNG settings.
    $this->setUpRng();

    $this->product = $this->createEventWithVariation($this->store);
  }

}
