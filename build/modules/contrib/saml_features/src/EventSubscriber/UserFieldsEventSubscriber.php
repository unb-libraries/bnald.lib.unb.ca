<?php

namespace Drupal\saml_features\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\samlauth\Event\SamlauthEvents;
use Drupal\samlauth\Event\SamlauthUserSyncEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extra field manipulation on SAML attribute Synchronization.
 */
class UserFieldsEventSubscriber implements EventSubscriberInterface {

  /**
   * Name of the phone number field in SAML.
   */
  const SAML_PHONE_ATTRIBUTE_NAME = 'telephoneNumber';

  /**
   * A configuration object containing samlauth field mappings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * UserFieldsEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('samlauth_user_fields.mappings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SamlauthEvents::USER_SYNC][] = ['onUserSync'];
    return $events;
  }

  /**
   * Adjustments to fields being saved.
   *
   * @param \Drupal\samlauth\Event\SamlauthUserSyncEvent $event
   *   The event being dispatched.
   */
  public function onUserSync(SamlauthUserSyncEvent $event) {
    $account = $event->getAccount();
    $attributes = $event->getAttributes();
    $mappings = $this->config->get('field_mappings');

    if ($mappings) {
      foreach ($mappings as $mapping) {
        if ($mapping['attribute_name'] == static::SAML_PHONE_ATTRIBUTE_NAME) {
          $value = !empty($attributes[$mapping['attribute_name']][0]) ? $attributes[$mapping['attribute_name']][0] : NULL;
          if (isset($value)) {
            $value = preg_replace('/^1 (\d{3}) (\d{3}) (\d{4})$/', '($1) $2-$3', $value);
            $account->set($mapping['field_name'], $value);
            $event->markAccountChanged();
          }
        }
      }
    }
  }

}
