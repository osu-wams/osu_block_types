<?php

declare(strict_types=1);

namespace Drupal\osu_block_types\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OSU Stat Card Block.
 */
#[Block(
  id: 'osu_stat_card',
  admin_label: new TranslatableMarkup('Stat Card'),
  category: new TranslatableMarkup('OSU')
)]
class StatCardBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    return parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['stat_card_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('Add an optional prefix to be show before the number.'),
      '#default_value' => $this->configuration['stat_card_prefix'],
    ];
    $form['stat_card_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Stat Number'),
      '#description' => $this->t('Number to be used in Stat Card.'),
      '#default_value' => $this->configuration['stat_card_number'],
      '#step' => '.01',
      '#required' => TRUE,
    ];
    $form['stat_card_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#description' => $this->t('Add an optional suffix to be show before the number.'),
      '#default_value' => $this->configuration['stat_card_suffix'],
    ];
    $form['stat_card_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#description' => $this->t('For aditional information.'),
      '#default_value' => $this->configuration['stat_card_description']['value'],
      '#format' => 'text_and_links',
      '#allowed_formats' => ['text_and_links'],
    ];
    $form['stat_card_count_up'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count Up'),
      '#description' => $this->t('Enable count up feature.'),
      '#default_value' => $this->configuration['stat_card_count_up'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['stat_card_prefix'] = $form_state->getValue('stat_card_prefix');
    $this->configuration['stat_card_number'] = $form_state->getValue('stat_card_number');
    $this->configuration['stat_card_suffix'] = $form_state->getValue('stat_card_suffix');
    $this->configuration['stat_card_count_up'] = $form_state->getValue('stat_card_count_up');
    $this->configuration['stat_card_description'] = $form_state->getValue('stat_card_description');
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $description = $form_state->getValue('stat_card_description');
    $format = FilterFormat::load($description['format']);

    // Ensure the author has access to the format.
    if (!$format || !$format->access('use')) {
      $form_state->setErrorByName(
        'stat_card_description',
        $this->t('You are not allowed to use the selected text format.')
      );
    }

    /* This one lone ungly line removes ALL the html elements so we can get a
     * count on the actualy entered text.
     */
    if (mb_strlen(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($description['value']))))) > 300) {
      $form_state->setErrorByName(
        'stat_card_description',
        $this->t('Description too long. Please keep description under 300 characters.'),
      );
    }

    return parent::blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $stat_card_number = $this->configuration['stat_card_number'];

    // Make some text not have decmial places, for reasons.
    if (str_contains($stat_card_number, '.')) {
      $stat_card_number_formatted = number_format((float) $stat_card_number, 2);
    }
    else {
      $stat_card_number_formatted = number_format((float) $stat_card_number);
    }

    return [
      '#type' => 'component',
      '#component' => 'osu_block_types:stat-card',
      '#props' => [
        'stat_card_number' => $stat_card_number_formatted,
        'stat_card_prefix' => (string) $this->configuration['stat_card_prefix'],
        'stat_card_suffix' => (string) $this->configuration['stat_card_suffix'],
        'stat_card_count_up' => (bool) $this->configuration['stat_card_count_up'],
      ],
      '#slots' => [
        'stat_card_description' => [
          '#type' => 'processed_text',
          '#text' => $this->configuration['stat_card_description']['value'],
          '#format' => $this->configuration['stat_card_description']['format'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'stat_card_prefix' => '',
      'stat_card_number' => 0.00,
      'stat_card_suffix' => '',
      'stat_card_count_up' => 0,
      'stat_card_description' => [
        'value' => '',
        'format' => '',
      ],
    ] + parent::defaultConfiguration();
  }

}
