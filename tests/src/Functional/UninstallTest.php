<?php

namespace Drupal\Tests\commerce_rng\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module uninstallation.
 *
 * @group commerce_rng
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'commerce_rng',
  ];

  /**
   * Tests module uninstallation.
   *
   * @todo not all installed config is cleaned up upon uninstall.
   */
  public function testUninstall() {
    // Confirm that all Commerce modules have been installed successfully.
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayHasKey($module, $installed_modules, t('Commerce module @module installed successfully.', ['@module' => $module]));
    }

    // First uninstall a part of the modules so fields can be deleted.
    $this->container->get('module_installer')->uninstall([
      'commerce_order',
      'commerce_cart',
      'commerce_checkout',
      'commerce_rng',
    ]);
    $this->rebuildContainer();
    field_purge_batch(50);

    // Now uninstall all other Commerce modules except the base module.
    $modules = ['commerce_price', 'commerce_store', 'commerce_product'];
    $this->container->get('module_installer')->uninstall($modules);
    $this->rebuildContainer();
    // Purge field data in order to remove the commerce_remote_id field.
    field_purge_batch(50);
    // Uninstall the base module.
    $this->container->get('module_installer')->uninstall(['commerce']);
    $this->rebuildContainer();
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayNotHasKey($module, $installed_modules, t('Commerce module @module uninstalled successfully.', ['@module' => $module]));
    }

    $this->markTestIncomplete('Reinstalling has the issue that some config files already exist.');

    // Reinstall the modules. If there was no trailing configuration left
    // behind after uninstall, then this too should be successful.
    $this->container->get('module_installer')->install(self::$modules);
    $this->rebuildContainer();
    $installed_modules = $this->container->get('module_handler')->getModuleList();
    foreach (self::$modules as $module) {
      $this->assertArrayHasKey($module, $installed_modules, t('Commerce module @module reinstalled successfully.', ['@module' => $module]));
    }
  }

}
