<?php

namespace Drupal\Tests\samlauth\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\externalauth\ExternalAuth;
use Drupal\samlauth\SamlService;
use Drupal\user\UserInterface;
use Psr\Log\NullLogger;

/**
 * Tests login.
 *
 * @group custom_elements
 */
class SamlLoginTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'externalauth',
    'samlauth',
  ];


  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('externalauth', 'authmap');
  }

  /**
   * Tests user linking/creation, separately from interpreting SAML assertions.
   *
   * This does not test linking
   *
   * @dataProvider providerUserLogin
   */
  public function testUserLogin(array $config_values, array $attributes, mixed $expected) {
    // Add base config and precreate users.
    // 1: [user1, user1@example.com].
    // 1*: Same, already linked in authmap.
    // 1*:blocked: linked in authmap and blocked.
    // Default: user1 prelinked, user2 not linked.
    $precreate_users = isset($config_values['USERS'])
      ? is_array($config_values['USERS']) ? $config_values['USERS'] : [$config_values['USERS']]
      : ['1*', '2'];
    unset($config_values['USERS']);

    foreach ($precreate_users as $id) {
      $data = [];
      $block = str_ends_with((string) $id, ':blocked');
      if ($block) {
        $id = substr($id, 0, strlen($id) - 8);
        $data['status'] = 0;
      }
      $prelink = str_ends_with((string) $id, '*');
      if ($prelink) {
        $id = substr($id, 0, strlen($id) - 1);
      }
      $data['mail'] = "user$id@example.com";
      $user = $this->createUser([], "user$id", FALSE, $data);
      if ($prelink) {
        $this->container->get('externalauth.authmap')->save($user, 'samlauth', $id);
      }
    }

    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('samlauth.authentication');
    foreach ($config_values + [
      'create_users' => FALSE,
      'map_users' => FALSE,
      'map_users_mail' => FALSE,
      'map_users_name' => FALSE,
      'user_mail_attribute' => 'm',
      'user_name_attribute' => 'n',
      'unique_id_attribute' => 'U',
      'sync_mail' => FALSE,
      'sync_name' => FALSE,
    ] as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    // Create mock Externalauth and SamlService.
    $externalauth = new class(
      $this->container->get('entity_type.manager'),
      $this->container->get('externalauth.authmap'),
      new NullLogger(),
      $this->container->get('event_dispatcher'),
    ) extends ExternalAuth {

      public ?UserInterface $_loggedinUser = NULL;

      public function userLoginFinalize(UserInterface $account, string $authname, string $provider): UserInterface {
        // Don't actually log in. Keep the account for later.
        $this->_loggedinUser = $account;
        return $account;
      }

    };

    $saml = new class($this->container, $externalauth, $attributes) extends SamlService {

      protected array $_attributes;

      public function __construct($container, $externalauth, array $attributes) {
        parent::__construct(
          $externalauth,
          $container->get('externalauth.authmap'),
          $container->get('config.factory'),
          $container->get('entity_type.manager'),
          new NullLogger(),
          $container->get('event_dispatcher'),
          $container->get('request_stack'),
          $container->get('tempstore.private'),
          $container->get('flood'),
          $container->get('current_user'),
          $container->get('messenger'),
          $container->get('string_translation')
        );

        // Test data is defined as simple values but SAML attributes are arrays.
        $this->_attributes = array_map(fn($v) => [$v], $attributes);
      }

      // Override attributes to always get the ones passed in.
      public function getAttributes() {
        return $this->_attributes;
      }

      // Make dologin() public, for testing.
      public function doLogin($unique_id, AccountInterface $account = NULL) {
        parent::doLogin($unique_id, $account);
      }

    };

    // This is needed because samlauth_user_presave calls \Drupal::service().
    $this->container->set('samlauth.saml', $saml);

    // Exercise the tested code. This first block is pretty much copied from
    // SamlService, instead of the original code being tested, because it's
    // inside acs() among the SAML assertion handling code.
    $account = NULL;
    $unique_id = $saml->getAttributeByConfig('unique_id_attribute');
    if (isset($unique_id)) {
      /** @var \Drupal\externalauth\ExternalAuth $ea */
      $ea = $this->container->get('externalauth.externalauth');
      $account = $ea->load($unique_id, 'samlauth') ?: NULL;
    }

    $exception_message = '';
    try {
      $saml->doLogin($unique_id, $account);
    }
    catch (\Exception $e) {
      if (!is_string($expected)) {
        throw $e;
      }
      $exception_message = $e->getMessage();
    }
    if (is_string($expected)) {
      // Expect a specific exception to be thrown, i.e. fail if there is no
      // exception or if the message differs. ($expected must not be empty.)
      $this->assertSame($expected, $exception_message);
    }
    else {
      // doLogin() always results in a logged-in account or an exception, but
      // the logged-in account isn't necessarily in $account.
      $this->assertNotEmpty($externalauth->_loggedinUser, 'A user must be logged in at this point.');
      $this->assertSame($expected[0], $externalauth->_loggedinUser->getAccountName(), "User name must be $expected[0].");
      $this->assertSame($expected[1], $externalauth->_loggedinUser->getEmail(), "User e-mail must be $expected[1].");
    }
  }

  /**
   * Data provider for testUserLogin().
   *
   * @return array
   *   - Array with samlauth config values;
   *   - Array with attributes
   *   - Two element array with expected name + email of the user, or string:
   *     expected exception message.
   */
  public function providerUserLogin() {
    // To repeat: if no 'USERS' specified: user1 exists and is pre-linked,
    // user2 exists and is not linked.
    return [
      // Existing already-linked user can be logged in.
      [
        [],
        ['U' => 1, 'n' => 'user1', 'm' => 'user1@example.com'],
        ['user1', 'user1@example.com'],
      ],
      // Synchronizing name / email happens when configured; otherwise, only
      // the unique ID is used.
      [
        [],
        ['U' => 1, 'n' => 'newname', 'm' => 'new@example.com'],
        ['user1', 'user1@example.com'],
      ],
      [
        ['sync_name' => TRUE],
        ['U' => 1, 'n' => 'newname', 'm' => 'new@example.com'],
        ['newname', 'user1@example.com'],
      ],
      [
        ['sync_mail' => TRUE],
        ['U' => 1, 'n' => 'newname', 'm' => 'new@example.com'],
        ['user1', 'new@example.com'],
      ],
      // Existing user login works without any name attribute specified.
      [
        [],
        ['U' => 1],
        ['user1', 'user1@example.com'],
      ],
      [
        ['sync_name' => TRUE, 'sync_mail' => TRUE],
        ['U' => 1],
        ['user1', 'user1@example.com'],
      ],
      // When username already exists, an error is logged (which this test
      // doesn't check) and the old name is kept. When email already exists, it
      // is just changed anyway, so (/ because) two Drupal accounts can have
      // the same email address. Note this is inconsistent with creation of a
      // new user with duplicate email address, which is forbidden:
      // - We do not cancel login while just synchronizing data for an existing
      //   account. (We might consider that in a later version.)
      // - Leaving the email unchanged is not better. (We only do that for name
      //   because there's no other way.)
      // Newly-linking an account by name and changing the email address to a
      // duplicate at the same time is also allowed; see further below.
      [
        ['sync_name' => TRUE, 'sync_mail' => TRUE],
        ['U' => 1, 'n' => 'user2', 'm' => 'user2@example.com'],
        ['user1', 'user2@example.com'],
      ],
      // Blocked user cannot log in.
      [
        ['USERS' => 'b*:blocked'],
        ['U' => 'b'],
        'Requested account is blocked.',
      ],
      [
        ['USERS' => 'b*:blocked'],
        ['U' => 'b', 'n' => 'user1'],
        'Requested account is blocked.',
      ],

      // New user (i.e. unique ID does not exist) can be created and logged
      // in, if config allows it.
      [
        [],
        ['U' => 3, 'n' => 'newname', 'm' => 'new@example.com'],
        'No existing user account matches the unique ID in the SAML data. This authentication service does not create new accounts.',
      ],
      [
        ['create_users' => TRUE],
        ['U' => 3, 'n' => 'newname', 'm' => 'new@example.com'],
        ['newname', 'new@example.com'],
      ],
      // If no email adress is given and mail_attribute is set: error.
      [
        ['create_users' => TRUE],
        ['U' => 3, 'n' => 'newname'],
        'Error(s) encountered during SAML attribute synchronization: Email address is not provided in SAML attribute.',
      ],
      // Creating users without email address is allowed / all users are
      // created without email, if mail_attribute is not set.
      [
        ['create_users' => TRUE, 'user_mail_attribute' => ''],
        ['U' => 3, 'n' => 'newname', 'm' => 'new@example.com'],
        ['newname', NULL],
      ],
      [
        ['create_users' => TRUE, 'user_mail_attribute' => ''],
        ['U' => 3, 'n' => 'newname'],
        ['newname', NULL],
      ],
      // New account without name: never works. Error is ambiguous / might
      // be changed whenever needed.
      [
        [],
        ['U' => 3, 'm' => 'new@example.com'],
        'No existing user account matches the unique ID in the SAML data. This authentication service does not create new accounts.',
      ],
      [
        ['user_name_attribute' => ''],
        ['U' => 3, 'm' => 'new@example.com'],
        'No existing user account matches the unique ID in the SAML data. This authentication service does not create new accounts.',
      ],
      // If a name / email already exists: error. Note this exposes information
      // about the existence of existing users, but that is only an issue when
      // - the IdP is not trusted
      // - or unsigned SAML messages are allowed.
      [
        ['create_users' => TRUE],
        ['U' => 3, 'n' => 'user2', 'm' => 'new@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],
      [
        ['create_users' => TRUE],
        ['U' => 3, 'n' => 'newname', 'm' => 'user1@example.com'],
        'A local user account with your login email address already exists, and the current configuration disallows its use.',
      ],
      [
        ['create_users' => TRUE, 'USERS' => '3:blocked'],
        ['U' => 3, 'n' => 'user3', 'm' => 'user3@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],

      // Not pre-linked user can be linked and logged in, if config allows it.
      // Linking uses only the data for mapping; other data is only updated
      // if the 'sync' option is enabled.
      [
        [],
        ['U' => 2, 'n' => 'user2', 'm' => 'user2@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],
      [
        ['map_users_name' => TRUE],
        ['U' => 2, 'n' => 'user2'],
        ['user2', 'user2@example.com'],
      ],
      // (Added because equivalent of below <= 3.9 bug.)
      [
        ['map_users_name' => TRUE],
        ['U' => 2, 'n' => 'user2', 'm' => 'user2@example.com'],
        ['user2', 'user2@example.com'],
      ],
      [
        ['map_users_name' => TRUE],
        ['U' => 2, 'n' => 'user2', 'm' => 'user1@example.com'],
        ['user2', 'user2@example.com'],
      ],
      // User is linked and email address is changed: this can end up being
      // a duplicate email, just like when logging in an already-linked user.
      [
        ['map_users_name' => TRUE, 'sync_mail' => TRUE],
        ['U' => 2, 'n' => 'user2', 'm' => 'user1@example.com'],
        ['user2', 'user1@example.com'],
      ],
      [
        ['map_users_mail' => TRUE],
        ['U' => 2, 'm' => 'user2@example.com'],
        ['user2', 'user2@example.com'],
      ],
      [
        ['map_users_mail' => TRUE],
        ['U' => 2, 'n' => 'newname', 'm' => 'user2@example.com'],
        ['user2', 'user2@example.com'],
      ],
      [
        ['map_users_mail' => TRUE, 'sync_name' => TRUE],
        ['U' => 2, 'n' => 'newname', 'm' => 'user2@example.com'],
        ['newname', 'user2@example.com'],
      ],
      // user2 is not seen as a duplicate name while mapping only email. (This
      // was a bug in <= 3.9.)
      [
        ['map_users_mail' => TRUE],
        ['U' => 2, 'n' => 'user2', 'm' => 'user2@example.com'],
        ['user2', 'user2@example.com'],
      ],
      // The name in the SAML attribute is used for uniqueness checking, even
      // when it is not used for linking and 'sync_name' is off, so the
      // logged-in user would end up as ['user2', 'user2@example.com'].
      // Reasoning:
      // - It's too (potentially) confusing to let this through;
      // - If the existing user2 did not exist, a new user would be generated
      //   (if config allows it) and that one would clash. Better be consistent
      //   with that situation.
      // Note that
      // - per above, linking a user and changing the email to a duplicate IS
      //   possible, so that is consistent with logging in an already-linked
      //   user (and not with creating a new user), when sync_mail is enabled.
      // - while this name handling is consistent with creating a new user (and
      //   not with logging in an already-linked user; when sync_name is
      //   enabled, that will just skip changing the change to a duplicate.)
      [
        ['map_users_mail' => TRUE],
        ['U' => 2, 'n' => 'user1', 'm' => 'user2@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],
      // - When different unlinked users with the same name + email exist, the
      //   name is preferred for linking (or giving error). And if sync_mail is
      //   on, that would lead to a duplicate email, per above.
      // - Linking an account that is already linked to another unique ID
      //   throws an error.
      [
        ['map_users_name' => TRUE, 'map_users_mail' => TRUE],
        ['U' => 2, 'n' => 'user1', 'm' => 'user2@example.com'],
        'Your login data match an earlier login by a different SAML user.',
      ],
      [
        ['map_users_name' => TRUE, 'map_users_mail' => TRUE, 'USERS' => [1, 2]],
        ['U' => 2, 'n' => 'user1', 'm' => 'user2@example.com'],
        ['user1', 'user1@example.com'],
      ],
      // Linking a blocked account does not work.
      [
        ['map_users_mail' => TRUE, 'USERS' => '2:blocked'],
        ['U' => 2, 'n' => 'user2', 'm' => 'user2@example.com'],
        'Requested account is blocked.',
      ],

      // Incomplete configurations. Some of these are more like a spec /
      // explanation of how things work at the moment, than like tests, and
      // might change over time.
      //
      // Unique ID not provided: error (which changes based upon whether the
      // name/mail exists; this might change if needed).
      [
        [],
        ['n' => 'newname', 'm' => 'new@example.com'],
        'No existing user account matches the unique ID in the SAML data. This authentication service does not create new accounts.',
      ],
      [
        [],
        [],
        'No existing user account matches the unique ID in the SAML data. This authentication service does not create new accounts.',
      ],
      [
        [],
        ['n' => 'user2', 'm' => 'user2@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],
      [
        [],
        ['n' => 'newname', 'm' => 'user2@example.com'],
        'A local user account with your login email address already exists, and the current configuration disallows its use.',
      ],
      // Unique ID not configured: error says the same (and does not mention
      // the misconfiguration; this might change if needed).
      [
        ['unique_id_attribute' => ''],
        [],
        'No existing user account matches the unique ID in the SAML data. This authentication service does not create new accounts.',
      ],
      [
        ['unique_id_attribute' => ''],
        ['n' => 'user1', 'm' => 'user1@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],

      // Repeat most tests with ID '0' to shake out bugs because '0' == ''.
      // It should work; It's the IdP's business if that is disallowed.
      [
        ['USERS' => '0*'],
        ['U' => '0'],
        ['user0', 'user0@example.com'],
      ],
      [
        ['USERS' => '0*:blocked'],
        ['U' => '0', 'n' => 'user1', 'm' => 'user1@example.com'],
        'Requested account is blocked.',
      ],
      [
        ['create_users' => TRUE],
        ['U' => '0', 'n' => 'user0', 'm' => 'user0@example.com'],
        ['user0', 'user0@example.com'],
      ],
      [
        ['create_users' => TRUE, 'USERS' => '0'],
        ['U' => '0', 'n' => 'user0', 'm' => 'user0@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],
      [
        ['create_users' => TRUE, 'USERS' => '0*:blocked'],
        ['U' => '0', 'n' => 'user1', 'm' => 'user1@example.com'],
        'Requested account is blocked.',
      ],
      [
        ['create_users' => TRUE, 'USERS' => '0:blocked'],
        ['U' => '0', 'n' => 'user0', 'm' => 'user1@example.com'],
        'A local user account with your login name already exists, and the current configuration disallows its use.',
      ],
      [
        ['map_users_name' => TRUE, 'USERS' => '0'],
        ['U' => '0', 'n' => 'user0',],
        ['user0', 'user0@example.com'],
      ],
      [
        ['map_users_name' => TRUE, 'USERS' => '0:blocked'],
        ['U' => '0', 'n' => 'user0',],
        'Requested account is blocked.',
      ],
    ];
  }

}
