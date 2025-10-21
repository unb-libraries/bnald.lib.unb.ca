<?php

namespace Drupal\samlauth\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use OneLogin\Saml2\Constants as SamlConstants;

/**
 * Trait to prevent repeating some code across configuration screens.
 */
trait SamlauthConfigureTrait {

  // @todo Start using when minimum PHP version is >= 8.2.
  /**
   * Value used for "other" option - never gets saved; must not clash with
   * possible options.
   */
  //private const UNSAVED_OTHER_OPTION = '*';

  /**
   * Adds form elements using the type and title found in the config schema.
   *
   * This way we don't need to define these in two places. (If we don't define
   * them in the schema, configuration translation/inspector forms look strange;
   * at least the translation form is important.)
   */
  protected function addElementsFromSchema(array &$build, array $schema_definition, Config $config, array $elements) {
    foreach ($elements as $key => $data) {
      assert(!empty($schema_definition[$key]['type']), "'$key.type' not found in schema definition for samlauth.authentication.");

      $label = $schema_definition[$key]['label'] ?? 'Label not found.';
      $default_default = NULL;
      switch ($schema_definition[$key]['type']) {
        case 'boolean':
          $type = 'checkbox';
          break;

        case 'string':
        case 'label':
          $type = 'textfield';
          break;

        case 'text':
          $type = 'textarea';
          break;

        case 'integer':
          $type = 'number';
          break;

        case 'sequence':
          // This one is very much specific to our situation.
          $type = 'checkboxes';
          $default_default = [];
          assert(!empty($data['#options']), "No #options set for $key (type=sequence).");
          break;

        default:
          $type = '';
      }
      // We must only call this helper function for simple elements.
      assert(!empty($type), "Unrecognized type $type in addElementsFromSchema().");

      $build[$key] = [
        '#type' => $type,
        // A label of any config element (as defined in the schema.yml) is
        // translatable through 'UI translation'.
        '#title' => $this->t($label),
        '#default_value' => $config->get($key),
      ];
      if (isset($default_default) && !isset($build[$key]['#default_value'])) {
        $build[$key]['#default_value'] = $default_default;
      }
      if (is_array($data)) {
        $build[$key] = array_merge($build[$key], $data);
      }
      elseif ($data) {
        $build[$key]['#description'] = $data;
      }
    }
  }

  /**
   * Adds a 'select or other' style NameID selector.
   *
   * The NameID format value isn't required; only when "Other" is selected.
   */
  protected function addNameID(array &$build, array $schema_definition, Config $config) {
    $this->addElementsFromSchema($build, $schema_definition, $config, [
      'sp_name_id_format' => $this->t('The format for the NameID attribute to request from the IdP / to send in logout requests.*'),
    ]);
    // Keep default_value and title.
    $build['sp_name_id_format']['#type'] = 'select';
    $build['sp_name_id_format']['#options'] = [
      '' => $this->t('- Select -'),
      SamlConstants::NAMEID_UNSPECIFIED => $this->t('Unspecified'),
      SamlConstants::NAMEID_PERSISTENT => $this->t('Persistent'),
      SamlConstants::NAMEID_EMAIL_ADDRESS => $this->t('Email address'),
      SamlConstants::NAMEID_ENTITY => $this->t('Entity'),
      SamlConstants::NAMEID_WINDOWS_DOMAIN_QUALIFIED_NAME => $this->t('Windows domain qualified name'),
      SamlConstants::NAMEID_X509_SUBJECT_NAME => $this->t('X.509 subject name'),
      SamlConstants::NAMEID_TRANSIENT => $this->t('Transient'),
      SamlConstants::NAMEID_ENCRYPTED => $this->t('Encrypted'),
      SamlConstants::NAMEID_KERBEROS => $this->t('Kerberos'),
      '*' => $this->t('- Other -'),
    ];
    $build['sp_name_id_format_'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other NameID Format'),
      '#description' => $this->t('This value usually has a format like "@value".', ['@value' => SamlConstants::NAMEID_UNSPECIFIED]),
      '#states' => [
        'visible' => [
          ':input[name="sp_name_id_format"]' => [
            ['value' => '*'],
          ],
        ],
        'required' => [
          ':input[name="sp_name_id_format"]' => [
            ['value' => '*'],
          ],
        ],
      ],
    ];
    $default_value = $config->get('sp_name_id_format');
    // If $default_value === '*', it cannot be saved as
    // such while submitting the form.
    if ($default_value && (!isset($build['sp_name_id_format']['#options'][$default_value])
        || $default_value === '*')) {
      $build['sp_name_id_format']['#default_value'] = '*';
      $build['sp_name_id_format_']['#default_value'] = $default_value;
    }
  }

  /**
   * Saves the form state from NameID selector(s) in config.
   */
  protected function setNameID(FormStateInterface $form_state, Config $config) {
    $value = $form_state->getValue('sp_name_id_format');
    if ($value === '*') {
      $value = $form_state->getValue('sp_name_id_format_');
    }
    if ($value) {
      $config->set('sp_name_id_format', $value);
    }
    else {
      $config->clear('sp_name_id_format');
    }
  }

}

