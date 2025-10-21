<?php

namespace Drupal\Tests\samlauth\Functional;

use Drupal\samlauth\Controller\SamlController;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use OneLogin\Saml2\Utils as SamlUtils;

/**
 * Semi random tests for the samlauth module.
 *
 * The most important part (login functionality) isn't tested yet.
 *
 * @group samlauth
 */
class SamlTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Modules to Enable.
   *
   * @var array
   */
  protected static $modules = ['samlauth'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the Admin Pages.
   */
  public function testAdminPages() {
    // The form and config systems are already tested well, so only
    // rudimentary testing is done here. This should probably have some tests
    // for the interactive parts (e.g. adding a second certificate) added.
    $web_user = $this->drupalCreateUser(['configure saml']);
    $this->drupalLogin($web_user);
    $this->drupalGet('admin/config/people/saml');
    // These (sub)section titles are referenced in the README.
    // Apparently tab text is not in 'pageTextContains':
    // $this->assertSession()->pageTextContains('Login / Users');
    $this->assertSession()->pageTextContains('User Interface');
    $this->assertSession()->pageTextContains('Drupal Login Using SAML Data');
    $this->assertSession()->pageTextContains('Login / Logout');
    $this->assertSession()->pageTextContains('Attempt to link SAML data to existing Drupal users');
    $this->drupalGet('admin/config/people/saml/saml');
    $this->assertSession()->pageTextContains('Service Provider');
    $this->assertSession()->pageTextContains('Identity Provider');
    $this->assertSession()->pageTextContains('SAML Message Construction');
    $this->assertSession()->pageTextContains('SAML Message Validation');
  }

  /**
   * Tests metadata retrieval.
   */
  public function testMetadata() {
    // Adding just minimal configuration enables getting valid metadata. (This
    // is not evident because of checks/code structure of the SAML PHP Toolkit.)
    $minimal_sp_config = [
      'sp_entity_id' => 'samlauthEntityId',
      'sp_x509_certificate' => 'MIIDozCCAoqgAwIBAgIBADANBgkqhkiG9w0BAQ0FADBrMQswCQYDVQQGEwJ1czEOMAwGA1UECAwFSWRhaG8xFTATBgNVBAoMDGN3ZWFnYW5zLm5ldDEVMBMGA1UEAwwMY3dlYWdhbnMubmV0MR4wHAYJKoZIhvcNAQkBFg9tZUBjd2VhZ2Fucy5uZXQwHhcNMTUwNjIzMjAwMjMyWhcNMjUwNjIwMjAwMjMyWjBrMQswCQYDVQQGEwJ1czEOMAwGA1UECAwFSWRhaG8xFTATBgNVBAoMDGN3ZWFnYW5zLm5ldDEVMBMGA1UEAwwMY3dlYWdhbnMubmV0MR4wHAYJKoZIhvcNAQkBFg9tZUBjd2VhZ2Fucy5uZXQwggEjMA0GCSqGSIb3DQEBAQUAA4IBEAAwggELAoIBAgDDZOeQF9Cp5k0WzNBye9S/3FgKxTZjcAPBFLtMMAhcx9+kLYMwS5J5h1OUKQcaoxmz/MiVKnrnozStdOKYIeS0C+8DmjRPjKEva77RYEy/Zu4l2Y+Nijt9/OMrO2JwuchHI9Xx+rqifDCR9rJ4vwbu/6/NhTVggSgsDsxlgGtLWC1zoUmwtcBe30t63P1eDrNAEg5EkM3y6OCsx6HaK7nAJmGaF6of/60UmEXB6qBVgZlU/qUmrVX89EdGvPrKWvYJcX3xAcIQh/on/1e/XmGMRYnBB6E0qyx6sL0ZmHzwH5jIUR5S1xwqWhSAjlOUHLSg2tYfHx0dn3UV2koY9QsKEQIDAQABo1AwTjAdBgNVHQ4EFgQUTJG5GAzq0olNiSfg7c/zjaBHnwcwHwYDVR0jBBgwFoAUTJG5GAzq0olNiSfg7c/zjaBHnwcwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQ0FAAOCAQIAsaxgtTmqQLbNETa7pLD0q0qCU7awxUUfwM73CwW+uIoZXeTz4YP6qaQ9T8kbBjyQKK9jmvTCHHm7y7U7a6NTj3DHuGpjo6mOXNMeh259iCSOfxpm2hMnUsLuQk3dM1+POJWSRQ8LFUcx9WT7siqvKfKq8cTjul7DMRR3MWOAgcd6N+ru3UYsuU81M0Gar417va+GkdMhoRBGT/K6jz9dkvOdQWmIlitYGvkQ6o7VGM5wB9J9wZ/A5FDJ0/IxGXJDD8aFtst8RTBwf8Cgptbmmycu9jqrby4bDLQ5ygviBZ7ZvXt4c9pOPWtXxvloilUNM992EVFyiJTTg9yniXcZSsU=',
      'sp_private_key' => 'MIIEwQIBADANBgkqhkiG9w0BAQEFAASCBKswggSnAgEAAoIBAgDDZOeQF9Cp5k0WzNBye9S/3FgKxTZjcAPBFLtMMAhcx9+kLYMwS5J5h1OUKQcaoxmz/MiVKnrnozStdOKYIeS0C+8DmjRPjKEva77RYEy/Zu4l2Y+Nijt9/OMrO2JwuchHI9Xx+rqifDCR9rJ4vwbu/6/NhTVggSgsDsxlgGtLWC1zoUmwtcBe30t63P1eDrNAEg5EkM3y6OCsx6HaK7nAJmGaF6of/60UmEXB6qBVgZlU/qUmrVX89EdGvPrKWvYJcX3xAcIQh/on/1e/XmGMRYnBB6E0qyx6sL0ZmHzwH5jIUR5S1xwqWhSAjlOUHLSg2tYfHx0dn3UV2koY9QsKEQIDAQABAoIBAUs9S728rej+eajR7WJoNKA8pNpg3nSj6Y4sAYNw64dun7uEmwO51gleBt0Cf23OaFNaf5KQ7QrNWbeBTs/uHTcHcV4dvw7yxA6SmsPdJTB+3i1M/W4vUIFPI9q930YxA+IA9p1bQwrWb42FRWwhgvX9FyE4rjkfAu0UNbjQHoDAxNFHHW2OZm9DFtZE8Y3qFFLXjnwl2acFncexDbY0A9vVR+ldpTruz7LQXRmAhozXmVnRtzMlDWDB0hjUQJYIAuue6tTHuD6VcxGLKYUgfB4AZ8IkvD2cbky38omll3KvaPbtOJFGNsBaqt0PVrZv//iZQHgIZe2roKNGBpUNA/7BAoGBD0H/eeG8KoFjLyqLLlBDndHPb3aURJGqf/rFuooK29NBn5n30cftFv7mXc+RA9/SBbIrX5+0p6Nez4SuJLYfVHBzDF9YOVzQbDUWZZWIUtR0yBSl2WAFEET7jyofzXTKOCo2eFrnWj5a2Q0xEFj11f7q83pbdQ8HvbUdi+roaRCtAoGBDM5hrDc4CI8yR6La9fAtA4bkQ/SxiDwfh/Xnjg0eSiVIs5GeGCAJEIeNoZE7n9BQHhH5m0HyjHMnzMfLyexhW6/xHAktEvcEWZMBBIBTbXsGn/f4yKiyfLCsdoLtQIBBQTpYAXwbqVjE+L6xgK/noFdDV17XZcYbPk6xr+f6Hnd1AoGBCQi2z9/Mng5GP+MczatQnd1gyUqYt5DYNzawZKbfjxEixfFQPrH1u6vpkpoX7wdTP3QjIldZi/i7ZnvU8H+1RTXfqO+7ORuvfKJiRHupYAHTs7QmDvM/jEaL/FSgx/Hi2iaEYfbRDSnmeKXK6zcBOFfbnZZRGJpxpu3aNMI+IhdxAoGBC2RplWeGDG8+3mVs/k6L7NBKLn32lOhPcIb8V+0pnfIvC7el+XY+OhssjqeBcDlDnIyHDWwMVo92v4CZtOb48TTCfBtZor5mez0AMb3q+cDw8swI4JDaP3x33/G3F6NA6cL6WU/L18nlaBdUFtPlbUlT2dzAJ4Sl5bbh8UefxQylAoGBAKP0QllPVH3cAKcplu/vTm3Brw6cejhwUX21qBspHJDQcjbqQMqI4xdcY7oYwBGazgPKnBOgiRqSg4018dcJySL5tHneGuTXHVfp+4FznlOQKxRg7I6e/KUOzRSsLy49KlGs9OmuACe0MOTboDIn00mzUnxdmk4qsq34KaqJ4w5G',
      'sp_name_id_format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    ];
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')
      ->getEditable('samlauth.authentication');
    $config->setData($minimal_sp_config)->save();

    // Metadata is not reachable by default.
    $unauth_user = $this->drupalCreateUser();
    $this->drupalLogin($unauth_user);
    $this->drupalGet('saml/metadata');
    $this->assertSession()->statusCodeEquals(403);

    // Correct metadata is returned for other user.
    $web_user = $this->drupalCreateUser(['view sp metadata']);
    $this->drupalLogin($web_user);
    $this->drupalGet('saml/metadata');
    $this->validateXml();

    // On a subsequent request, the response is cached. (In the dynamic page
    // cache, because I happened to see headers to test this. We could also
    // test that the response is cached for anonymous users in the regular
    // page cache; one of the two feels like enough, for this XML response.)
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->drupalGet('saml/metadata');
    $this->validateXml();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    // When the config is edited, the XML is de-cached.
    $config->setData($minimal_sp_config + ['metadata_valid_secs' => 24 * 3600 * 5])->save();
    $this->drupalGet('saml/metadata');
    $this->validateXml();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');

    // Error (invalid_xml in this case)
    // @todo we likely want to make this a 500 (or so) retunr code with a short
    //   plaintext or XML body, instead. Adjust this test if we do.
    $config->setData(['sp_x509_certificate' => "#Let'st"] + $minimal_sp_config)->save();
    $this->drupalGet('saml/metadata');
    $webassert = $this->assertSession();
    $webassert->statusCodeEquals(200);
    $webassert->responseHeaderExists('Content-Type');
    $webassert->responseHeaderNotMatches('Content-Type', '[^text/xml;]');

    // When explicitly not validating XML, it does get returned.
    $this->drupalGet('saml/metadata', ['query' => ['check' => '0']]);
    $webassert = $this->assertSession();
    $webassert->statusCodeEquals(200);
    $webassert->responseHeaderExists('Content-Type');
    $webassert->responseHeaderMatches('Content-Type', '[^text/xml;]');
  }

  /**
   * Validates that metadata is valid XML and returns it as DomDocument.
   *
   * @return \DOMDocument
   */
  protected function validateXml() {
    $webassert = $this->assertSession();
    $webassert->statusCodeEquals(200);
    $webassert->responseHeaderExists('Content-Type');
    $webassert->responseHeaderMatches('Content-Type', '[^text/xml;]');
    // Looks like all the session objects can only interpret HTML (elements
    // have '//html' hardcoded in getXpath())? Load XML ourxelves, using a
    // helper method that also validates against the SAML schema.
    $dom = SamlUtils::validateXML($this->getSession()->getPage()->getContent(), 'saml-schema-metadata-2.0.xsd');
    $this->assertTrue($dom instanceof \DOMDocument, 'XML not valid; validator method returned: ' . var_export($dom, TRUE));
    // Assume a single entity descriptor, for the SP.
    $root_node_attr = SamlUtils::query($dom, '//md:EntityDescriptor')->item(0)->attributes;
    $this->assertEquals('samlauthEntityId', $root_node_attr->getNamedItem('entityID')->value);

    return $dom;
  }

  /**
   * Tests UI changes to login screen, excluding login-failure behavior.
   */
  public function testLoginUI() {
    // Assumption: our imported config has no values for title. Default
    // is to show no link.
    $config = \Drupal::configFactory()->getEditable(SamlController::CONFIG_OBJECT_NAME);
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->assertSession()->elementNotExists('css', '.samlauth-auth-login-link');

    $link_title = 'Log in using SAML IdP';
    $config->set('login_link_title', $link_title)->save();
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->assertSession()->elementTextContains('css', '.samlauth-auth-login-link', $link_title);
  }

  /**
   * Tests behavior of password reset / login screen.
   */
  public function testPasswordReset() {
    $core_msg_mail_sent = 'an email will be sent with instructions to reset your password.';
    $mails = $this->drupalGetMails();
    $initial_count_mails = count($mails);
    $config = \Drupal::configFactory()->getEditable(SamlController::CONFIG_OBJECT_NAME);

    $web_user = $this->drupalCreateUser();
    $this->drupalLogin($web_user);

    // Baseline: The 'real' error about being a SAML user is suppressed.
    $this->assertEquals(FALSE, $config->get('local_login_saml_error'), "'local_login_saml_error' config must be FALSE.");

    // Baseline: un-linked users can still reset their password.
    $this->drupalGet('user/password');
    $this->submitForm([], 'Submit');
    $this->assertSession()->responseContains($core_msg_mail_sent);
    $mails = $this->drupalGetMails();
    $this->assertEquals($initial_count_mails + 1, count($mails));

    // Linked users only can if a role-based config value says they can. They
    // do not see a message about this by default, but the mail is not sent.
    \Drupal::service('externalauth.authmap')->save($web_user, 'samlauth', $this->randomString());
    $this->drupalGet('user/password');
    $this->submitForm([], 'Submit');
    $this->assertSession()->responseContains($core_msg_mail_sent);
    $mails = $this->drupalGetMails();
    $this->assertEquals($initial_count_mails + 1, count($mails));
    // The user does see an error if the appropriate config value is set.
    $config->set('local_login_saml_error', TRUE)->save();
    $this->drupalGet('user/password');
    $this->submitForm([], 'Submit');
    $this->assertSession()->responseContains('This user is only allowed to log in through an external authentication provider.');
    $this->assertSession()->responseNotContains($core_msg_mail_sent);
    $mails = $this->drupalGetMails();
    $this->assertEquals($initial_count_mails + 1, count($mails));

    // Linked users can reset their password if they have the proper permission.
    \Drupal::configFactory()->getEditable(SamlController::CONFIG_OBJECT_NAME)
      ->set('drupal_login_roles', [RoleInterface::AUTHENTICATED_ID])->save();
    $this->submitForm([], 'Submit');
    $this->assertSession()->responseContains($core_msg_mail_sent);
    $mails = $this->drupalGetMails();
    $this->assertEquals($initial_count_mails + 2, count($mails));

    // The same logic applies to the login form. Test in reverse order: now
    // that the user has the permission, we can log in...
    $this->drupalLogout();
    $this->drupalLogin($web_user);

    // ...but not if we revoke the permission (and the user has an authmap
    // entry). The fact that we see the specific message means that the
    // user/password was actually recognized.
    $this->drupalLogout();
    \Drupal::configFactory()->getEditable(SamlController::CONFIG_OBJECT_NAME)
      ->set('drupal_login_roles', [])->save();
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => $web_user->getAccountName(),
      'pass' => $web_user->passRaw,
    ], t('Log in'));
    $this->assertSession()->responseContains('This user is only allowed to log in through an external authentication provider.');
    // The user sees the general (untrue) "Unrecognized" error if the
    // appropriate config value is not set.
    $config->set('local_login_saml_error', FALSE)->save();
    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => $web_user->getAccountName(),
      'pass' => $web_user->passRaw,
    ], t('Log in'));
    $this->assertSession()->responseNotContains('This user is only allowed to log in through an external authentication provider.');
    $this->assertSession()->responseContains('Unrecognized username or password.');
  }

}
