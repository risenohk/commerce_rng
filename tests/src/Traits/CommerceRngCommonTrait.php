<?php

namespace Drupal\Tests\commerce_rng\Traits;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides methods useful for Kernel and Functional Commerce RNG tests.
 *
 * This trait is meant to be used only by test classes.
 */
trait CommerceRngCommonTrait {

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
      'create person profile',
      'update own person profile',
      'view own person profile',
      'delete own person profile',
    ];
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

  /**
   * Sets settings related to RNG for tests.
   */
  protected function setUpRng() {
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
  }

  /**
   * Creates a new product and product variation of type event.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store to create a product for.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The created product.
   */
  protected function createEventWithVariation(StoreInterface $store) {
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'event',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->createEntity('commerce_product', [
      'type' => 'event',
      'title' => $this->randomMachineName(),
      'variations' => [$variation],
      'stores' => [$store],
      'rng_registrants_minimum' => 1,
      'rng_status' => TRUE,
      'rng_registrants_duplicate' => TRUE,
      'rng_registration_type' => ['standard_registration'],
    ]);

    return $product;
  }

  /**
   * Adds the given product to the cart.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product to add to the cart.
   */
  protected function addProductToCart(ProductInterface $product) {
    $this->drupalGet($product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
  }

  /**
   * Proceeds to checkout.
   */
  protected function goToCheckout() {
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
  }

  /**
   * Asserts the current step in the checkout progress block.
   *
   * @param string $expected
   *   The expected value.
   */
  protected function assertCheckoutProgressStep($expected) {
    $current_step = $this->getSession()->getPage()->find('css', '.checkout-progress--step__current')->getText();
    $this->assertEquals($expected, $current_step);
  }

  /**
   * Processes order information step.
   *
   * @param bool $new_customer
   *   Whether or not a new customer is checking out. Defaults to true.
   */
  protected function processOrderInformation($new_customer = TRUE) {
    $edit = [
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ];
    if ($new_customer) {
      $edit += [
        'contact_information[email]' => 'guest@example.com',
      ];
    }

    // Add order information.
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm($edit, 'Continue to review');
  }

}
