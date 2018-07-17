<?php

namespace Drupal\Tests\commerce_rng\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\profile\Entity\Profile;

/**
 * Tests for an anonymous user checking out as business customer.
 *
 * @group commerce_rng
 */
class AnonymousBusinessCustomerTest extends CommerceRngBrowserTestBase {

  /**
   * Basic test.
   *
   * Tests adding three registrants for an event for an anonymous user.
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
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 1',
      'person[field_email][0][value]' => 'person1@example.com',
      'field_comments[0][value]' => 'No commments',
    ], 'Save');

    // Add second registrant.
    $this->clickLink('Add registrant');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 2',
      'person[field_email][0][value]' => 'person2@example.com',
    ], 'Save');

    // Add third registrant and continue to order information.
    $this->clickLink('Add registrant');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 3',
      'person[field_email][0][value]' => 'person3@example.com',
    ], 'Save');
    $this->submitForm([], 'Continue');

    // Add order information.
    $this->assertCheckoutProgressStep('Order information');
    $this->assertSession()->pageTextContains('3 items');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');

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
    $registrants = $this->container->get('commerce_rng.registration_data')->getOrderRegistrations($order);
    $this->assertCount(3, $registrants);
  }

  /**
   * Tests new persons ownership for logged in customers.
   *
   * When a logged in customer adds a new person for an event, that customer
   * should then immediately become the owner of the person entity.
   */
  public function testNewPersonsOwnershipExistingCustomers() {
    $this->addProductToCart($this->product);
    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Event registration');

    // Save first registrant.
    $this->clickLink('Add registrant');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 1',
      'person[field_email][0][value]' => 'person1@example.com',
      'field_comments[0][value]' => 'No commments',
    ], 'Save');

    // Assert that this person profile is now owned by the current logged in user.
    $person = Profile::load(1);
    // Assert that we are checking the expected person.
    $this->assertEquals('Person 1', $person->field_name->value);
    $this->assertEquals($this->adminUser->id(), $person->getOwnerId());
  }

  /**
   * Adds the given product to the cart.
   */
  protected function addProductToCart($product) {
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

}
