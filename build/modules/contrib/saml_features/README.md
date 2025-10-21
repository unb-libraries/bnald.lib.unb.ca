# UNB Libraries Saml
## saml_features

Custom support module to add UNB Libraries login form UI customizations

## Features
- Adds URL Alias `/login` to Saml Authentications `/saml_login` route
- Adds an alert to the Drupal core user login form that links to the Saml login route
- Replaces Drupal core login form error message references with Saml-based versions
- Administration settings may be configured at `/admin/config/saml_features/adminsettings`
  - STU references in the login text may be toggled on/off
  - Email/password field editing may enabled/disabled on the user profile form

## Installation Note
- Please increase the weight of the `saml_features` **module** array element to **11** in your Drupal repo's
 `config-yml/core.extension.yml` configuration file:

  ```
  module:
     ...
     externalauth: 10
     views: 10
     saml_features: 11
     ...
  ```

## Prerequisites
- This module depends on SAML Authentication module, 8.x-3.0-alpha2 version or better:
  - https://www.drupal.org/project/samlauth
- This module displays alerts in the **System Messages** block.
  Please ensure this block is placed/enabled in a region within your active theme's
   **Block layout**
   - Preferred placement is in the **Main content** region *above* the **Main page content** block.

## License
- saml_features is licensed under the MIT License:
  - http://opensource.org/licenses/mit-license.html
