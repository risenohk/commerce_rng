<?php

namespace Drupal\Tests\commerce_rng\FunctionalJavascript;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\profile\Entity\Profile;

/**
 * Tests checkout.
 *
 * @group commerce_rng
 */
class CheckoutTest extends CommerceRngJavascriptTestBase {

  /**
   * Basic test.
   */
  public function test() {
    $this->drupalLogout();
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Event registration');

    // Save first registrant.
    $this->clickLink('Add registrant');
    $this->assertSession()->waitForElementVisible('css', '#drupal-modal');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 1',
      'person[field_email][0][value]' => 'person1@example.com',
      'field_comments[0][value]' => 'No commments',
    ], 'Save');
    $this->waitForElementNotVisible('css', '#drupal-modal');

    // Add second registrant.
    $this->clickLink('Add registrant');
    $this->assertSession()->waitForElementVisible('css', '#drupal-modal');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 2',
      'person[field_email][0][value]' => 'person2@example.com',
    ], 'Save');
    $this->waitForElementNotVisible('css', '#drupal-modal');

    // Add third registrant and continue to order information.
    $this->clickLink('Add registrant');
    $this->assertSession()->waitForElementVisible('css', '#drupal-modal');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 3',
      'person[field_email][0][value]' => 'person3@example.com',
    ], 'Save');
    $this->waitForElementNotVisible('css', '#drupal-modal');

    $this->submitForm([], 'Continue');

    // Add order information.
    $this->assertSession()->pageTextContains('3 items');
    $this->processOrderInformation();

    // Review.
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->assertSession()->pageTextContains('Person 1');
    $this->assertSession()->pageTextContains('Person 2');
    $this->assertSession()->pageTextContains('Person 3');
    // Finalize order.
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');

    // Assert that three registrants were added to the order.
    $order = Order::load(1);
    $registrations = $this->container->get('commerce_rng.registration_data')->getOrderRegistrations($order);
    $registrants = $this->container->get('commerce_rng.registration_data')->formatRegistrationData($registrations);
    $this->assertCount(3, $registrants);
  }

}
