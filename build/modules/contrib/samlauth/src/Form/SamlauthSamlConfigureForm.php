<?php

namespace Drupal\samlauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\key\Plugin\KeyPluginBase;
use Drupal\samlauth\Controller\SamlController;
use OneLogin\Saml2\Metadata;
use OneLogin\Saml2\Utils as SamlUtils;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for SAML protocol/comms related settings.
 *
 * SAML attributes are in other forms. This reads/writes the same config object
 * as SamlauthConfigureForm; the form is split up because configuration options
 * became unwieldy.
 */
class SamlauthSamlConfigureForm extends ConfigFormBase {
  use SamlauthConfigureTrait;

  /**
   * The Key repository service.
   *
   * This is used as an indicator whether we can show a 'Key' selector on
   * screen. This is when the key module is installed - not when the
   * key_asymmetric module is installed. (The latter is necessary for entering
   * public/private keys but reading them will work fine without it, it seems.)
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->keyRepository = $container->get('key.repository', ContainerInterface::NULL_ON_INVALID_REFERENCE);

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

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t("When the below sections are correctly configured, SAML login through the Identity Provider (IdP) should result in no errors from the PHP SAML toolkit. (Login to Drupal may still fail, because of e.g. missing 'unique ID'.)"),
    ];

    $form['service_provider'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Service Provider'),
      '#description' => $this->t("Metadata XML is not exposed by default; see <a href=\":permissions\">permissions</a>. XML contents are influenced by this configuration section, as well as others; those other options often don't matter for getting SAML communication to happen successfully.", [
        ':permissions' => Url::fromUri('base:admin/people/permissions', ['fragment' => 'module-samlauth'])->toString(),
      ]),
    ];

    $form['service_provider']['config_info'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Metadata URL: :url', [
          ':url' => Url::fromRoute('samlauth.saml_controller_metadata', [], ['absolute' => TRUE])->toString(),
        ]),
        $this->t('Assertion Consumer Service: :url', [
          ':url' => Url::fromRoute('samlauth.saml_controller_acs', [], ['absolute' => TRUE])->toString(),
        ]),
        $this->t('Single Logout Service: :url', [
          ':url' => Url::fromRoute('samlauth.saml_controller_sls', [], ['absolute' => TRUE])->toString(),
        ]),
      ],
      '#empty' => [],
      '#list_type' => 'ul',
    ];

    // Guessing: if caching < 10 minutes then it still needs to be adjusted.
    $form['service_provider']['caching'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Caching / Validity'),
      '#description' => $this->t('These values are low/off for newly installed sites, and should be raised/enabled when login is working.'),
    ];

    $value = $config->get('metadata_valid_secs');
    $default = $this->makeReadableDuration(Metadata::TIME_VALID);
    $this->addElementsFromSchema($form['service_provider']['caching'], $schema_definition, $config, [
      'metadata_valid_secs' => [
        '#type' => 'textfield',
        '#description' => $this->t('The maximum amount of time that the metadata (which is often cached by IdPs) should be considered valid, in readable format, e.g. "1 day 8 hours". The XML expresses "validUntil" as a specific date, so a HTTP cache will contain XML with slowly decreasing validity. The default (when left empty) is @default.', ['@default' => $default]),
        '#default_value' => $value ? $this->makeReadableDuration($value) : NULL,
      ],
      'metadata_cache_http' => [
        '#description' => $this->t("This affects just (Drupal's and external) response caches, whereas the above also affects caching by the IdP. Caching is only important if the metadata URL can be reached by anonymous visitors. The Max-Age value is derived from the validity."),
        // TRUE on existing installations where the checkbox didn't exist before;
        // FALSE on new installations.
        '#default_value' => $config->get('metadata_cache_http') ?? TRUE,
      ],
    ]);

    $this->addElementsFromSchema($form['service_provider'], $schema_definition, $config, [
      'security_metadata_sign' => $this->t('Add a UUID to the metadata XML and sign it (using the key whose public equivalent is published inside this same metadata).'),
      'sp_entity_id' => $this->t('An identifier for the SP. Free form (unless mandated by IdP administrators).'),
    ]);

    // Create options for cert/key type select element, and list of Keys for
    // 'key' select element.
    $nonconfig_type_options = ['key', 'file'];
    $key_cert_type_options = [
      'key_key' => $this->t('Key storage'),
      'file_file' => $this->t('File'),
      'config_config' => $this->t('Configuration'),
      'key_file' => $this->t('Key/File'),
      'key_config' => $this->t('Key/Config'),
      'file_config' => $this->t('File/Config'),
    ];
    $type_error = FALSE;
    // List of certs, for selection in IdP section.
    $selectable_public_certs = [];
    // List of certs referencing a private key, for selection in SP section.
    $selectable_public_keypairs = [];
    $referenced_private_key_ids = [];
    // List of keys that are selectable on their own, for selection in SP
    // section if the cert type is file/config; these are not necessarily
    // referenced from a certificate.
    $selectable_private_keys = [];
    if ($this->keyRepository) {
      $selectable_private_keys = $this->keyRepository->getKeyNamesAsOptions(['type' => 'asymmetric_private']);
      $keys = $this->keyRepository->getKeysByType('asymmetric_public');
      foreach ($keys as $public_key_id => $key) {
        $selectable_public_certs[$public_key_id] = $key->label();
        $key_type = $key->getKeyType();
        assert($key_type instanceof KeyPluginBase);
        $key_type_settings = $key_type->getConfiguration();
        if (!empty($key_type_settings['private_key'])) {
          $selectable_public_keypairs[$public_key_id] = $key->label();
          $referenced_private_key_ids[$public_key_id] = $key_type_settings['private_key'];
        }
      }
    }
    else {
      unset($key_cert_type_options['key_key'], $key_cert_type_options['key_file'], $key_cert_type_options['key_config']);
    }

    // Get cert + key; see which types they are and do custom checks.
    $sp_private_key = $config->get('sp_private_key') ?? '';
    $sp_cert = $config->get('sp_x509_certificate') ?? '';
    $sp_new_cert = $config->get('sp_new_certificate') ?? '';
    // @todo remove reference to $cert_folder in 4.x.
    $cert_folder = $config->get('sp_cert_folder');
    if ($cert_folder && is_string($cert_folder)) {
      // Update function hasn't run yet.
      $sp_private_key = "file:$cert_folder/certs/sp.key";
      $sp_cert = "file:$cert_folder/certs/sp.crt";
    }
    $sp_key_type = strstr($sp_private_key, ':', TRUE);
    if ($sp_key_type && in_array($sp_key_type, $nonconfig_type_options, TRUE)) {
      $sp_private_key = substr($sp_private_key, strlen($sp_key_type) + 1);
      if ($sp_key_type === 'key' && !isset($selectable_private_keys[$sp_private_key])) {
        // Warn if the key doesn't exist. If so, we don't want to mess with the
        // value (unlike when the cert doesn't exist; see below); let's add it
        // to the 'selectable keys' so validation doesn't fail.
        if ($this->keyRepository) {
          if (!$form_state->getUserInput()) {
            $this->messenger()->addWarning($this->t("Key entity '@key_name' for SP private key is missing.", [
              '@key_name' => $sp_private_key,
            ]));
          }
          $selectable_private_keys[$sp_private_key] = $this->t('@value (does not exist)', [
            '@value' => $sp_private_key,
          ]);
        }
        else {
          // ...except if we cannot display 'selectable keys' at all.
          if (!$form_state->getUserInput()) {
            $this->messenger()->addWarning($this->t('Key module is disabled even though the SP private key has Key storage configured.'));
          }
          $sp_private_key = "key:$sp_private_key";
          $sp_key_type = 'config';
        }
      }
    }
    elseif ($sp_private_key) {
      $type_error = (bool) $sp_key_type;
      $sp_key_type = 'config';
    }
    $sp_cert_type = strstr($sp_cert, ':', TRUE);
    if ($sp_cert_type && in_array($sp_cert_type, $nonconfig_type_options, TRUE)) {
      $sp_cert = substr($sp_cert, strlen($sp_cert_type) + 1);
      if ($sp_cert_type === 'key') {
        // Warn if the key doesn't exist; not on validation but on every form
        // display. Display the original value (including "key:") in the
        // 'config' textarea; if we put it in the select element just like the
        // private key, we'd need to alter the validation code too too (to not
        // derive the private key from this nonexistent key in this case). It
        // may now fail validation (unlike with missing files).
        if (!isset($selectable_public_keypairs[$sp_cert])) {
          if (!$form_state->getUserInput()) {
            if ($this->keyRepository) {
              // Text differs from key, b/c reasons can be slightly different.
              $this->messenger()->addWarning($this->t("Key entity '@key_name' for SP certificate is missing, or not referenced from a public certificate.", [
                '@key_name' => $sp_cert,
              ]));
            }
            else {
              $this->messenger()->addWarning($this->t('Key module is disabled even though the SP certificate has Key storage configured.'));
            }
          }
          $sp_cert = "key:$sp_cert";
          $sp_cert_type = 'config';
        }
        elseif ($sp_key_type === 'key' && $referenced_private_key_ids[$sp_cert] !== $sp_private_key) {
          // If our key exists but isn't referenced from our cert, we cannot
          // display both in our regular single 'keypair' selector. Move the
          // cert to the 'config' textarea so we display the private key in its
          // standalone keys select element.
          if (!$form_state->getUserInput()) {
            $this->messenger()->addWarning($this->t("Certificate '@cert_keyname' does not reference key '@key_keyname', which our UI cannot handle. The effect is that the certificate selection UI now probably looks confusing and may fail validation.", [
              '@cert_keyname' => $sp_cert,
              '@key_keyname' => $sp_private_key,
            ]));
          }
          $sp_cert = "key:$sp_cert";
          $sp_cert_type = 'config';
        }
      }
    }
    elseif ($sp_cert) {
      $type_error = (bool) $sp_cert_type;
      $sp_cert_type = 'config';
    }
    $sp_new_cert_type = $sp_new_cert ? strstr($sp_new_cert, ':', TRUE) : NULL;
    if ($sp_new_cert_type && in_array($sp_new_cert_type, $nonconfig_type_options, TRUE)) {
      $sp_new_cert = substr($sp_new_cert, strlen($sp_new_cert_type) + 1);
      if ($sp_new_cert_type === 'key' && !isset($selectable_public_keypairs[$sp_new_cert])) {
        if (!$form_state->getUserInput()) {
          if ($this->keyRepository) {
            $this->messenger()->addWarning($this->t("Key entity '@key_name' for new SP certificate is missing, or not referenced from a public certificate.", [
              '@key_name' => $sp_new_cert,
            ]));
          }
          else {
            $this->messenger()->addWarning($this->t('Key module is disabled even though the new SP certificate has Key storage configured.'));
          }
        }
        $sp_new_cert = "key:$sp_new_cert";
        $sp_new_cert_type = 'config';
      }
    }
    elseif ($sp_new_cert) {
      $type_error = (bool) $sp_new_cert_type;
      $sp_new_cert_type = 'config';
    }

    if (!$form_state->getUserInput()) {
      // Warn if the files don't exist; not on validation but on every form
      // display. (They may be missing if we're looking at a copy of the site,
      // and we still want to be able to test other form interactions.)
      // '@' suppression was added because of possible open_basedir restriction
      // but see comment at #3300383.
      if ($sp_key_type === 'file' && !@file_exists($sp_private_key)) {
        $this->messenger()->addWarning($this->t('SP private key file is missing or not accessible.'));
      }
      if ($sp_cert_type === 'file' && !@file_exists($sp_cert)) {
        $this->messenger()->addWarning($this->t('SP certificate file is missing or not accessible.'));
      }
      if ($sp_new_cert_type === 'file' && !@file_exists($sp_new_cert)) {
        $this->messenger()->addWarning($this->t('SP new certificate file is missing or not accessible.'));
      }
    }

    // Set default types if key/certificate values are not present yet.
    if (!$sp_key_type) {
      $sp_key_type = $this->keyRepository ? 'key' : 'file';
    }
    if (!$sp_cert_type) {
      if (!$sp_new_cert_type) {
        $sp_new_cert_type = $sp_key_type;
      }
      $sp_cert_type = $sp_new_cert_type;
    }
    elseif (!$sp_new_cert_type) {
      $sp_new_cert_type = $sp_cert_type;
    }

    // Check if these types make sense and, in case of key_key, the combination
    // of both keys can actually be presented as a keypair.
    $sp_key_cert_type = "{$sp_key_type}_{$sp_cert_type}";
    if ($type_error || $sp_new_cert_type !== $sp_cert_type || !isset($key_cert_type_options[$sp_key_cert_type])) {
      $sp_key_cert_type = '';
      $key_cert_type_options = ['' => '?'] + $key_cert_type_options;
      if (!$form_state->getUserInput()) {
        $this->messenger()->addWarning($this->t("Encountered an unexpected combination of SP key / certificate types (@value). The effect is that the UI probably looks confusing, without much clarity about which entries will get saved. Careful when editing.", [
          '@value' => "$sp_key_type / $sp_cert_type" . ($sp_new_cert ? " / $sp_new_cert_type" : ''),
        ]));
      }
    }

    // We have only a subselection of common/logical types, with 'key type'
    // being as least as safe as 'cert type'. If our actual stored types do not
    // match those OR the stored 'cert' and 'new cert' have different types, we
    // add another option '?' which will not hide any key/cert inputs.
    $form['service_provider']['sp_key_cert_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of values to save for the key/certificate'),
      '#description' => ($this->keyRepository ? $this->t('Key storage is most versatile.') . ' ' : '')
      . $this->t('File is generally considered more secure than configuration.'),
      '#options' => $key_cert_type_options,
      '#default_value' => $sp_key_cert_type,
    ];

    // @todo Links to pages that decode/show info about the key or cert.
    if ($this->keyRepository) {
      // We've decided on one selector for a keypair instead of separate ones
      // for certs and keys (even though we'll store them separately in
      // config), because forcing the user to create references to their
      // private keys is likely beneficial for longer term maintenance. This
      // means we don't show this selector for sp_key_cert_type "key_key".
      // Still, we set the #default_value also in that case which, while not
      // necessary for saving, can be good for the editing experience.
      $form['service_provider']['sp_key_key'] = [
        '#type' => 'select',
        '#title' => $this->t($schema_definition['sp_private_key']['label']),
        '#description' => $this->t('Add private keys in the <a href=":url">Keys</a> list.', [
          ':url' => Url::fromRoute('entity.key.collection')->toString(),
        ]),
        '#options' => $selectable_private_keys,
        '#empty_option' => $this->t('- Select a private key -'),
        '#default_value' => $sp_key_type === 'key' ? $sp_private_key : '',
        '#states' => [
          'visible' => [
            ':input[name="sp_key_cert_type"]' => [
              ['value' => 'key_file'],
              'or',
              ['value' => 'key_config'],
              'or',
              ['value' => ''],
            ],
          ],
        ],
      ];
    }
    $form['service_provider']['sp_key_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key filename'),
      '#default_value' => $sp_key_type === 'file' ? $sp_private_key : '',
      '#states' => [
        'visible' => [
          ':input[name="sp_key_cert_type"]' => [
            ['value' => 'file_file'],
            'or',
            ['value' => 'file_config'],
            'or',
            ['value' => ''],
          ],
        ],
      ],
    ];
    $form['service_provider']['sp_private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t($schema_definition['sp_private_key']['label']),
      '#description' => $this->t("Line breaks and '-----BEGIN/END' lines are optional."),
      '#default_value' => $sp_key_type === 'config' ? $this->formatKeyOrCert($sp_private_key, TRUE, TRUE) : '',
      '#states' => [
        'visible' => [
          ':input[name="sp_key_cert_type"]' => [
            ['value' => 'config_config'],
            'or',
            ['value' => ''],
          ],
        ],
      ],
    ];

    if ($this->keyRepository) {
      $form['service_provider']['sp_cert_key'] = [
        '#type' => 'select',
        '#title' => $this->t('X.509 certificate with attached private key'),
        '#description' => $this->t("Add private keys and certificates (don't forget to reference the private key) in the <a href=\":url\">Keys</a> list.", [
          ':url' => Url::fromRoute('entity.key.collection')->toString(),
        ]),
        '#options' => $selectable_public_keypairs,
        '#empty_option' => $this->t('- Select a certificate -'),
        '#default_value' => $sp_cert_type === 'key' ? $sp_cert : '',
        '#states' => [
          'visible' => [
            ':input[name="sp_key_cert_type"]' => [
              ['value' => 'key_key'],
              'or',
              ['value' => ''],
            ],
          ],
        ],
      ];
    }
    $form['service_provider']['sp_cert_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X.509 certificate filename'),
      '#default_value' => $sp_cert_type === 'file' ? $sp_cert : '',
      '#states' => [
        'visible' => [
          ':input[name="sp_key_cert_type"]' => [
            ['value' => 'file_file'],
            'or',
            ['value' => 'key_file'],
            'or',
            ['value' => ''],
          ],
        ],
      ],
    ];
    $form['service_provider']['sp_x509_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t($schema_definition['sp_x509_certificate']['label']),
      '#description' => $this->t("Line breaks and '-----BEGIN/END' lines are optional."),
      '#default_value' => $sp_cert_type === 'config' ? $this->formatKeyOrCert($sp_cert, TRUE) : '',
      '#states' => [
        'visible' => [
          ':input[name="sp_key_cert_type"]' => [
            ['value' => 'key_config'],
            'or',
            ['value' => 'file_config'],
            'or',
            ['value' => 'config_config'],
            'or',
            ['value' => ''],
          ],
        ],
      ],
    ];

    if ($this->keyRepository) {
      // We've decided on one selector for a keypair instead of separate ones
      // for certs and keys (even though we'll store them separately in
      // config), because forcing the user to create references to their
      // private keys is likely beneficial for longer term maintenance.
      $form['service_provider']['sp_new_cert_key'] = [
        '#type' => 'select',
        '#title' => $this->t($schema_definition['sp_new_certificate']['label']),
        '#description' => $this->t("Optional; not used for login, only added to the metadata. If you plan to replace the above key/certificate, the future certificate can be added here so the IdP can plan for the switch. Add the certificate in the <a href=\":url\">Keys</a> list. It must reference a key (even though that won't be used yet), so this cert/key pair is ready to be moved into production.", [
          ':url' => Url::fromRoute('entity.key.collection')->toString(),
        ]),
        '#options' => $selectable_public_keypairs,
        '#empty_option' => $this->t('- Select a certificate -'),
        '#default_value' => $sp_new_cert_type === 'key' ? $sp_new_cert : '',
        '#states' => [
          'visible' => [
            ':input[name="sp_key_cert_type"]' => [
              ['value' => 'key_key'],
              'or',
              ['value' => ''],
            ],
          ],
        ],
      ];
    }
    $form['service_provider']['sp_new_cert_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New X.509 certificate filename'),
      '#description' => $this->t('Optional; not used for login, only added to the metadata. If you plan to replace the above key/certificate, the future certificate can be added here so the IdP can plan for the switch.'),
      '#default_value' => $sp_new_cert_type === 'file' ? $sp_new_cert : '',
      '#states' => [
        'visible' => [
          ':input[name="sp_key_cert_type"]' => [
            ['value' => 'key_file'],
            'or',
            ['value' => 'file_file'],
            'or',
            ['value' => ''],
          ],
        ],
      ],
    ];
    $form['service_provider']['sp_new_cert'] = [
      '#type' => 'textarea',
      '#title' => $this->t($schema_definition['sp_new_certificate']['label']),
      '#description' => $this->t("Optional; not used for login, only added to the metadata. If you plan to replace the above key/certificate, the future certificate can be added here so the IdP can plan for the switch. Line breaks and '-----BEGIN/END' lines are optional."),
      '#default_value' => $sp_new_cert_type === 'config' ? $this->formatKeyOrCert($sp_new_cert, TRUE) : '',
      '#states' => [
        'visible' => [
          ':input[name="sp_key_cert_type"]' => [
            ['value' => 'key_config'],
            'or',
            ['value' => 'file_config'],
            'or',
            ['value' => 'config_config'],
            'or',
            ['value' => ''],
          ],
        ],
      ],
    ];

    $form['identity_provider'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Identity Provider'),
    ];

    // @todo Allow a user to automagically populate this by providing a
    //   metadata URL for the IdP. OneLogin's IdPMetadataParser can likely help.
    // $form['identity_provider']['idp_metadata_url'] = [
    // '#type' => 'url',
    // '#title' => $this->t('Metadata URL'),
    // '#description' => $this->t('URL of the XML metadata for the IdP.'),
    // '#default_value' => $config->get('idp_metadata_url'),
    // ];
    $this->addElementsFromSchema($form['identity_provider'], $schema_definition, $config, [
      'idp_entity_id' => $this->t('The identifier for the IdP.'),
      'idp_single_sign_on_service' => $this->t('URL where the SP will direct authentication requests.'),
      'idp_single_log_out_service' => $this->t('URL where the SP will direct logout requests.'),
    ]);

    $certs = $config->get('idp_certs');
    $encryption_cert = $config->get('idp_cert_encryption');
    // @todo remove this block; idp_cert_type was removed in 3.3.
    if (!$certs && !$encryption_cert) {
      $value = $config->get('idp_x509_certificate');
      $certs = $value ? [$value] : [];
      $value = $config->get('idp_x509_certificate_multi');
      if ($value) {
        if ($config->get('idp_cert_type') === 'encryption') {
          $encryption_cert = $value;
        }
        else {
          $certs[] = $value;
        }
      }
    }
    // Check if all certs are of the same type. The SSO part of the module can
    // handle that fine (if someone saved the configuration that way) but the
    // UI cannot; it would make things look more complicated and I don't see a
    // reason to do so.
    $cert_types = $encryption_cert ? strstr($encryption_cert, ':', TRUE) : NULL;
    foreach ($certs as $value) {
      $cert_type = strstr($value, ':', TRUE);
      if (!$cert_type) {
        $cert_type = 'config';
      }
      if ($cert_types && $cert_types !== $cert_type) {
        if (!$form_state->getUserInput()) {
          $this->messenger()->addWarning($this->t("IdP certificates are not all of the same type. The effect is that the UI probably looks confusing, without much clarity about which entries will get saved. Careful when editing."));
        }
        $cert_types = ':';
        break;
      }
      $cert_types = $cert_type;
    }

    $options = [
      'file' => $this->t('File'),
      'config' => $this->t('Configuration'),
    ];
    if ($this->keyRepository) {
      $options = ['key' => $this->t('Key storage')] + $options;
    }
    if ($cert_types && !isset($options[$cert_types])) {
      $options = ['' => '?'] + $options;
    }
    if (!$cert_types) {
      $cert_types = $this->keyRepository ? 'key' : 'file';
    }
    $form['identity_provider']['idp_cert_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of values to save for the certificate(s)'),
      '#options' => $options,
      '#default_value' => isset($options[$cert_types]) ? $cert_types : '',
    ];

    $form['identity_provider']['idp_certs'] = [
      // @todo sometime: 'multivalue'... if #1091852 has been solved for a long
      //   time so we don't need the #description_suffix anymore.
      '#type' => 'samlmultivalue',
      '#add_empty' => FALSE,
      '#title' => $this->t($schema_definition['idp_certs']['label']),
      '#description' => $this->t('Public X.509 certificate(s) of the IdP, used for validating signatures (and by default also for encryption).'),
      '#add_more_label' => $this->t('Add extra certificate'),
    ];
    if ($this->keyRepository) {
      $form['identity_provider']['idp_certs']['key'] = [
        '#type' => 'select',
        '#title' => $this->t($schema_definition['idp_certs']['sequence']['label']),
        '#description' => $this->t('Add certificates in the <a href=":url">Keys</a> list.', [
          ':url' => Url::fromRoute('entity.key.collection')->toString(),
        ]),
        '#options' => $selectable_public_certs,
        '#empty_option' => $this->t('- Select a certificate -'),
        '#states' => [
          'visible' => [
            ':input[name="idp_cert_type"]' => [
              ['value' => 'key'],
              'or',
              ['value' => ''],
            ],
          ],
        ],
      ];
    }
    $form['identity_provider']['idp_certs'] += [
      'file' => [
        '#type' => 'textfield',
        '#title' => $this->t('Certificate filename'),
        '#states' => [
          'visible' => [
            ':input[name="idp_cert_type"]' => [
              ['value' => 'file'],
              'or',
              ['value' => ''],
            ],
          ],
        ],
      ],
      'cert' => [
        '#type' => 'textarea',
        '#title' => $this->t($schema_definition['idp_certs']['sequence']['label']),
        '#description' => $this->t("Line breaks and '-----BEGIN/END' lines are optional."),
        '#states' => [
          'visible' => [
            ':input[name="idp_cert_type"]' => [
              ['value' => 'config'],
              'or',
              ['value' => ''],
            ],
          ],
        ],
      ],
      // Bug #1091852 keeps all child elements visible. This JS was an attempt
      // at fixing this but makes them all invisible, which is worse. (Note we
      // cannot just make JS that hides the ones we need to hide, because then
      // they don't respond to #states changes anymore.)
      // '#attached' => ['library' => ['samlauth/fix1091852']],.
    ];
    if ($this->getRequest()->getMethod() === 'POST') {
      // We hacked #description_suffix into MultiValue.
      $form['identity_provider']['idp_certs']['#description_suffix'] = $this->t('<div class="messages messages--warning"><strong>Apologies if multiple types of input elements are visible in every row. Please fill only the appropriate type, or re-select the "Type of values" above.</strong></div>');
    }
    if ($certs) {
      $form['identity_provider']['idp_certs']['#default_value'] = [];
      foreach ($certs as $index => $value) {
        $cert_type = strstr($value, ':', TRUE);
        $form['identity_provider']['idp_certs']['#default_value'][] =
          in_array($cert_type, ['key', 'file'], TRUE)
            ? [$cert_type => substr($value, strlen($cert_type) + 1)]
            : ['cert' => $this->formatKeyOrCert($value, TRUE)];
        if (!$form_state->getUserInput() && $cert_type === 'file' && !@file_exists(substr($value, 5))) {
          $this->messenger()->addWarning($this->t('IdP certificate file@index is missing or not accessible.', [
            '@index' => $index ? " $index" : '',
          ]));
        }
      }
    }

    $description = $this->t("Optional public X.509 certificate used for encrypting the NameID in logout requests (if specified below). If left empty, the first certificate above is used for encryption too.");
    if ($this->keyRepository) {
      // It is odd to make disabled-ness depend on a security checkbox that is
      // furthe down below, but at least this makes clear that this encryption
      // cert is only used for one very specific thing. Also, it is likely that
      // only very few installations use a separate encryption certificate.
      $form['identity_provider']['idp_certkey_encryption'] = [
        '#type' => 'select',
        '#title' => $this->t($schema_definition['idp_cert_encryption']['label']),
        '#description' => $description,
        '#default_value' => $cert_types === 'key' && $encryption_cert ? substr($encryption_cert, 4) : '',
        '#options' => $selectable_public_certs,
        '#empty_option' => $this->t('- Select a certificate -'),
        '#states' => [
          'visible' => [
            ':input[name="idp_cert_type"]' => [
              ['value' => 'key'],
              'or',
              ['value' => ''],
            ],
          ],
          'disabled' => [
            ':input[name="security_nameid_encrypt"]' => ['checked' => FALSE],
          ],
        ],
      ];
    }
    $form['identity_provider']['idp_certfile_encryption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encryption certificate filename'),
      '#description' => $description,
      '#default_value' => $cert_types === 'file' && $encryption_cert ? substr($encryption_cert, 5) : '',
      '#states' => [
        'visible' => [
          ':input[name="idp_cert_type"]' => [
            ['value' => 'file'],
            'or',
            ['value' => ''],
          ],
        ],
        'disabled' => [
          ':input[name="security_nameid_encrypt"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['identity_provider']['idp_cert_encryption'] = [
      '#type' => 'textarea',
      '#title' => $this->t($schema_definition['idp_cert_encryption']['label']),
      '#description' => $description,
      '#default_value' => $cert_types === 'config' && $encryption_cert ? $this->formatKeyOrCert($encryption_cert, TRUE) : '',
      '#states' => [
        'visible' => [
          ':input[name="idp_cert_type"]' => [
            ['value' => 'config'],
            'or',
            ['value' => ''],
          ],
        ],
        'disabled' => [
          ':input[name="security_nameid_encrypt"]' => ['checked' => FALSE],
        ],
      ],
    ];
    if (!$form_state->getUserInput() && $cert_types === 'file' && $encryption_cert && !@file_exists(substr($encryption_cert, 5))) {
      $this->messenger()->addWarning($this->t('IdP encryption certificate file is missing or not accessible.'));
    }

    $form['construction'] = [
      '#title' => $this->t('SAML Message Construction'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $this->addElementsFromSchema($form['construction'], $schema_definition, $config, [
      'security_authn_requests_sign' => $this->t('Requests sent to the Single Sign-On Service of the IdP will include a signature.'). '*',
      'security_logout_requests_sign' => $this->t('Requests sent to the Single Logout Service of the IdP will include a signature.'),
      'security_logout_responses_sign' => $this->t('Responses sent back to the IdP will include a signature.'),
      'security_signature_algorithm' => [
        '#type' => 'select',
        '#options' => [
          '' => $this->t('library default'),
          XMLSecurityKey::RSA_SHA1 => 'RSA-SHA1',
          XMLSecurityKey::HMAC_SHA1 => 'HMAC-SHA1',
          XMLSecurityKey::RSA_SHA256 => 'SHA256',
          XMLSecurityKey::RSA_SHA384 => 'SHA384',
          XMLSecurityKey::RSA_SHA512 => 'SHA512',
        ],
        '#description' => $this->t('Algorithm used by the signing process.'),
        '#states' => [
          'disabled' => [
            ':input[name="security_authn_requests_sign"]' => ['checked' => FALSE],
            ':input[name="security_logout_requests_sign"]' => ['checked' => FALSE],
            ':input[name="security_logout_responses_sign"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'security_nameid_encrypt' => $this->t("The NameID included in requests sent to the Single Logout Service of the IdP is encrypted."),
      'security_encryption_algorithm' => [
        '#type' => 'select',
        // I am not a crypto expert and do not know if we can/should add
        // AESnnn/GCM and others here as well. The library default can be found
        // in the Utils::generateNameId() definition.
        '#options' => [
          '' => $this->t('library default'),
          XMLSecurityKey::AES128_CBC => 'AES128/CBC',
          XMLSecurityKey::AES192_CBC => 'AES192/CBC',
          XMLSecurityKey::AES256_CBC => 'AES256/CBC',
        ],
        '#description' => $this->t('Algorithm used by the encryption process.'),
        '#states' => [
          'disabled' => [
            ':input[name="security_nameid_encrypt"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'security_request_authn_context' => $this->t('Specify that only a subset of authentication methods available at the IdP should be used. (When enabled, the "PasswordProtectedTransport" authentication method is specified, which is default behavior for the SAML Toolkit library. If needed, this module should be extended to be able to specify more methods.)'),
      'request_set_name_id_policy' => [
        '#description' => $this->t('A NameIDPolicy element is added in authentication requests, mentioning the below format (if "Require NameID to be encrypted" is off).'),
        // This is one of the few checkboxes that must be TRUE on existing
        // installations where the checkbox didn't exist before (in older module
        // versions). Others get their default only from the config/install yml.
        '#default_value' => $config->get('request_set_name_id_policy') ?? TRUE,
      ],
    ]);

    $this->addNameID($form['construction'], $schema_definition, $config);

    $form['construction']['description'] = [
      '#type' => 'markup',
      '#markup' => '*: ' . $this->t('These options also influence the SP metadata. (They are mentioned as an attribute or child element of the SPSSODescriptor element.)'),
    ];

    $form['responses'] = [
      '#title' => $this->t('SAML Message Validation'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $this->addElementsFromSchema($form['responses'], $schema_definition, $config, [
      'security_want_name_id' => [
        '#description' => $this->t('The authentication response from the IdP must contain a NameID attribute.'),
        // See request_set_name_id_policy.
        '#default_value' => $config->get('security_want_name_id') ?? TRUE,
      ],
      'security_allow_repeat_attribute_name' => $this->t('Do not raise an error when the authentication response contains duplicate attribute elements with the same name.'),
      // This option's default value is FALSE but according to the SAML spec,
      // signing parameters should always be retrieved from the original request
      // instead of recalculated. (As argued in e.g.
      // https://github.com/onelogin/php-saml/issues/130.) The 'TRUE' option
      // (which was implemented in #6a828bf, as a result of
      // https://github.com/onelogin/php-saml/pull/37) reads the parameters from
      // $_SERVER['REQUEST'] but unfortunately this is not always populated in
      // all PHP/webserver configurations. IMHO the code should have a fallback
      // to other 'self encoding' methods if $_SERVER['REQUEST'] is empty; I see
      // no downside to that and it would enable us to always set TRUE / get rid
      // of this option in a future version of the SAML Toolkit library.
      // @todo file PR against SAML toolkit; note it in https://www.drupal.org/project/samlauth/issues/3131028
      'security_logout_reuse_sigs' => $this->t('Validation of logout requests/responses can fail on some IdPs (among others, ADFS) if this option is not set. This happens independently of the  "Strict validation" option.'),
      'strict' => $this->t('Validation failures (partly based on the next options) will cause the SAML conversation to be terminated. In production environments, this <em>must</em> be set.'),
      'security_messages_sign' => [
        '#description' => $this->t('Responses (and logout requests) from the IdP are expected to be signed.'),
        '#states' => [
          'disabled' => [
            ':input[name="strict"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'security_assertions_signed' => $this->t('Assertion elements in authentication responses from the IdP are expected to be signed.') . '*',
      'security_assertions_encrypt' => $this->t('Assertion elements in responses from the IdP are expected to be encrypted.') . '*',
      'security_nameid_encrypted' => $this->t('The NameID in login responses from the IdP is expected to be encrypted. This overrides the requested NameID Format and sets "Encrypted" in authentication requests\' NameIDPolicy element.') . '*',
    ]);

    // Untll #description_display works: (#314385)
    $form['responses']['description'] = [
      '#type' => 'markup',
      '#markup' => '*: ' . $this->t('These checks are not done when strict validation is turned off, but the settings also influence the SP metadata. (The "signed" value is mentioned as an attribute of the SPSSODescriptor element. The "encrypted" options add an extra "encryption" certificate descriptor element when enabled.)'),
    ];

    $form['other'] = [
      '#title' => $this->t('Other / deprecated'),
      '#type' => 'details',
      '#description' => $this->t('This section should not need changes. Some options are Drupal related, not SAML related.'),
      '#open' => FALSE,
    ];

    $this->addElementsFromSchema($form['other'], $schema_definition, $config, [
      // This option has effect on signing of (login + logout) requests and
      // logout responses. It's badly named (in the SAML Toolkit;
      // "lowercaseUrlencoding") because there has never been any connection to
      // the case of URL-encoded strings. The only thing this does is use
      // rawurlencode() rather than urlencode() for URL encoding of signatures
      // sent to the IdP. This option arguably shouldn't even exist because the
      // use of urlencode() arguably is a bug that should just have been fixed.
      // (The name "lowercaseUrlencoding" seems to come from a mistake: it
      // originates from https://github.com/onelogin/python-saml/pull/144/files,
      // a PR for the signature validation code for incoming messages, which was
      // then mentioned in https://github.com/onelogin/php-saml/issues/136.
      // However, the latter / this option is about signature generation for
      // outgoing messages. Validation concerns different code, and is influenced
      // by the 'security_logout_reuse_sigs' option below, which has its own
      // issues.) This means that the default value should actually be TRUE.
      // @todo file PR against SAML toolkit; note it in https://www.drupal.org/project/samlauth/issues/3131028
      // @todo change default to TRUE; amend description (and d.o issue, and README)
      'security_lowercase_url_encoding' => [
        '#description' => $this->t("If there is ever a reason to turn this option off, a bug report is greatly appreciated. (The module author believes this option is unnecessary and plans for a PR to the SAML Toolkit to re-document it / phase it out. If you installed this module prior to 8.x-3.0-alpha2 and this option is turned off already, that's fine - changing it should make no difference.)"),
        '#states' => [
          'disabled' => [
            ':input[name="security_authn_requests_sign"]' => ['checked' => FALSE],
            ':input[name="security_logout_requests_sign"]' => ['checked' => FALSE],
            ':input[name="security_logout_responses_sign"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'use_base_url' => $this->t('This is supposedly a better version of the next option that works for all Drupal configurations and (for reverse proxies) only uses HTTP headers/hostnames when you configured them as <a href=":trusted">trusted</a>. Please turn this on, and file an issue if it doesn\'t work for you; it will be standard and non-configurable in the next major module version.', [
        ':trusted' => 'https://www.drupal.org/docs/installing-drupal/trusted-host-settings#s-trusted-host-security-setting-in-drupal-8',
      ]),
      'use_proxy_headers' => [
        '#description' => $this->t("The SAML Toolkit will use 'X-Forwarded-*' HTTP headers (if present) for constructing/identifying the SP URL in sent/received messages. This used to be necessary if your SP is behind a reverse proxy."),
        '#states' => [
          'disabled' => [
            ':input[name="use_base_url"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ]);

    $form['debugging'] = [
      '#title' => $this->t('Debugging'),
      '#type' => 'details',
      '#description' => $this->t('When turning off debugging options to go into production mode, re-enable above "Strict validation" and "Caching / validity" options.'),
      '#open' => TRUE,
    ];

    $this->addElementsFromSchema($form['debugging'], $schema_definition, $config, [
      // A note - if we ever split this config screen in two: this option does
      // not govern just SAML communication but all errors during login.
      'debug_display_error_details' => $this->t("This can help testing until login/logout works: when disabled, technical details are only logged to watchdog (to prevent exposing information about a misconfigured system / because it's unlikely they are useful)."),
      'debug_log_saml_out' => $this->t("Log messages which the SAML Toolkit 'sends' to the IdP (usually via the web browser through a HTTP redirect, as part of the URL)."),
      'debug_log_saml_in' => $this->t('Log SAML responses (and logout requests) received by the ACS/SLS endpoints.'),
      'debug_log_in' => $this->t('Log supposed SAML messages received by the ACS/SLS endpoints before validating them as XML. If the other option logs nothing, this still might, but the logged contents may make less sense.'),
      'debug_phpsaml' => $this->t('The exact benefit is unclear; as of library v3.4, this prints out certain validation errors to STDOUT / syslog, many of which would also be reported by other means. However, that might change...'),
    ]);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $duration = $form_state->getValue('metadata_valid_secs');
    if ($duration || $duration == '0') {
      $duration = $this->parseReadableDuration($form_state->getValue('metadata_valid_secs'));
      if (!$duration) {
        $form_state->setErrorByName('metadata_valid_secs', $this->t('Invalid period value.'));
      }
    }

    // @todo Validate key/certs. Might be able to just openssl_x509_parse().
    $sp_key_type = $form_state->getValue('sp_key_cert_type');
    if ($sp_key_type) {
      [$sp_key_type, $sp_cert_type] = explode('_', $sp_key_type, 2);
    }
    else {
      $sp_cert_type = '';
    }
    $keyname = $form_state->getValue('sp_key_key');
    $cert_keyname = $form_state->getValue('sp_cert_key');
    if (in_array($sp_cert_type, ['', 'key']) && $cert_keyname && ($sp_key_type === 'key' || !$sp_key_type && !$keyname)) {
      // The select element for the private key is invisible. Get it from the
      // cert (except if that is empty; then we don't really care what happens
      // at this stage; we'll warn while displaying the form).
      $key = $this->keyRepository->getKey($cert_keyname);
      if ($key) {
        $key_type = $key->getKeyType();
        assert($key_type instanceof KeyPluginBase);
        $key_type_settings = $key_type->getConfiguration();
        if (!empty($key_type_settings['private_key'])) {
          $key = $this->keyRepository->getKey($key_type_settings['private_key']);
        }
      }
      $form_state->setValue('sp_key_key', $key ? $key->id() : '');
    }
    $filename = $form_state->getValue('sp_key_file');
    $full_cert = $form_state->getValue('sp_private_key');
    // There are 4 elements that reference the key. At least 3 must be empty or
    // invisible. (Checking $sp_key_type=='' is enough to determine if multiple
    // elements are visible.)
    if (!$sp_key_type && (((int) empty($keyname)) + ((int) empty($cert_keyname)) + ((int) empty($filename)) + ((int) empty($full_cert))) < 3) {
      $form_state->setErrorByName("sp_private_key", $this->t('Only one private key (filename) element must be populated.'));
    }

    $filename = $form_state->getValue('sp_cert_file');
    $full_cert = $form_state->getValue('sp_x509_certificate');
    if (!$sp_cert_type && (($cert_keyname && $filename) || ($cert_keyname && $full_cert) || ($filename && $full_cert))) {
      $form_state->setErrorByName("sp_private_key", $this->t('Only one certificate (filename) element must be populated.'));
    }
    $keyname = $form_state->getValue('sp_new_cert_key');
    $filename = $form_state->getValue('sp_new_cert_file');
    $full_cert = $form_state->getValue('sp_new_cert');
    if ($filename && in_array($sp_cert_type, ['', 'file']) && $filename[0] !== '/') {
      $form_state->setErrorByName("sp_private_key", $this->t('Only one new certificate (filename) element must be populated.'));
    }
    if (!$sp_cert_type && (($keyname && $filename) || ($keyname && $full_cert) || ($filename && $full_cert))) {
      $form_state->setErrorByName("sp_new_cert", $this->t('Only one new certificate (filename) element must be populated.'));
    }

    $idp_cert_type = $form_state->getValue('idp_cert_type');
    $idp_certs = $form_state->getValue('idp_certs');
    foreach ($idp_certs as $index => $item) {
      if (!$idp_cert_type && ((!empty($item['key']) && !empty($item['file'])) || (!empty($item['key']) && !empty($item['cert'])) || (!empty($item['file']) && !empty($item['cert'])))) {
        $form_state->setErrorByName("idp_certs][$index][cert", $this->t('Only one new certificate (filename) element must be populated per row.'));
      }
    }
    $keyname = $form_state->getValue('idp_certkey_encryption');
    $filename = $form_state->getValue('idp_certfile_encryption');
    $full_cert = $form_state->getValue('idp_cert_encryption');
    if (!$idp_cert_type && (($keyname && $filename) || ($keyname && $full_cert) || ($filename && $full_cert))) {
      $form_state->setErrorByName("idp_cert_encryption", $this->t('IdP certificate and filename cannot both be set.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(SamlController::CONFIG_OBJECT_NAME);

    $sp_key_type = $form_state->getValue('sp_key_cert_type');
    if ($sp_key_type) {
      [$sp_key_type, $sp_cert_type] = explode('_', $sp_key_type, 2);
    }
    else {
      $sp_cert_type = '';
    }
    // We validated that max. 1 of the values is set if $sp_key/cert_type == ''.
    // If $sp_key/cert_type is nonempty, other values may be set which we must
    // explicitly skip.
    $sp_private_key = $form_state->getValue('sp_key_key');
    if ($sp_private_key && in_array($sp_key_type, ['', 'key'])) {
      // If 'key', the value was changed to the appropriate one in the
      // validate function (if necessary).
      $sp_private_key = "key:$sp_private_key";
    }
    if (!$sp_private_key && in_array($sp_key_type, ['', 'file'])) {
      $sp_private_key = $form_state->getValue('sp_key_file');
      if ($sp_private_key) {
        $sp_private_key = "file:$sp_private_key";
      }
    }
    if (!$sp_private_key && in_array($sp_key_type, ['', 'config'])) {
      $sp_private_key = $form_state->getValue('sp_private_key');
      if ($sp_private_key) {
        $sp_private_key = $this->formatKeyOrCert($sp_private_key, FALSE, TRUE);
      }
    }

    $sp_cert = $form_state->getValue('sp_cert_key');
    if ($sp_cert && in_array($sp_cert_type, ['', 'key'])) {
      // If 'key', the value was changed to the appropriate one in the
      // validate function (if necessary).
      $sp_cert = "key:$sp_cert";
    }
    if (!$sp_cert && in_array($sp_cert_type, ['', 'file'])) {
      $sp_cert = $form_state->getValue('sp_cert_file');
      if ($sp_cert) {
        $sp_cert = "file:$sp_cert";
      }
    }
    if (!$sp_cert && in_array($sp_cert_type, ['', 'config'])) {
      $sp_cert = $form_state->getValue('sp_x509_certificate');
      if ($sp_cert) {
        $sp_cert = $this->formatKeyOrCert($sp_cert, FALSE);
      }
    }

    $sp_new_cert = $form_state->getValue('sp_new_cert_key');
    if ($sp_new_cert && in_array($sp_cert_type, ['', 'key'])) {
      // If 'key', the value was changed to the appropriate one in the
      // validate function (if necessary).
      $sp_new_cert = "key:$sp_new_cert";
    }
    if (!$sp_new_cert && in_array($sp_cert_type, ['', 'file'])) {
      $sp_new_cert = $form_state->getValue('sp_new_cert_file');
      if ($sp_new_cert) {
        $sp_new_cert = "file:$sp_new_cert";
      }
    }
    if (!$sp_new_cert && in_array($sp_cert_type, ['', 'config'])) {
      $sp_new_cert = $form_state->getValue('sp_new_cert');
      if ($sp_new_cert) {
        $sp_new_cert = $this->formatKeyOrCert($sp_new_cert, FALSE);
      }
    }

    $idp_cert_type = $form_state->getValue('idp_cert_type');
    $idp_certs = [];
    foreach ($form_state->getValue('idp_certs') as $item) {
      // We validated that max. 1 of the values is set if $idp_cert_type == ''.
      if (!empty($item['key']) && in_array($idp_cert_type, ['', 'key'])) {
        $idp_certs[] = "key:{$item['key']}";
      }
      if (!empty($item['file']) && in_array($idp_cert_type, ['', 'file'])) {
        $idp_certs[] = "file:{$item['file']}";
      }
      if (!empty($item['cert']) && in_array($idp_cert_type, ['', 'config'])) {
        $idp_certs[] = $this->formatKeyOrCert($item['cert'], FALSE);
      }
    }
    $idp_cert_encryption = $form_state->getValue('idp_certkey_encryption');
    if ($idp_cert_encryption && in_array($idp_cert_type, ['', 'key'])) {
      // If 'key', the value was changed to the appropriate one in the
      // validate function (if necessary).
      $idp_cert_encryption = "key:$idp_cert_encryption";
    }
    if (!$idp_cert_encryption && in_array($idp_cert_type, ['', 'file'])) {
      $idp_cert_encryption = $form_state->getValue('idp_certfile_encryption');
      if ($idp_cert_encryption) {
        $idp_cert_encryption = "file:$idp_cert_encryption";
      }
    }
    if (!$idp_cert_encryption && in_array($idp_cert_type, ['', 'config'])) {
      $idp_cert_encryption = $form_state->getValue('idp_cert_encryption');
      if ($idp_cert_encryption) {
        $idp_cert_encryption = $this->formatKeyOrCert($idp_cert_encryption, FALSE);
      }
    }

    $config->set('sp_x509_certificate', $sp_cert)
      ->set('sp_new_certificate', $sp_new_cert)
      ->set('sp_private_key', $sp_private_key)
      ->set('idp_certs', $idp_certs)
      ->set('idp_cert_encryption', $idp_cert_encryption)
      ->clear('sp_cert_folder');

    // This is never 0 but can be ''. (NULL would mean same as ''.) Unlike
    // others, this value needs to be unset if empty.
    $metadata_valid = $form_state->getValue('metadata_valid_secs');
    if ($metadata_valid) {
      $config->set('metadata_valid_secs', $this->parseReadableDuration($metadata_valid));
    }
    else {
      $config->clear('metadata_valid_secs');
    }

    $this->setNameID($form_state, $config);

    foreach ([
      'metadata_valid_secs',
      'metadata_cache_http',
      'security_metadata_sign',
      'sp_entity_id',
      'idp_entity_id',
      'idp_single_sign_on_service',
      'idp_single_log_out_service',
      'security_authn_requests_sign',
      'security_logout_requests_sign',
      'security_logout_responses_sign',
      'security_signature_algorithm',
      'security_nameid_encrypt',
      'security_encryption_algorithm',
      'security_request_authn_context',
      'request_set_name_id_policy',
      'security_want_name_id',
      'security_allow_repeat_attribute_name',
      'security_logout_reuse_sigs',
      'strict',
      'security_messages_sign',
      'security_assertions_signed',
      'security_assertions_encrypt',
      'security_nameid_encrypted',
      'security_lowercase_url_encoding',
      'use_proxy_headers',
      'use_base_url',
      'debug_display_error_details',
      'debug_log_saml_out',
      'debug_log_saml_in',
      'debug_log_in',
      'debug_phpsaml',
    ] as $config_value) {
      $config->set($config_value, $form_state->getValue($config_value));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Format a long string in PEM format, or remove PEM format.
   *
   * Our configuration stores unformatted key/cert values, which is what we
   * would get from SAML metadata and what the SAML toolkit expects. But
   * displaying them formatted in a textbox is better for humans, and also
   * allows us to paste PEM-formatted values (as well as unformatted) into the
   * textbox and not have to remove all the newlines manually, if we got them
   * delivered this way.
   *
   * The side effect is that certificates/keys are re- and un-formatted on
   * every save operation, but that should be OK.
   *
   * @param string|null $value
   *   A certificate or private key, either with or without head/footer.
   * @param bool $heads
   *   True to format and include head and footer; False to remove them and
   *   return one string without spaces / line breaks.
   * @param bool $key
   *   (optional) True if this is a private key rather than a certificate.
   *
   * @return string
   *   (Un)formatted key or cert.
   */
  protected function formatKeyOrCert($value, $heads, $key = FALSE) {
    // If the string contains a colon, it's probably a "key:" config value
    // that we placed in the certificate element because we have no other
    // place for it. Leave it alone (and if it fails validation, so be it).
    if (is_string($value) && strpos($value, ':') === FALSE) {
      $value = $key ?
        SamlUtils::formatPrivateKey($value, $heads) :
        SamlUtils::formatCert($value, $heads);
    }
    return $value;
  }

