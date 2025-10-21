<?php

namespace Drupal\samlauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\samlauth\Controller\SamlController;
use Drupal\samlauth\SamlService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for miscellaneous samlauth module settings.
 *
 * Reads/writes the same config object as SamlauthSamlConfigureForm; the form
 * is split up because configuration options became unwieldy.
 */
class SamlauthConfigureForm extends ConfigFormBase {
  use SamlauthConfigureTrait;

  const MAX_UNCOLLAPSED_ROLES = 10;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The PathValidator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->token = $container->get('token');
    $instance->pathValidator = $container->get('path.validator');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [SamlController::CONFIG_OBJECT_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'samlauth_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The types and labels of all configuration values are defined in the
    // schema.yml; we want to get them from there instead of repeating them.
    // A simple definition array without replacements should suffice for this
    // purpose; it doesn't seem to make sense to wrap it in some typed
    // DataDefinition class...
    // @phpstan-ignore-next-line inorder to keep backward compatibility.
    $schema_definition = \Drupal::service('config.typed')->getDefinition(SamlController::CONFIG_OBJECT_NAME);
    assert(!empty($schema_definition['mapping']), 'Config schema of ' . SamlController::CONFIG_OBJECT_NAME . ' has unexpected value; ' . self::class . ' needs rework.');
    $schema_definition = $schema_definition['mapping'];

    $config = $this->config(SamlController::CONFIG_OBJECT_NAME);

    /** @var \Drupal\user\Entity\Role[] $roles */
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($roles[UserInterface::ANONYMOUS_ROLE]);
    $role_options = [];
    foreach ($roles as $name => $role) {
      $role_options[$name] = $role->label();
    }
    $real_role_options = $role_options;
    unset($real_role_options[UserInterface::AUTHENTICATED_ROLE]);
    $collapse_rolesets = count($role_options) > self::MAX_UNCOLLAPSED_ROLES;

    $form['ui'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('User Interface'),
    ];

    // Show note for enabling "log in" or "log out" menu link item.
    if (Url::fromRoute('entity.menu.edit_form', ['menu' => 'account'])->access()) {
      $form['ui']['#description'] =
        '<em>' . $this->t('Note: You <a href="@url">may want to enable</a> the "log in" / "log out" menu item and disable the original one.', [
          '@url' => Url::fromRoute('entity.menu.edit_form', ['menu' => 'account'])
            ->toString(),
        ]) . '</em>';
    }

    $this->addElementsFromSchema($form['ui'], $schema_definition, $config, [
      'login_menu_item_title' => $this->t('The title of the SAML login link in the User account menu. Defaults to "Log in".'),
      'logout_menu_item_title' => $this->t('The title of the SAML logout link in the User account menu. Defaults to "Log out".'),
      'login_link_title' => $this->t('Displays a link to SAML login on the login form, with the provided title.'),
      'login_auto_redirect' => $this->t("When enabled, the regular Drupal login screen at /user/login cannot be used. The login block still works, so that's the only place where the login link is still seen (if a title is provided). /user/logout is still regular Drupal logout (as supposed to /saml/logout which redirects to the IdP and potentially logs out from other sites)."),
    ]);

    $form['user_info'] = [
      '#title' => $this->t('Drupal Login Using SAML Data'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('User creation / synchronization / Drupal login can proceed when <a href=":url">SAML communication</a> happens successfully.', [
        ':url' => Url::fromRoute('samlauth.saml_configure_form')->toString(),
      ]),
    ];

    $form['user_info']['unique_id_source'] = [
      '#title' => $this->t('Unique ID source'),
      '#description' => $this->t('Never change this setting (and the NameID format / Unique ID Attribute) after users have started logging in.'),
      '#type' => 'radios',
      '#options' => [$this->t('NameID'), $this->t('Attribute')],
      '#default_value' => $config->get('unique_id_attribute') === SamlService::NAMEID_MOCK_ATTRIBUTE_NAME ? 0 : 1,
    ];

    $this->addElementsFromSchema($form['user_info'], $schema_definition, $config, [
      'unique_id_attribute' => [
        '#description' => $this->t('You need to know which attributes your IdP sends along in a SAML login response.'),
        '#states' => [
          'visible' => [
            ':input[name="unique_id_source"]' => ['value' => 1],
          ],
        ],
        '#default_value' => $config->get('unique_id_attribute') === SamlService::NAMEID_MOCK_ATTRIBUTE_NAME ? NULL : $config->get('unique_id_attribute'),
      ],
    ]);

    // Make nameID options from (several different groups on) the SAML tab
    // visible / editable here. Hopefully the descriptions provide at least a
    // little clarity about what should be configured how (though the way in
    // which options can influence each other doesn't help). An alternative
    // would be to have warning messages for certain configuration values in
    // combined with source "NameID", but I'm not sure that would turn out
    // clearer.
    $form['user_info']['nameid'] = [
      '#title' => $this->t('NameID options, repeated from "SAML" tab'),
      '#type' => 'details',
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="unique_id_source"]' => ['value' => 0],
        ],
      ],
    ];
    $this->addElementsFromSchema($form['user_info']['nameid'], $schema_definition, $config, [
      'request_set_name_id_policy' => [
        '#description' => $this->t('A NameIDPolicy element is added in authentication requests, mentioning the below format (if "Require NameID to be encrypted" is off).'),
        '#default_value' => $config->get('request_set_name_id_policy') ?? TRUE,
      ],
    ]);
    $this->addNameID($form['user_info']['nameid'], $schema_definition, $config);
    $this->addElementsFromSchema($form['user_info']['nameid'], $schema_definition, $config, [
      'security_want_name_id' => [
        '#description' => $this->t('The authentication response from the IdP must contain a NameID attribute.'),
        '#default_value' => $config->get('security_want_name_id') ?? TRUE,
      ],
      'security_nameid_encrypted' => $this->t('The NameID in login responses from the IdP is expected to be encrypted. This overrides the requested NameID Format and sets "Encrypted" in authentication requests\' NameIDPolicy element.') . '*',
    ]);

    $form['user_info']['linking'] = [
      '#title' => $this->t('Attempt to link SAML data to existing Drupal users'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => t('If enabled, whenever the unique ID in the SAML assertion is not already associated with a Drupal user but the assertion data can be matched with an existing Drupal user without SAML association, that user will be linked and logged in. Matching is attempted in the order of below enabled checkboxes, until a user is found.')
      . '<br><br><em>' . t('Warning: if the data used for matching can be changed by the IdP user, this has security implications; it enables a user to influence which Drupal user they take over.') . '</em>',
    ];

    $this->addElementsFromSchema($form['user_info']['linking'], $schema_definition, $config, [
      'map_users' => $this->t("Allows user matching by the included 'User Fields Mapping' module as well as any other code (event subscriber) installed for this purpose."),
      'map_users_name' => $this->t('Allows matching an existing Drupal user name with value of the user name attribute.'),
      'map_users_mail' => $this->t('Allows matching an existing Drupal user email with value of the user email attribute.'),
    ]);
    // map_users_role special value ['anonymous'] means "Allow all roles".
    // Otherwise, 'anonymous' and 'authenticated' must not be / are assumed to
    // not be part of the map_users_role value; they're "reserved" for possible
    // future use.
    $roles = $config->get('map_users_roles') ?? [];
    $allow_all = $roles === [AccountInterface::ANONYMOUS_ROLE];
    $form['user_info']['linking']['allow_all_roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow all Drupal users to be linked (regardless of role)'),
      '#description' => $this->t("This option may be enabled if you absolutely trust that the SAML attributes used in your linking configuration can never be manipulated to point to an unintended user."),
      '#default_value' => $allow_all,
      '#states' => [
        'disabled' => [
          ':input[name="map_users"]' => ['checked' => FALSE],
          ':input[name="map_users_name"]' => ['checked' => FALSE],
          ':input[name="map_users_mail"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element = [
      '#type' => 'checkboxes',
      '#title' => $this->t($schema_definition['map_users_roles']['label']),
      '#description' => $this->t('If a matched account has <em>any</em> role that is not explicitly allowed here, linking/login is denied.'),
      '#options' => $real_role_options,
      '#default_value'  => $allow_all ? [] : array_diff($roles, [AccountInterface::ANONYMOUS_ROLE, AccountInterface::AUTHENTICATED_ROLE]),
      '#states' => [
        'disabled' => [
          ':input[name="map_users"]' => ['checked' => FALSE],
          ':input[name="map_users_name"]' => ['checked' => FALSE],
          ':input[name="map_users_mail"]' => ['checked' => FALSE],
        ],
        'invisible' => [
          ':input[name="allow_all_roles"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if ($collapse_rolesets) {
      $form['user_info']['linking']['roles'] = [
        '#title' => $this->t('Allowed roles'),
        '#type' => 'details',
        '#open' => FALSE,
        '#states' => [
          'invisible' => [
            ':input[name="allow_all_roles"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['user_info']['linking']['roles']['map_users_roles'] = $element;
    }
    else {
      $form['user_info']['linking']['map_users_roles'] = $element;
    }

    $this->addElementsFromSchema($form['user_info'], $schema_definition, $config, [
      'create_users' => $this->t('If data in the SAML assertion is not associated with a Drupal user, a new user is created using the name / email attributes from the response.'),
      'sync_name' => $this->t('The name attribute in the SAML assertion is propagated to the associated Drupal user on every login. (When disabled, the Drupal user name is not changed after user creation.)'),
      'sync_mail' => $this->t('The email attribute in the SAML assertion is propagated to the associated Drupal user on every login. (When disabled, the Drupal user email is not changed after user creation.)'),
      'user_name_attribute' => [
        '#description' => $this->t('When users are linked / created, this field specifies which SAML attribute should be used for the Drupal user name.<br />Example: <em>cn</em> or <em>eduPersonPrincipalName</em>'),
        '#states' => [
          'disabled' => [
            ':input[name="map_users_name"]' => ['checked' => FALSE],
            ':input[name="create_users"]' => ['checked' => FALSE],
            ':input[name="sync_name"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'user_mail_attribute' => [
        '#description' => $this->t('When users are linked / created, this field specifies which SAML attribute should be used for the Drupal email address.<br />Example: <em>mail</em>'),
        '#states' => [
          'disabled' => [
            ':input[name="map_users_mail"]' => ['checked' => FALSE],
            ':input[name="create_users"]' => ['checked' => FALSE],
            ':input[name="sync_mail"]' => ['checked' => FALSE],
          ],
        ],
      ],
    ]);

    $form['login_logout'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Login / Logout'),
    ];

    if ($collapse_rolesets) {
      $form['login_logout']['roles'] = [
        '#title' => $this->t('Roles allowed Drupal login'),
        '#type' => 'details',
        '#open' => FALSE,
      ];
    }
    $this->addElementsFromSchema($form['login_logout'], $schema_definition, $config, [
      'drupal_login_roles' => [
        '#description' => $this->t('Users who have previously logged in through the IdP can only use the standard Drupal login method if they have one of the roles selected here. Preexisting Drupal users who have never logged in through the IdP are not affected by this restriction.'),
        '#options' => $role_options,
      ],
      'local_login_saml_error' => [
        '#description' => $this->t('When disabled, the generic "Unrecognized username or password" message is shown to users who cannot use the standard Drupal login method. This prevents disclosing information about whether the account name exists, but is untrue / potentially confusing.', [
          ':permission' => Url::fromUri('base:admin/people/permissions', ['fragment' => 'module-samlauth'])->toString(),
        ]),
        // TRUE on existing installations where the checkbox didn't exist
        // before; FALSE on new installations.
        '#default_value' => $config->get('local_login_saml_error') ?? TRUE,
      ],
      'idp_change_password_service' => $this->t('URL where disallowed users (who do not have a Drupal password) will be directed to change their password. This is shown on their account edit form.'),
      'login_redirect_url' => $this->t("The default URL to redirect the user to after login. This should be an internal path starting with a slash, or an absolute URL. Defaults to the logged-in user's account page."),
      'logout_redirect_url' => $this->t('The default URL to redirect the user to after logout. This should be an internal path starting with a slash, or an absolute URL. Defaults to the front page.'),
      'error_redirect_url' => [
        '#description' => $this->t("The default URL to redirect the user to after an error occurred. This should be an internal path starting with a slash, or an absolute URL. Defaults to the front page."),
        '#states' => [
          'disabled' => [
            ':input[name="error_throw"]' => ['checked' => TRUE],
          ],
        ],
      ],
      'error_throw' => $this->t("No redirection or meaningful logging is done. This better enables custom code to handle errors."),
      'login_error_keep_session' => $this->t("When Drupal login fails after successful SAML authentication, the user's state at the IdP is still 'logged in'. This option keeps SAML session data in a Drupal session for the anonymous user, so that a logout request can be started from this site (/saml/logout) successfully afterwards."),
      'logout_different_user' => $this->t('If a login (coming from the IdP) happens while another user is still logged into the site, that user is logged out and the new user is logged in. (By default, the old user stays logged in and a warning is displayed. This situation does not apply if the IdP is on another domain and <a href="https://www.drupal.org/node/3275352">cookie_samesite is configured</a> as "Strict" or "Lax", as is standard for new D10.1+ installs, because then the old user is not seen while coming from the IdP, and login happens normally.)'),
      'bypass_relay_state_check' => $this->t("When enabled, a response's RelayState parameter is redirected to, even if not a known safe hostname. (This will be removed in a newer version of the module.)"),
    ]);
    if ($collapse_rolesets) {
      $form['login_logout']['roles']['drupal_login_roles'] = $form['login_logout']['drupal_login_roles'];
      $form['login_logout']['drupal_login_roles'] = [];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validate login/logout redirect URLs.
    $login_url_path = $form_state->getValue('login_redirect_url');
    if ($login_url_path) {
      $login_url_path = $this->token->replace($login_url_path);
      $login_url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($login_url_path);
      if (!$login_url) {
        $form_state->setErrorByName('login_redirect_url', $this->t('The Login Redirect URL is not a valid path.'));
      }
    }
    $logout_url_path = $form_state->getValue('logout_redirect_url');
    if ($logout_url_path) {
      $logout_url_path = $this->token->replace($logout_url_path);
      $logout_url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($logout_url_path);
      if (!$logout_url) {
        $form_state->setErrorByName('logout_redirect_url', $this->t('The Logout Redirect URL is not a valid path.'));
      }
    }
    $error_redirect_url = $form_state->getValue('error_redirect_url');
    if ($error_redirect_url) {
      $error_redirect_url = $this->token->replace($error_redirect_url);
      $error_url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($error_redirect_url);
      if (!$error_url) {
        $form_state->setErrorByName('error_redirect_url', $this->t('The Error redirect URL is not a valid path.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(SamlController::CONFIG_OBJECT_NAME);

    $config_keys_to_save = [
      'login_menu_item_title',
      'logout_menu_item_title',
      'login_link_title',
      'login_auto_redirect',
      'map_users',
      'map_users_name',
      'map_users_mail',
      'create_users',
      'sync_name',
      'sync_mail',
      'user_name_attribute',
      'user_mail_attribute',
      'local_login_saml_error',
      'idp_change_password_service',
      'login_redirect_url',
      'logout_redirect_url',
      'error_redirect_url',
      'error_throw',
      'login_error_keep_session',
      'logout_different_user',
      'bypass_relay_state_check',
    ];
    // unique_id_source indexes is hardcoded: 0 == nameid
    if ($form_state->getValue('unique_id_source')) {
      $config_keys_to_save[] = 'unique_id_attribute';
    }
    else {
      $config->set('unique_id_attribute', SamlService::NAMEID_MOCK_ATTRIBUTE_NAME);
      // Only save the NameID config values if they are visible.
      $this->setNameID($form_state, $config);
      $config_keys_to_save[] = 'request_set_name_id_policy';
      $config_keys_to_save[] = 'security_want_name_id';
      $config_keys_to_save[] = 'security_nameid_encrypted';
    }

    foreach ($config_keys_to_save as $config_key) {
      $config->set($config_key, $form_state->getValue($config_key));
    }
    // Filter out 0 inputs from multivalue checkboxes.
    $config->set('drupal_login_roles', array_filter($form_state->getValue('drupal_login_roles')));
    $config->set('map_users_roles', $form_state->getValue('allow_all_roles') ?
      [AccountInterface::ANONYMOUS_ROLE] : array_filter($form_state->getValue('map_users_roles')));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
