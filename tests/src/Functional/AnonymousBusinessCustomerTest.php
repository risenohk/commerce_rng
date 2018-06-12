<?php

namespace Drupal\Tests\commerce_rng\Functional;

/**
 * Tests for an anonymous user checking out as business customer.
 *
 * @group commerce_rng
 */
class AnonymousBusinessCustomerTest extends CommerceRngBrowserTestBase {

  /**
   * Basic test.
   */
  public function test() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Event registration');

    // Save first registrant.
    $this->submitForm([
      'registrant_information[1][registrant][person][field_name][0][value]' => 'Person 1',
      'registrant_information[1][registrant][person][field_email][0][value]' => 'person1@example.com',
      'registrant_information[1][registrant][field_comments][0][value]' => 'No commments',
    ], 'Save');

    // Add second registrant.
    $this->submitForm([], 'Add another registrant');
    $this->submitForm([
      'registrant_information[1][registrant][person][field_name][0][value]' => 'Person 2',
      'registrant_information[1][registrant][person][field_email][0][value]' => 'person2@example.com',
    ], 'Save');

    // Add third registrant and continue to order information.
    $this->submitForm([], 'Add another registrant');
    $this->submitForm([
      'registrant_information[1][registrant][person][field_name][0][value]' => 'Person 3',
      'registrant_information[1][registrant][person][field_email][0][value]' => 'person3@example.com',
    ], 'Continue');

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
    // $this->assertSession()->pageTextContains('Person 1');
    // $this->assertSession()->pageTextContains('Person 2');
    // $this->assertSession()->pageTextContains('Person 3');
    // Finalize order.
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');

    // @todo Assert that three registrants were added to the order.
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
