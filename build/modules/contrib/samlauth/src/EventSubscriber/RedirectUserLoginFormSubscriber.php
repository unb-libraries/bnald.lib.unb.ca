<?php

namespace Drupal\samlauth\EventSubscriber;

use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber that redirects the user login route to the SAML login.
 */
class RedirectUserLoginFormSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * Constructs a RedirectUserLoginFormSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function __construct(ConfigFactoryInterface $configFactory, RouteMatchInterface $routeMatch, AccountInterface $account) {
    $this->configFactory = $configFactory;
    $this->routeMatch = $routeMatch;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['redirectLogin'];
    return $events;
  }

  /**
   * Redirect the user login form to the SSO provider.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function redirectLogin(RequestEvent $event) {
    if ($this->account->isAnonymous() && $this->routeMatch->getRouteName() === 'user.login') {
      $config = $this->configFactory->get('samlauth.authentication');
      if ($config->get('login_auto_redirect')) {
        // @todo instead of this, directly execute login() and set the response.
        //   Needs 1) !preg_match('[/(saml|user)/(log|reauth)]' in controller;
        //   2) first make all responses cacheable [#3211536]
        $saml_login_url = Url::fromRoute('samlauth.saml_controller_login');
        $redirect = new CacheableRedirectResponse($saml_login_url->toString());
        $redirect->addCacheableDependency($config);
        $event->setResponse($redirect);
      }
    }
  }

}
