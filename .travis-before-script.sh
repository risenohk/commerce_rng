#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Apply patches.
cd "$DRUPAL_TI_DRUPAL_DIR/$DRUPAL_TI_MODULES_PATH/rng"
patch -p1 < ../commerce_rng/patches/rng-152-event-type-get-identity-type-entity-form-mode.patch

cd ../commerce
patch -p1 < ../commerce_rng/patches/commerce-checkout-pane-guest-registration-2857157-88.patch
