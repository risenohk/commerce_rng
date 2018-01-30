<?php

namespace Drupal\commerce_rng\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds breadcrumbs for registrant forms.
 */
class OrderRegistrantBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return strpos($route_match->getRouteName(), 'commerce_rng.registrant_form') === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Admin'), 'system.admin'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Commerce'), 'commerce.admin_commerce'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Orders'), 'entity.commerce_order.collection'));

    // Order.
    $order = $route_match->getParameter('commerce_order');
    $breadcrumb->addLink(new Link($order->label(), $order->toUrl()));
    $breadcrumb->addLink(new Link(t('Edit'), $order->toUrl('edit-form')));

    return $breadcrumb;
  }

}
