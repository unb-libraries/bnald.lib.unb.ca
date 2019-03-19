<?php

namespace Drupal\bnald_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows to place static text as a block.
 *
 * @Block(
 *   id = "static_text",
 *   admin_label = @Translation("Static Text"),
 *   category = @Translation("Text"),
 * )
 */
class StaticText extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $text = isset($config['text']) ? $config['text'] : '';
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $text,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#description' => $this->t('The text to display'),
      '#default_value' => isset($config['text']) ? $config['text'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['text'] = $values['text'];
  }

}
