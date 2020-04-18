<?php

namespace Drupal\commerce_rng\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the registrant delete form.
 *
 * @todo needs update
 */
class RegistrantDeleteForm extends ContentEntityDeleteForm implements AjaxFormInterface, RegistrantFormInterface {

  use AjaxButtonsTrait;

  /**
   * The order to which the registration belongs.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The route matcher, used to retrieve parameters from the route.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RegistrantDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route matcher, used to retrieve parameters from the route.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->setModuleHandler($module_handler);
    $this->order = $route_match->getParameter('commerce_order');
    $this->setEntity($route_match->getParameter('registrant'));
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $identity = $this->entity->getIdentity();
    if (!$identity) {
      return parent::getQuestion();
    }

    return $this->t('Do you want to delete this registration for %person?', [
      '%person' => $identity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.commerce_order.edit_form', ['commerce_order' => $this->order->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $identity = $this->entity->getIdentity();

    return $this->t('The registration for %person has been deleted.', [
      '%person' => $identity->label(),
    ]);
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
    $form['actions']['submit'] += $this->saveButton($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $buttons = parent::actions($form, $form_state);

    $buttons['cancel'] = $this->cancelButton($form, $form_state);

    return $buttons;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Update order.
    $this->order->recalculateTotalPrice();
    $this->order->save();

    // Redirect to the order.
    $url = Url::fromRoute('entity.commerce_order.edit_form', ['commerce_order' => $this->order->id()]);
    $form_state->setRedirectUrl($url);
  }

}
