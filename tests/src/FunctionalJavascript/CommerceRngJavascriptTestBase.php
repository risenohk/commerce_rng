<?php

namespace Drupal\Tests\commerce_rng\FunctionalJavascript;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\commerce_rng\Traits\CommerceRngCommonTrait;

/**
 * Base class for Commerce RNG javascript tests.
 */
abstract class CommerceRngJavascriptTestBase extends WebDriverTestBase {

  use BlockCreationTrait;
  use StoreCreationTrait;
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
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

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

    $this->store = $this->createStore();
    $this->placeBlock('local_tasks_block');
    $this->placeBlock('local_actions_block');
    $this->placeBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    // Change RNG settings.
    $this->setUpRng();

    $this->product = $this->createEventWithVariation($this->store);
  }

  /**
   * Waits for the specified selector to become invisible.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   *
   * @return true|null
   *   True if not found or invisible, NULL otherwise.
   *
   * @see \Behat\Mink\Element\ElementInterface::findAll()
   */
  public function waitForElementNotVisible($selector, $locator, $timeout = 10000) {
    $page = $this->getSession()->getPage();

    $result = $page->waitFor($timeout / 1000, function () use ($page, $selector, $locator) {
      $element = $page->find($selector, $locator);
      if (empty($element) || !$element->isVisible()) {
        return TRUE;
      }
      return NULL;
    });

    return $result;
  }

}
