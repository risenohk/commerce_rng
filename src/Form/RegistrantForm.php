<?php

namespace Drupal\commerce_rng\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for registrant entity edit forms.
 */
class RegistrantForm extends ContentEntityForm implements AjaxFormInterface, RegistrantFormInterface {

  use AjaxButtonsTrait;

  /**
   * The route matcher, used to retrieve parameters from the route.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The order to which the registration belongs.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Helper class for generating registrant forms.
   *
   * @var \Drupal\commerce_rng\Form\RegistrantFormHelperInterface
   */
  protected $registrantFormHelper;

  /**
   * Constructs a new RegistrantForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route matcher, used to retrieve parameters from the route.
   * @param \Drupal\commerce_rng\Form\RegistrantFormHelperInterface $registrant_form_helper
   *   Helper class for generating registrant forms.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    RegistrantFormHelperInterface $registrant_form_helper
  ) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->setModuleHandler($module_handler);
    $this->routeMatch = $route_match;
    $this->registrantFormHelper = $registrant_form_helper;

    $this->initConstruct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('commerce_rng.registrant_form')
    );
  }

  /**
   * Initialize values.
   */
  protected function initConstruct() {
    $this->setEntity($this->routeMatch->getParameter('registrant'));
    $this->order = $this->routeMatch->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_rng_registrant_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrant() {
    return $this->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Persons.
    $form['person'] = [
      '#parents' => array_merge($form['#parents'], ['person']),
      '#weight' => -1,
    ];
    $form['person'] = $this->registrantFormHelper->buildPersonFormByRegistrant($form['person'], $form_state, $this->entity);

    // Submit button.
    $form['actions']['submit'] = $this->saveButton($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Update person.
    $values = $form_state->getValue($form['#array_parents']);
    if (isset($values['person'])) {
      $person = $this->registrantFormHelper->submitPersonForm($form['person'], $form_state);
      $this->entity->setIdentity($person);
      $this->entity->save();
    }

    // Redirect to the order.
    $url = Url::fromRoute('entity.commerce_order.edit_form', ['commerce_order' => $this->order->id()]);
    $form_state->setRedirectUrl($url);
  }

}
