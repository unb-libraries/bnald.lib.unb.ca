<?php

namespace Drupal\bnald_core\migrate;

/**
 * Helper class providing callables.
 */
class BnaldMigrateHelper {

  /**
   * Create a password hash of the given plain input.
   *
   * @param string $pass
   *   The plain password to hash.
   *
   * @return string
   *   A hashed password.
   */
  public static function hashPass(string $pass) {
    return \Drupal::service('password')->hash(trim($pass));
  }

}
