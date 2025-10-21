INTRODUCTION
------------
This module allows users to authenticate against a SAML Identity Provider (IdP)
to log in to a Drupal application.

Essential basics of SAML, tuned to our situation: The IdP is the remote system
which users are directed to upon login, which authorizes the user to log into
our site. The Service Provider (SP) is a standalone piece of code (implemented
by the SAML PHP Toolkit) which takes care of the SAML communication /
validating the assertions sent back by the IdP.

In our case, the SP is integrated into the Drupal site, so "your SP" is "your
Drupal application". The SAML Authentication module
- enables configuring most options for the SP
- exposes some URL paths which pass HTTP requests (which either start the login
  procedure or are redirected back from the IdP) into the SP library/code
- logs the user in to Drupal / out of Drupal, after the SP has validated the
  assertions in these HTTP requests.
- configures other things like disabling local Drupal login for users who must
  only log in through SAML.

For more information about SAML, see: https://en.wikipedia.org/wiki/SAML_2.0

UPGRADING
------------
Upgrading from 8.x-2.x to 8.x-3.x is trivial; just make sure your composer
dependencies are met. The only reason for the major version jump was a major
version jump in the upstream php-saml dependency that was caused by a security
issue - plus having no indication that the upstream 2.x version (which only
supported PHP < 7.2) would stay maintained.

There is no tested upgrade path from 8.x-1.3 to 8.x-3.x. Upgrading has two
aspects:
* Configuration: the module should keep working after the module upgrade
  because the configuration hasn't changed; it has just been added to.
  Regardless: it is recommended to look through existing configuration to see
  which new configuration options are beneficial to include/change.
* Data: the links between SAML login ID and Drupal user are stored differently
  so after the upgrade, users could (depending on your settings) be unable to
  log in or potentially even log in as a different user. The data can be
  migrated with this SQL query (to be modified for database prefixes):
  ```sql
  INSERT INTO authmap (uid, authname, provider, data)
  SELECT uid, value AS authname, 'samlauth' AS provider, 'N;' as data from users_data WHERE module='samlauth' and name='saml_id'
  ```

INSTALLATION
------------
Install as you would normally install a contributed drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

REQUIREMENTS
------------
This module depends on OneLogin's SAML PHP Toolkit:
https://github.com/onelogin/php-saml. This is automatically installed if you
installed the module using Composer.

Other optional installs:
- views module, to see a list of currently registered links (associations)
  between SAML login data and Drupal users - and be able to delete them from
  the administrative UI (rather than directly manipulating the 'authmap' table).
- flood_control module. Flood control is applied to failed login attempts -
  which is Drupal Core functionality without a UI. Too many failed logins could
  result  in "Access is blocked because of IP based flood prevention."
  messages, though this is very unlikely to happen. To have an administrative
  UI rather than manipulating the 'flood' table directly in those cases,
  install the flood_control module.

IdP requrements:

The SAML PHP Toolkit only works with IdP endpoints that have a "Redirect
binding" for the SSO/login endpoint. A "POST binding" is not supported. (See
https://github.com/SAML-Toolkits/php-saml/issues/264.) Which binding your IdP
uses, is visible either in their metadata XML or hopefully in some other
information they provide (e.g. the URL itself).

If you must use a POST binding: untested support, without guarantees of working,
is available indirectly through the previous link or from
https://www.drupal.org/project/samlauth/issues/2854751.

For Service Provider configuration,

- You need an SSL public/private key pair - or, more precisely: a private key
  and a related public X.509 certificate. You may have opinions and/or
  procedures around creating safe key pairs, and we won't discuss this here
  besides giving a common command for creating a test key if you have none:
  ```
  openssl req -new -x509 -days 3652 -nodes -out sp.crt -keyout sp.key
  ```
  Further, the internet has more information about this topic.
