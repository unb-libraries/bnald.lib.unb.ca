<?php

namespace Drupal\bnald_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ldap_user\Processor\DrupalUserProcessor;

/**
 * Class MigrateLdapUserEventSubscriber.
 *
 * @package Drupal\bnald_migrate\EventSubscriber
 */
class PostMigrateUserEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::POST_ROW_SAVE => 'onPostSaveRow',
    ];
  }

  /**
   * If the migrated row stems from a user migration, find an LDAP login for it.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The triggering event.
   */
  public function onPostSaveRow(MigratePostRowSaveEvent $event) {
    if ($this->isLdapUserMigration($event->getMigration())) {
      $user = $this->getUser($event);
      if (!$this->tryAssociateLdap($user)) {
        $event->logMessage('User ' . $user . ' has been migrated, but could not be associated with an LDAP entry.', 'warning');
      }
    }
  }

  /**
   * Try associating the user account with the given login ID to LDAP.
   *
   * @param string $loginId
   *   The login ID to search for.
   *
   * @return bool
   *   True if loginId could be matched to an LDAP entry. False otherwise.
   */
  protected function tryAssociateLdap(string $loginId) {
    $userProcessor = new DrupalUserProcessor();
    return $userProcessor->ldapAssociateDrupalAccount($loginId);
  }

  /**
   * Determine whether the given migration migrates user data.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration in question.
   *
   * @return bool
   *   True if the migration is tagged with 'User' or 'user'. False otherwise.
   */
  protected function isLdapUserMigration(MigrationInterface $migration) {
    return in_array('LDAP User', $migration->getMigrationTags());
  }

  /**
   * Extract the user login ID from the triggering event.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The triggering event.
   *
   * @return string|null
   *   The login ID, if one exists. Null otherwise.
   */
  protected function getUser(MigratePostRowSaveEvent $event) {
    return $event->getRow()->getSourceProperty('name');
  }

}
