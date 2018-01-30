<?php

namespace Drupal\commerce_rng\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -50];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Deny access to certain routes.
    $routes = [
      'rng.event.commerce_product.register',
      'rng.event.commerce_product.register.type_list',
      'rng.event.commerce_product.group.add',
    ];
    foreach ($routes as $route) {
      $route_object = $collection->get($route);
      if ($route_object) {
        $route_object->setRequirement('_access', 'FALSE');
      }
    }
  }

}