- You need to decide where to store the keys. This module can:
  - Store them in a file on the webserver's file system. (Always keep the
    private key in a safe location outside the webserver's document root.)
  - Store them in configuration values. This is generally less secure than a
    file, but may be useful for test environments, depending on your setup.
  - Use the [Key](https://www.drupal.org/project/key) module, which has options
    for safer retrieval of keys, e.g. from an environment variable or various
    external key management solutions. In order to use this solution, before
    configuring samlauth:
    - Install [Asymmetric Keys](https://www.drupal.org/project/key_asymmetric),
      plus its dependencies (the Key module and phpseclib/phpseclib:~3.0.7).
    - Install the appropriate add-on module if you want to use an external key
      provider.
    - Visit admin/config/system/keys and add 'Key' for your private key, using
      your preferred key provider. Optionally, also create a 'key' for the
      related X.509 certificate. (This is optional because the certificate is
      not a secret, but it may be beneficial to keep both in the same list.)
- You need to exchange information with the IdP, because both parties need to
  configure the other's identity/location. You also need to know how the IdP
  will send a never-changing Unique ID across, that will identify a login. More
  details are in the respective configuration sections.

CONFIGURATION AND TESTING
-------------------------

Start at the "User Interface" part of /admin/config/people/saml; check if
you want to enable links for testing. (This is optional; direct URLs
/saml/login and /saml/logout can also be used for testing.) Then (save and)
switch to the "SAML" tab.

Testing SAML login is often a challenge to get right in one go. For those not
familiar with SAML setup, it may be less confusing to test things in separate
stages, so several configuration sections below document a specific action to
take after configuring that one section. You're free to either go through those
stages separately or configure everything at once / in a different order.

## Stage 1: SAML communication setup

The main thing to do in this first stage is: make sure that the SP can talk to
the IdP and vice versa. For this to happen, both sides will need to know data
from the other side:
* Its 'entity ID';
* A set of URLs to redirect users to, on login/logout actions;
* A public SSL certificate (so communication can be encrypted).

How this data is exchanged (e.g. sent by email, or through XML files that
are either exchanged manually or retrieved from a URL), and which side sends
their data first, depends on your organization's structure / preferences.

This information is available / configurable at the "SAML" tab.

### Service Provider:

URLs are hardcoded by this module. If you see HTTP where you want to see HTTPS:
See the question / answer section at the bottom.

The Entity ID is used to identify this particular SP to the IdP, and can be any
value as long as it is unique among all SPs known by the IdP. (Many SPs make it
equal to the URL for the application or metadata, but that's just a convention.
Choose anything you like, unless the organization operating the IdP mandates a
specific value/format.)

If your SSL certificate / private key is stored safely as discussed above at
"Requirements", reference them in this section. Alternatively (less safe) select
"Configuration" for 'Type of storage', and paste the key / certificate into
the corresponding text areas.

After saving this configuration, the metadata XML should contain the basic data
necessary for the IdP to configure the SP information on their side. When in
doubt, this is the point at which you can provide this data to the (people
administering the) IdP:

- go to admin/people/permissions#module-samlauth to enable permission to view
  the metadata, test it (see URL at top of section) and pass on the URL
- or: save the XML file from the metadata URL (/saml/metadata) and pass it on
- or: just send them the Entity ID, the public certificate and the URLs
  displayed in the "Service Provider" section of the configuration screen.

However, there are more hints about the SAML login / logout requests that are
reflected in the metadata XML. So if you're curious and/or know details about
what the IdP expects, then go through other sections to get the details of
the XML exactly right:
* SAML Message Construction
* SAML Message Validation
* The names of attributes mentioned in "Drupal Login Using SAML Data" (
  "Login/Users" tab) and optionally "User field mapping" (provided by
  samlauth_user_fields module)

### Identity Provider:

The information in this section must be provided by the IdP. Likely they
provide it in a metadata file in XML format (at a URL which may or may not be
publicly accessible).

This module has no option to parse the XML yet, so: copy the information from
the XML file into this section.

At this point, the communication between IdP and SP can be tested, though users
will not be logged into Drupal yet. If a login attempt is terminated with an
error "Configured unique ID is not present in SAML response", the configuration
is correct, and you can continue with stage 2.

In other cases, something is going wrong in the SAML communication. If the
error is not obvious, read through the "SAML Message Construction" / "SAML
Message Validation" sections to see if there are corresponding settings to
adjust. (For instance, if some validation of signatures fails, try to turn
strictness/validation settings off.)

### SAML Message Construction / SAML Message Validation

This ever expanding section of advanced configuration won't be discussed here
in detail; hopefully the setting descriptions give a clue. Just some hints:

- Turn strictness / signing / validation settings off only temporarily for
  testing / if absolutely needed.

### Debugging options

Hopefully the 'Debugging' options in the configuration screen are of enough
support to be able to get SAML login working. In particular, turn on "Log
incoming SAML messages" to be able to inspect the contents of SAML assertions
for the names of attributes containing data that needs to be written into
Drupal user accounts. (After trying to log in through the IdP, Drupal's "Recent
log messages" should contain the XML message that contains the assertion /
attributes.)

### Further debugging

Third party tools to help debug your SSO flow with SAML can be found at
https://www.samltool.com/saml_tools.php.

### Using a test Identity Provider

If you want to test SAML login against a different (test) IdP than the
'production' one you should connect to in the end, here's a few options:

1. Use an online service. (Search e.g. "SAML test service" and look for an IdP.
   None of the currently available options are documented in detail, here.)

2. Locally install a Docker instance containing a preconfigured IdP. Note that,
   since all communication happens through browser redirects, there is nothing
   preventing a remotely hosted Drupal application from configuring 'localhost'
   as Single Sign-On Service.

One Docker based IDP can be found at https://github.com/kenchan0130/docker-simplesamlphp
/ https://github.com/3breadt/docker-simplesamlphp. Instructions:

* Edit the 3 'SP' environment variables in docker-compose.yml to be equal to
  what is set/shown in Drupal's "Service Provider" section.
* In Drupal for the IdP, set (and change the ports to match docker-compose.yml)
  - Entity ID: https://localhost:8080/simplesaml/saml2/idp/metadata.php
  - Single Sign-On Service: http://localhost:8080/simplesaml/saml2/idp/SSOService.php
  - Single Logout Service: http://localhost:8080/simplesaml/saml2/idp/SingleLogoutService.php
  - X.509 certificate(s):
    - Type: Configuration; paste the contents of config/simplesamlphp/server.crt
    - Or: Type: file; copy config/simlesamlphp/server.crt somewhere appropriate.
* `docker compose up`

If the /saml/login flow is cached at the IdP and you want to see the login
screen again: visit /saml/reauth instead.

The only attribute sent in SAML messages is "email", so to test actual
login to Drupal: configure "email" in 3 places for Unique ID, name and email
attribute, per Stage 2.

## Stage 2: SAML attributes / Drupal Login

After stage 1, exchange of SAML messages works. Now, the data inside SAML
messages coming from the IdP needs to be used to log Drupal users in/out.
The basic configuration for this purpose is done in the "Login / Users" tab
(admin/config/people/saml), section "Drupal Login Using SAML Data".

### Unique IDs

The most important configuration value to get right from the start, is the
"Unique ID source". Each user logging in through SAML is identified by a unique
value (sent by the IdP), that is used to identify the Drupal user on subsequent
logins. In other words:
- A SAML login is considered 'new' if a certain value sent by the IdP has never
  been seen before.
- At that moment, a new Drupal user account is created or an existing (yet
  unlinked) Drupal account is linked to this unique ID value. (If the module is
  allowed to create/link users, by its configuration; otherwise, login is
  denied.)
- On subsequent logins, the same value is used to identify the Drupal account
  that will be logged in.

It is very important that this ID value both is unique to a specific SAML login
and never changes. If it ever changes, the SAML Authentication module does not
recognize the login as belonging to the right Drupal account anymore. If the
changed value was not seen in a previous SAML login before, then a new Drupal
account is created - and duplicate Drupal accounts will likely create
operational issues for your site. If the changed value was already seen in a
previous SAML login, then a different/wrong existing Drupal user is logged in,
which constitutes a security risk.

This is why the value for the "Unique ID source" should be configured once and
never be changed. If it ever changes, that likely means that all existing
links between earlier SAML logins and Drupal accounts are invalid, and should
be deleted. (The links are stored in the authmap table, for which a UI screen
exists at admin/people/authmap/samlauth, to delete 'wrong' links. There is
explicitly no edit facility for these links, because they should only be added
by SAML logins (or prepopulated by a system administrator to allow a specific
set of users to use SAML login).

There are two possible sources for the ID value:

* The 'NameID', which is a SAML specific construct for sending a special ID
  value in SAML responses. This is only viable if the IdP is capable of sending
  a NameID that never changes. To be as sure as possible, you should in the
  "SAML Message Construction / Validation" sections:
  - Turn on "Specify NameID policy" and specify a "NameID format" that is
    guaranteed to stay the same, so certainly not "Transient" or "Unspecified".
    (Note that the "NameID format" is what the SP requests from the IdP. A
    'good' IdP either complies with the request or returns an error that this
    is impossible, but technically there's nothing preventing the IdP from
    sending a different format. So it's best to check that the value is as
    expected.)
  - Turn on "Require NameID", because empty NameID will be useless.
* The name of a SAML 'attribute'. It is possible that your IdP configures
  unique user values as attributes to be sent as part of the SAML login
  response which cannot be configured as a standard NameID. (e.g. an employee
  number.)
  - The "NameID" related settings in the "SAML" tab (that are also visible
    when selecting "NameID" in the "Login/Users" tab) can likely be turned off.
    They can be kept/turned on if they don't cause any trouble; they're just
    unused.

It is up to you and/or the administrator of the IdP, to work out which source
should be used for the unique ID.

To configure the Unique ID and other attributes, you need to know the names of
the attributes which the IdP sends in its login assertions, and/or verify that
the NameID is correct. If you do not know this information, you need to inspect
the contents of such an assertion while a user tries to log in. See the section
on Debugging.

If there is absolutely no unique non-changing value to set as Unique ID, you
can take the username or email value. However, please be aware of the
operational / security risks mentioned earlier, each time the value changes on
the IdP side.

### Other settings

Other settings / checkboxes are hopefully self-explanatory.

If you enable the "Create users from SAML data" option, it is quite possible
that you'll want to add more data to the users than just name and email.
Synchronizing other fields and/or roles is done with optional modules, so that
their behavior can be more easily replaced with custom code. See the modules/
subdirectory and enable the shipped submodules as desired; their configuration
is exposed in extra tabs next to the "Configuration" tab.

## Further steps

Before taking SAML login into production, check the other sections in
admin/config/people/saml:

* "Attempt to link SAML data to existing Drupal users": see next section.
  (Enabling this is discouraged in favor of prepopulating authmap entries.)
* "Login / Logout" (Hopefully all options speak for themselves.)

And re-enable "Strict validation" and "Caching / validity" options in
admin/config/people/saml/saml.

CONSIDERATIONS REGARDING YOUR DRUPAL USERS
------------------------------------------

### User linking is a potential security risk

When users log in for the first time through the SAML IdP, they can, in order
of decreasing preference:
* be associated with a Drupal user already, if the login's Unique ID value was
  prepopulated in the authmap table (which makes this indistinguishable from a
  repeat login, as far as the samlauth module is concerned);
* be linked to an existing Drupal user (based on certain attribute values sent
  along with the login; the attribute names are configured per above);
* have a new Drupal user created (based on those attribute values);
* be denied - if the options for linking and/or creating a new user were not
  enabled in configuration. (Or: if the option for linking was not enabled, and
  creating a new user would lead to a duplicate username / email.)

Consider that linking existing Drupal users can constitute a security risk if
users are able to change the values of any attributes used for matching at the
IdP side; in this case, they can influence which user they are linked to.
(This is a similar risk as discussed earlier for the "Unique ID" value.) Use
the various 'linking' configuration settings only if you know this is not a
concern.

If an organization wants to restrict the users who can log in to a new Drupal
site to a known set, they can keep the "create new users" option turned off and
pre-create that set of users. They can then either turn on a "link existing
users" option (by name and/or email) or also prepopulate the entries in the
authmap table. The externalauth module contains a 'migrate destination' class
that can assist here. (Any known links to documentation that gives an overview
/ set of steps on how to import users and authmap entries will be added here.
Using the migrate system to populate data is documented at e.g.
https://www.drupal.org/node/2574707, but that is not a quick example/overview.)

### Login through the IdP vs locally; local password / email change

After users have logged in through the SAML IdP, the association between that
particular login and the Drupal user gets remembered. From this point on,
* the above considerations do not apply to this user anymore. (SAML login data
  never gets 're-linked' to a different Drupal user unless the association is
  manually removed - or changed outside of the Drupal UI.)
* users are treated differently unless they have a role that is explicitly
  "allowed to use Drupal login also when associated with a SAML login" by
  configuration:
  * They cannot log into Drupal directly anymore. Remember that if your Drupal
    site has existing locally (pre-)created users who know their password, this
    means there is an 'invisible' distinction with users who have not logged in
    through the IdP (yet): they can still log in locally.
  * They cannot change their password or email in the user's edit form. The
    password is hidden and the email field is locked.

This last thing is slightly arbitrary but is the best thing we know to do for
a consistent and non-confusing UI. Users who can only log in through the IdP
don't need their password for anything. They also cannot change their email if
they don't know their current password - and it is unlikely that they do. If
your use case involves existing Drupal users who know their password, then log
in through the IdP _and_ should be barred from logging in through Drupal after
that, but should still be able to change their email... Please either file an
issue for a clear use case, or re-override the user edit screen using custom
code.

Users who have been created by the IdP login process get no password, so they
can only log in locally after using Drupal's 'password reset email'
functionality. They only have acces to that if they have a role which is
"allowed to use Drupal login also when associated with a SAML login"

### Exposure of existing user names / email addresses

If a user cannot log in because the username / email provided in the SAML data
is already used by an existing account (which is linked to a different unique
ID already, or if the configuration disallows linking), the user sees a message
that the specific name / email already exists.

This (ability to probe your Drupal application for specific accounts existing)
can be considered exposure of private information. However:
- It can only be exploited by anyone, if your configuration does not require
  SAML messages to be signed, which is very much discouraged.
- Beyond that, you should be able to trust your IdP (administrators) to not
  probe your system.

If this is considered a problem, the corresponding UserVisibleExceptions should
be replaced by 'internal' exceptions.

OCCASIONALLY ASKED QUESTIONS
----------------------------

Q: How do I redirect users to a specific path after they logged in?

A: A specific login URL can do this: /saml/login?destination=drupal/path. To
instead have all users redirect to a specific destination, regardless of
which URL they used, there is a configuration setting "Login redirect URL".
(This configured URL can at the moment also contain tokens even though this
is not documented anywhere. Frankly I've never been sure whether it should;
it was just added in a contributed patch when the module wasn't very stable
yet. To make sure that the usage of this token does not disappear in a next
version of this module: notify me about how you are using this.)

Q: Does this module have an option to redirect all not-logged-in users to the
IdP login screen?

A: No. This is something that a separate module like require_login / r4032login
could do, with more fine-grained configuration options that we don't want to
duplicate. If there is a reason that this module cannot be used together
with the samlauth module, feel free to open an issue that clearly states why.

Q: The 'Metadata URL' (displayed in the configuration screen) has a wrong
protocol/host. How can I fix this?

A: Typically, this happens is when your Drupal installation is behind a reverse
proxy, and the URL is showing as the one seen by the proxy, instead of the
one that browsers should be using. This is often 'http://' where you need
'https://'. This is a general issue which has implications beyond this
module (e.g. it can influence URL values output by the metatag module).
Drupal has settings that must be configured to derive the original URL (as
used by the browser) from the proxy's HTTP headers. Documentation can be
found at https://www.drupal.org/docs/getting-started/installing-drupal/using-a-load-balancer-or-reverse-proxy.

Q: Is there a setting to disable this module's behavior on local installs?

The SAML functionality should not interfere with Drupal in any way; only
restrictions on using local Drupal login should be undone if necessary.

If the regular login screen is redirected to SAML login, undo this with:

`drush config:set samlauth.authentication login_auto_redirect 0`

If certain users/roles are restricted from logging in, undo this with:

`drush config:set --input-format=yaml samlauth.authentication drupal_login_roles '["authenticated"]'`

Note that this only applies if these users actually have a password to log in
locally. `drush uli` still works when above restrictions are in place.

### From Developers

Q: How can I act on the user / custom SAML attributes during user registration?

A: Subscribe to the SamlauthEvents::USER_SYNC event, where you can act on
both new and existing accounts. See the constant's definition for more info.
There should be no need to have an event listener subscribed to
ExternalAuthEvents::REGISTER (or, likely, ExternalAuthEvents::LOGIN). An
advantage of SamlauthEvents::USER_SYNC is that an exception can be thrown
during registration, before a (partly populated) user is saved.