  /**
   * Converts number of seconds into a human readable 'duration' string.
   *
   * @param int $seconds
   *   Number of seconds.
   *
   * @return string
   *   The human readable duration description (e.g. "5 hours 3 minutes").
   */
  protected function makeReadableDuration($seconds) {
    $calculation = [
      'week' => 3600 * 24 * 7,
      'day' => 3600 * 24,
      'hour' => 3600,
      'minute' => 60,
      'second' => 1,
    ];

    $duration = '';
    foreach ($calculation as $unit => $unit_amount) {
      $amount = (int) ($seconds / $unit_amount);
      if ($amount) {
        if ($duration) {
          $duration .= ', ';
        }
        $duration .= "$amount $unit" . ($amount > 1 ? 's' : '');
      }
      $seconds -= $amount * $unit_amount;
    }

    return $duration;
  }

  /**
   * Parses a human readable 'duration' string.
   *
   * @param string $expression
   *   The human readable duration description (e.g. "5 hours 3 minutes").
   *
   * @return int
   *   The number of seconds; 0 implies invalid duration.
   */
  protected function parseReadableDuration($expression) {
    $calculation = [
      'week' => 3600 * 24 * 7,
      'day' => 3600 * 24,
      'hour' => 3600,
      'minute' => 60,
      'second' => 1,
    ];
    $expression = strtolower(trim($expression));
    if (substr($expression, -1) === '.') {
      $expression = rtrim(substr($expression, 0, strlen($expression) - 1));
    }
    $seconds = 0;
    $seen = [];
    // Numbers must be numeric. Valid: "X hours Y minutes" possibly separated
    // by comma or "and". Months/years are not accepted because their length is
    // ambiguous.
    $parts = preg_split('/(\s+|\s*,\s*|\s+and\s+)(?=\d)/', $expression);
    foreach ($parts as $part) {
      if (!preg_match('/^(\d+)\s*((?:week|day|hour|min(?:ute)?|sec(?:ond)?)s?)$/', $part, $matches)) {
        return 0;
      }
      if (substr($matches[2], -1) === 's') {
        $matches[2] = substr($matches[2], 0, strlen($matches[2]) - 1);
      }
      elseif ($matches[1] != 1 && !in_array($matches[2], ['min', 'sec'], TRUE)) {
        // We allow "1 min", "1 mins", "2 min", not "2 minute".
        return 0;
      }
      $unit = $matches[2] === 'min' ? 'minute' : ($matches[2] === 'sec' ? 'second' : $matches[2]);
      if (!isset($calculation[$unit])) {
        return 0;
      }
      if (isset($seen[$unit])) {
        return 0;
      }
      $seen[$unit] = TRUE;
      $seconds += $calculation[$unit] * $matches[1];
    }

    return $seconds;
  }

}
