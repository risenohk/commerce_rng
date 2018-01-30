<?php

namespace Drupal\commerce_rng\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\rng\RegistrantFactoryInterface;
use Drupal\commerce_rng\Form\RegistrantFormHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the registrant add form.
 */
class RegistrantAddForm extends FormBase implements AjaxFormInterface, RegistrantFormInterface {

  use AjaxButtonsTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The factory for creating a registrant entities.
   *
   * @var \Drupal\rng\RegistrantFactoryInterface
   */
  protected $registrantFactory;

  /**
   * Helper class for generating registrant forms.
   *
   * @var \Drupal\commerce_rng\Form\RegistrantFormHelperInterface
   */
  protected $registrantFormHelper;

  /**
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * @var \Drupal\rng\RegistrationInterface
   */
  protected $registration;

  /**
   * @var \Drupal\rng\RegistrantInterface
   */
  protected $registrant;

  /**
   * Constructs a new RegistrantForm.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    RouteMatchInterface $route_match,
    RegistrantFactoryInterface $registrant_factory,
    RegistrantFormHelperInterface $registrant_form_helper
  ) {

    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->routeMatch = $route_match;
    $this->registrantFactory = $registrant_factory;
    $this->registrantFormHelper = $registrant_form_helper;

    $this->initConstruct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('current_route_match'),
      $container->get('rng.registrant.factory'),
      $container->get('commerce_rng.registrant_form')
    );
  }

  /**
   * Initialize values.
   */
  public function initConstruct() {
    $this->registration = $this->routeMatch->getParameter('registration');
    $this->order = $this->routeMatch->getParameter('commerce_order');

    // Create a new registrant.
    $this->registrant = $this->createRegistrant();
    // If given, attach a person to this registrant.
    $person_id = $this->routeMatch->getParameter('person');
    if ($person_id) {
      $event = $this->registrantFormHelper->getEvent($this->registrant);
      list($person_entity_type_id, $person_bundle) = $this->registrantFormHelper->getIdentityType($event);
      $person = $this->entityTypeManager->getStorage($person_entity_type_id)->load($person_id);
      if ($person) {
        $this->registrant->setIdentity($person);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_rng_registrant_add_form';
  }

  /**
   * Returns wrapper ID for registrant element.
   *
   * @return string
   *   The wrapper ID for the registrant.
   */
  public function getRegistrantWrapperId() {
    $uuid = $this->registrant->uuid->first()->getValue();
    return $uuid['value'];
  }

  /**
   * The url to return to after submit or cancel.
   *
   * @return \Drupal\Core\Url
   *   The return url.
   */
  public function getReturnUrl() {
    return Url::fromRoute('entity.commerce_order.edit_form', ['commerce_order' => $this->order->id()]);
  }

  /**
   * The url to return to after cancel.
   *
   * @return \Drupal\Core\Url
   *   The return url.
   */
  public function getCancelUrl() {
    return $this->getReturnUrl();
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
    return $this->registrant;
  }

  /**
   * Returns a list of existing persons.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   A list of existing persons.
   */
  protected function getPersons() {
    // Get identity entity type.
    $event = $this->registrantFormHelper->getEvent($this->registrant);
    list($person_entity_type_id, $person_bundle) = $this->registrantFormHelper->getIdentityType($event);

    // Get owner.
    $uid = $this->order->getCustomerId();
    if (!$uid) {
      // Do not select existing persons for anonymous customers.
      return [];
    }

    // Find existing identities.
    $storage = $this->entityTypeManager->getStorage($person_entity_type_id);
    $ids = $storage->getQuery()
      ->condition('type', $person_bundle)
      ->condition('uid', $uid)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Creates a new registrant.
   *
   * @return \Drupal\rng\Entity\Registrant
   *   The created registrant.
   */
  protected function createRegistrant() {
    $registrant = $this->registrantFactory->createRegistrant([
      'event' => $this->registration->getEvent(),
    ]);
    $registrant->setRegistration($this->registration);

    return $registrant;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    $identity = $this->registrant->getIdentity();

    if (!$identity) {
      // Wrapper.
      $form['#prefix'] = '<div id="' . $this->getRegistrantWrapperId() . '">';
      $form['#suffix'] = '</div>';

      // Find existing identities.
      $persons = $this->getPersons();
      if (!count($persons)) {
        $person = $this->registrantFormHelper->createPersonForRegistrant($this->registrant);
        $this->registrant->setIdentity($person);
        return $this->buildForm($form, $form_state);
      }

      // Person.
      $form['persons'] = [
        '#parents' => array_merge($form['#parents'], ['persons']),
        '#weight' => -1,
      ];
      $form['persons'] = $this->buildPersonsTable($form['persons'], $form_state, $persons);

      $form['persons']['new'] = [
        '#name' => 'ajax-submit-' . implode('-', $form['persons']['#parents']) . '-' . 'new',
        '#type' => 'submit',
        '#value' => t('New person'),
        '#ajax' => [
          'callback' => [$this, 'ajaxRegistrantElement'],
          'wrapper' => $this->getRegistrantWrapperId(),
        ],
        '#submit' => [
          [$this, 'submitNewPerson'],
        ],
      ];

      // Cancel button.
      $form['actions']['cancel'] = $this->cancelButton($form, $form_state);
    }
    else {
      $form = $this->registrantFormHelper->buildRegistrantForm($form, $form_state, $this->registrant);
      $form['actions'] = $this->actions($form, $form_state);
    }

    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array &$form, FormStateInterface $form_state) {
    $actions = [
      '#weight' => 101,
    ];

    // Submit button.
    $actions['submit'] = $this->saveButton($form, $form_state);

    if (!$this->registrant->isNew() && $this->registrant->hasLinkTemplate('delete-form')) {
      $route_info = $this->registrant->urlInfo('delete-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#access' => $this->registrant->access('delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
      $actions['delete']['#url'] = $route_info;
    }

    $actions['cancel'] = $this->cancelButton($form, $form_state);

    return $actions;
  }

  /**
   * Builds a table of persons to choose from.
   *
   * @param \Drupal\Core\Entity\EntityInterface[]
   *   The person to display in the table.
   *
   * @return []
   *   The form element.
   */
  protected function buildPersonsTable(array $element, FormStateInterface $form_state, array $persons) {
    // Set #parents to 'top-level' by default.
    $element += ['#parents' => []];

    $element['people_list'] = [
      '#type' => 'table',
      '#header' => [
        t('Person'), t('Operations'),
      ],
      '#empty' => t('There are no people yet, add people below.'),
    ];

    foreach ($persons as $i => $person) {
      $row = [];
      $row[]['#markup'] = $person->label();

      $row[] = [
        // Needs a name else the submission handlers think all buttons are the
        // last button.
        '#name' => 'ajax-submit-' . implode('-', $element['#parents']) . '-' . $i,
        '#type' => 'submit',
        '#value' => t('Select'),
        '#ajax' => [
          'callback' => [$this, 'ajaxRegistrantElement'],
          'wrapper' => $this->getRegistrantWrapperId(),
        ],
        '#limit_validation_errors' => [],
        '#submit' => [
          [$this, 'submitSelectPerson'],
        ],
        '#identity_element_registrant_row' => $i,
      ];

      $element['people_list'][] = $row;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->registrantFormHelper->submitRegistrantForm($form, $form_state);

    // Redirect to the order.
    $form_state->setRedirectUrl($this->getReturnUrl());
  }

  /**
   * Submit handler for selecting an existing person.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitSelectPerson(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $row_id = $form_state->getTriggeringElement()['#identity_element_registrant_row'];
    $persons = $this->getPersons();
    if (isset($persons[$row_id])) {
      $this->registrant->setIdentity($persons[$row_id]);
    }
  }

  /**
   * Submit handler for selecting a new person.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitNewPerson(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $person = $this->registrantFormHelper->createPersonForRegistrant($this->registrant);
    $this->registrant->setIdentity($person);
  }

  /**
   * Ajax form callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ajaxRegistrantElement(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
