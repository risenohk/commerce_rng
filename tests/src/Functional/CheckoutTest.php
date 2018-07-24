<?php

namespace Drupal\Tests\commerce_rng\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\profile\Entity\Profile;

/**
 * Tests checkout.
 *
 * @group commerce_rng
 */
class CheckoutTest extends CommerceRngBrowserTestBase {

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

    // Assert that this person profile is now owned by the current logged in
    // user.
    $person = Profile::load(1);
    // Assert that we are checking the expected person.
    $this->assertEquals('Person 1', $person->field_name->value);
    $this->assertEquals($this->adminUser->id(), $person->getOwnerId());
  }

  /**
   * Tests new persons choosable for *not* logged in customers.
   *
   * When a not logged in customer inserts a new person for an event, that
   * person should become choosable for the next event for the same order.
   */
  public function testNewPersonChoosableNewCustomers() {
    $product2 = $this->createEventWithVariation($this->store);

    // Log out.
    $this->drupalLogout();

    // Add 2 products to the cart.
    $this->addProductToCart($this->product);
    $this->addProductToCart($product2);

    // Checkout as guest.
    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Event registration');

    // Save first registrant.
    $this->clickLink('Add registrant');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 1',
      'person[field_email][0][value]' => 'person1@example.com',
      'field_comments[0][value]' => 'Comment 1',
    ], 'Save');

    // Add registrant for second event.
    $this->clickLink('Add registrant', 1);
    $this->assertSession()->pageTextContains('Person 1');

    // Select person 1.
    $this->click('#edit-people-list .form-submit');
    $this->submitForm([
      'field_comments[0][value]' => 'Comment 2',
    ], 'Save');
    $this->submitForm([], 'Continue');

    // Add order information.
    $this->assertSession()->pageTextContains('2 items');
    $this->processOrderInformation();

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

    // Assert registrants that were added to the order.
    $order = Order::load(1);
    $registrations = $this->container->get('commerce_rng.registration_data')->getOrderRegistrations($order);
    $registrant1 = $registrations[1]->getRegistrants()[1];
    $registrant2 = $registrations[2]->getRegistrants()[2];

    // Assert that persons of both registrants are exactly the same.
    $this->assertSame($registrant1->getIdentity(), $registrant2->getIdentity());

    // Assert that comments of registrant differ.
    $this->assertEquals('Comment 1', $registrant1->field_comments->value);
    $this->assertEquals('Comment 2', $registrant2->field_comments->value);
  }

  /**
   * Tests new persons ownership for *not* logged in customers.
   *
   * When an anonymous user adds a new person for an event, that user should
   * then become the owner of the person entity when it creates an account at
   * the end of the process.
   *
   * Requires the following patch:
   * https://www.drupal.org/files/issues/2018-07-06/commerce-checkout-pane-guest-registration-2857157-88.patch
   */
  public function testNewPersonsOwnershipNewCustomers() {
    if (!class_exists('Drupal\commerce_checkout\Event\CheckoutEvents') || !defined('Drupal\commerce_checkout\Event\CheckoutEvents::ACCOUNT_CREATE')) {
      $this->markTestSkipped("The patch 'commerce-checkout-pane-guest-registration-2857157-88.patch' has not been applied to Commerce.");
    }

    // Enable the completion_registration pane.
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->container
      ->get('entity_type.manager')
      ->getStorage('commerce_checkout_flow')
      ->load('event');
    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow_plugin */
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionRegistration $pane */
    $pane = $checkout_flow_plugin->getPane('completion_registration');
    $pane->setConfiguration([]);
    $pane->setStepId('complete');
    $checkout_flow_plugin_configuration = $checkout_flow_plugin->getConfiguration();
    $checkout_flow_plugin_configuration['panes']['completion_registration'] = $pane->getConfiguration();
    $checkout_flow_plugin->setConfiguration($checkout_flow_plugin_configuration);
    $checkout_flow->save();

    $this->drupalLogout();
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Event registration');

    // Add registrant.
    $this->clickLink('Add registrant');
    $this->submitForm([
      'person[field_name][0][value]' => 'Person 1',
      'person[field_email][0][value]' => 'person1@example.com',
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
    $this->assertSession()->pageTextContains('Person 1');
    // Finalize order.
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');

    // Assert that the completion_registration checkout pane is shown.
    $this->assertSession()->pageTextContains('Create an account?');
    // Register.
    $this->submitForm([
      'completion_registration[register][name]' => 'User name',
      'completion_registration[register][password][pass1]' => 'pass',
      'completion_registration[register][password][pass2]' => 'pass',
    ], 'Create my account');
    // Assert that the account was created successfully.
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');

    // Assert ownership created profile.
    $person = Profile::load(1);
    // Assert that we are checking the expected person.
    $this->assertEquals('Person 1', $person->field_name->value);
    $this->assertEquals(3, $person->getOwnerId());
  }

  /**
   * Tests adding a new person in combination with person list.
   *
   * When there are existing persons, first a list of persons is shown where the
   * customer can choose from. At the bottom of the list, there is a button to
   * add a new person. This test tests the behavior for adding a new person in
   * that situation.
   */
  public function testAddNewPersonWithPersonList() {
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

    // Go to add registrant page.
    $this->clickLink('Add registrant');
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
