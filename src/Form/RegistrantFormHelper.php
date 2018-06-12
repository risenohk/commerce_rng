<?php

namespace Drupal\commerce_rng\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng\EventManagerInterface;
use Drupal\rng\RegistrantInterface;

/**
 * Helper class for building registrant forms.
 */
class RegistrantFormHelper implements RegistrantFormHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new RegistrantFormHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The event manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventManagerInterface $event_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventManager = $event_manager;
  }

  /**
   * Returns the default identity type for this event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   The entity that acts as the event.
   *
   * @return string[]
   *   An array that consists of the entity type ID and the bundle.
   *
   * @todo make protected again.
   */
  public function getIdentityType(EntityInterface $event) {
    $identity_types = $this->eventManager->getMeta($event)->getCreatableIdentityTypes();
    if (count($identity_types) > 1) {
      throw new \Exception('Multiple identity types is not supported by UKKB Study.');
    }
    foreach ($identity_types as $entity_type_id => $bundles) {
      if (count($bundles) > 1) {
        throw new \Exception('Multiple identity types is not supported by UKKB Study.');
      }
      $bundle = reset($bundles);
      return [$entity_type_id, $bundle];
    }

    return [];
  }

  /**
   * Returns the event entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $registrant
   *   A registrant entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity that acts as the event.
   *
   * @throws \Exception
   *   In case the registration or event does not exist.
   *
   * @todo make protected again.
   */
  public function getEvent(EntityInterface $registrant) {
    $registration = $registrant->getRegistration();
    if (!$registration) {
      throw new \Exception('The registration for this registrant no longer exists.');
    }

    $event = $registration->getEvent();
    if (!$event) {
      throw new \Exception('The event for this registrant no longer exists.');
    }

    return $event;
  }

  /**
   * Creates a new person entity.
   *
   * @param string $entity_type_id
   *   The type of entity to create.
   * @param string $bundle
   *   The entity subtype.
   */
  protected function createPerson($entity_type_id, $bundle) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $new_person_options = [];
    if ($entity_type->getBundleEntityType() !== NULL) {
      // This entity type has bundles.
      $new_person_options[$entity_type->getKey('bundle')] = $bundle;
    }
    return $entity_storage->create($new_person_options);
  }

  /**
   * Creates a new person for the registrant.
   *
   * @param \Drupal\rng\RegistrantInterface $registrant
   *   The registrant to create a person for.
   *
   * @todo make protected.
   */
  public function createPersonForRegistrant(RegistrantInterface $registrant) {
    /** @var \Drupal\Core\Entity\EntityInterface $event */
    $event = $this->getEvent($registrant);

    $person = $registrant->getIdentity();
    if (!$person) {
      list($person_entity_type_id, $person_bundle) = $this->getIdentityType($event);
      $person = $this->createPerson($person_entity_type_id, $person_bundle);
    }

    return $person;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRegistrantForm(array $form, FormStateInterface $form_state, RegistrantInterface $registrant) {
    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    // Registrant form.
    $display = entity_get_form_display('registrant', $registrant->bundle(), 'default');
    $display->buildForm($registrant, $form, $form_state);
    $form_state->set('registrant__form_display', $display);
    $form_state->set('registrant__entity', $registrant);

    // Person form.
    $form['person'] = [
      '#parents' => array_merge($form['#parents'], ['person']),
      '#weight' => -1,
    ];
    $form['person'] = $this->buildPersonFormByRegistrant($form['person'], $form_state, $registrant);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitRegistrantForm(array &$form, FormStateInterface $form_state) {
    // Set registrant values.
    $display = $form_state->get('registrant__form_display');
    $registrant = $form_state->get('registrant__entity');
    $display->extractFormValues($registrant, $form, $form_state);

    // Set person values.
    $values = $form_state->getValue($form['#array_parents']);
    if (isset($values['person'])) {
      $person = $this->submitPersonForm($form['person'], $form_state);
      $registrant->setIdentity($person);
    }

    // Save registrant.
    $registrant->save();

    return $registrant;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPersonFormByRegistrant(array $form, FormStateInterface $form_state, RegistrantInterface $registrant) {
    /** @var \Drupal\Core\Entity\EntityInterface $event */
    $event = $this->getEvent($registrant);

    $person = $registrant->getIdentity();
    if (!$person) {
      list($person_entity_type_id, $person_bundle) = $this->getIdentityType($event);
      $person = $this->createPerson($person_entity_type_id, $person_bundle);
    }
    $form = $this->buildPersonForm($form, $form_state, $event, $person);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPersonForm(array $form, FormStateInterface $form_state, EntityInterface $event, EntityInterface $person) {
    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    $form_mode = $this->eventManager->getMeta($event)
      ->getEventType()
      ->getIdentityTypeEntityFormMode($person->getEntityTypeId(), $person->bundle());

    $display = entity_get_form_display($person->getEntityTypeId(), $person->bundle(), $form_mode);
    $display->buildForm($person, $form, $form_state);
    $form_state->set('person__form_display', $display);
    $form_state->set('person__entity', $person);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPersonForm(array &$form, FormStateInterface $form_state) {
    $display = $form_state->get('person__form_display');
    $person = $form_state->get('person__entity');
    $display->extractFormValues($person, $form, $form_state);
    $person->save();

    return $person;
  }

}
