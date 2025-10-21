<?php

namespace Drupal\saml_features\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for saml_features routes.
 */
class LoginController extends ControllerBase {

  /**
   * Provides alternate '/login' url for Saml Authentication.
   *
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to samlauth's login route.
   */
  public function loginPage() {
    return $this->redirect('samlauth.saml_controller_login');
  }

}
