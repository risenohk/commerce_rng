<?php

namespace Drupal\Tests\commerce_rng\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Url;
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

  /**
   * Tests adding a registrant without using the popup.
   *
   * This test is useful if custom modules want to disable the popup.
   */
  public function testAddRegistrantWithoutUsingPopup() {
    $this->drupalLogout();
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Event registration');

    // Follow add registrant link directly.
    $url = Url::fromUri('base://registration/1/1/add/nojs', [
      'query' => [
        'destination' => '/checkout/1/event_registration',
      ],
    ]);
    $this->drupalGet($url);
    $this->submitForm([
      'person[field_name][0][value]' => 'Fluxity',
      'person[field_email][0][value]' => 'fluxity@example.com',
    ], 'Save');

    $this->submitForm([], 'Continue');

    // Add order information.
    $this->assertSession()->pageTextContains('1 item');
    $this->processOrderInformation();

    // Review.
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->assertSession()->pageTextContains('Fluxity');
    // Finalize order.
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');

    // Assert that three registrants were added to the order.
    $order = Order::load(1);
    $registrations = $this->container->get('commerce_rng.registration_data')->getOrderRegistrations($order);
    $registrants = $this->container->get('commerce_rng.registration_data')->formatRegistrationData($registrations);
    $this->assertCount(1, $registrants);
  }

  /**
   * Tests adding a registrant without using the popup, but with person list.
   *
   * This test is useful if custom modules want to disable the popup.
   */
  public function testAddRegistrantWithPersonListWithoutUsingPopup() {
    // Add an existing person.
    Profile::create([
      'type' => 'person',
      'field_name' => 'Existing person',
      'field_email' => 'existing_person@example.com',
      'uid' => $this->adminUser->id(),
    ])->save();

    $this->addProductToCart($this->product);
    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Event registration');

    // Follow add registrant link directly.
    $url = Url::fromUri('base://registration/1/1/add/nojs', [
      'query' => [
        'destination' => '/checkout/1/event_registration',
      ],
    ]);
    $this->drupalGet($url);
    // Assert that one person is already shown.
    $this->assertSession()->pageTextContains('Existing person');
    $this->submitForm([], 'New person');

    // Assert that no new profile is created yet.
    $this->assertNull(Profile::load(2));

    // Now, create person.
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 1',
      'person[field_email][0][value]' => 'person1@example.com',
    ], 'Save');

    // Assert that a new profile now exists.
    $person = Profile::load(2);
    $this->assertEquals('Person 1', $person->field_name->value);
    $this->assertEquals($this->adminUser->id(), $person->getOwnerId());

    // Continue checkout.
    $this->submitForm([], 'Continue');
    $this->assertSession()->pageTextContains('1 item');
    $this->processOrderInformation(FALSE);
    // Review.
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->assertSession()->pageTextContains('Person 1');
    // Finalize order.
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');
  }

}
