<?php

namespace Drupal\commerce_rng\Plugin\EntityReferenceSelection;

use Drupal\rng\Plugin\EntityReferenceSelection\RNGSelectionBase;

/**
 * Provides selection for profile entities when registering.
 *
 * @EntityReferenceSelection(
 *   id = "rng:register:commerce_rng_profile",
 *   label = @Translation("Profile selection"),
 *   entity_types = {"profile"},
 *   group = "rng_register",
 *   provider = "rng",
 *   weight = 10
 * )
 */
class ProfileRngSelection extends RNGSelectionBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Select contacts owned by the user.
    if ($this->currentUser->isAuthenticated()) {
      $query->condition('uid', $this->currentUser->id(), '=');
    }
    else {
      // Cancel the query.
      $query->condition($this->entityType->getKey('id'), NULL, 'IS NULL');
      return $query;
    }

    return $query;
  }

}
